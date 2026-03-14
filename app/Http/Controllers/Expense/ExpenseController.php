<?php

namespace App\Http\Controllers\Expense;

use App\Exports\ReportRowsExport;
use App\Http\Controllers\Controller;
use App\Models\AccountingEvent;
use App\Models\ExpenseAttachment;
use App\Models\ExpenseBudget;
use App\Models\ExpenseEntry;
use App\Models\ExpenseFeeSubcategory;
use App\Models\FeeType;
use App\Models\JournalEntryV2;
use App\Services\ExpenseManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseManagementService $expenseManagementService,
    ) {
    }

    public function index(Request $request): View
    {
        $entries = ExpenseEntry::query()
            ->with(['subcategory.category', 'feeType', 'postedTransaction', 'approver', 'attachments'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->input('status')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('entry_date', '>=', (string) $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('entry_date', '<=', (string) $request->input('date_to')))
            ->when($request->filled('vendor'), fn ($q) => $q->where('vendor_name', 'like', '%' . (string) $request->input('vendor') . '%'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $expenseFeeTypes = FeeType::query()
            ->with(['expenseFeeSubcategory.category'])
            ->where('is_active', true)
            ->whereNotNull('expense_fee_subcategory_id')
            ->orderBy('name')
            ->get();

        return view('expenses.index', compact('entries', 'expenseFeeTypes'));
    }

    public function show(ExpenseEntry $expense): View
    {
        $expense->load([
            'subcategory.category',
            'feeType',
            'postedTransaction.items.feeType',
            'approver',
            'creator',
            'attachments.uploader',
            'auditLogs.user',
        ]);

        // Budget reference for the entry's period
        $budgetInfo = null;
        if ($expense->expense_fee_subcategory_id && $expense->entry_date) {
            $month = $expense->entry_date->month;
            $year = $expense->entry_date->year;

            $budget = ExpenseBudget::query()
                ->where('expense_fee_subcategory_id', $expense->expense_fee_subcategory_id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if ($budget) {
                $realized = (float) ExpenseEntry::query()
                    ->where('expense_fee_subcategory_id', $expense->expense_fee_subcategory_id)
                    ->whereIn('status', ['approved', 'posted'])
                    ->whereMonth('entry_date', $month)
                    ->whereYear('entry_date', $year)
                    ->sum('amount');

                $budgetInfo = [
                    'planned' => (float) $budget->budget_amount,
                    'realized' => $realized,
                    'remaining' => (float) $budget->budget_amount - $realized,
                    'month' => $month,
                    'year' => $year,
                ];
            }
        }

        // Journal entries for posted transactions
        $journalEntries = collect();
        if ($expense->posted_transaction_id) {
            $event = AccountingEvent::query()
                ->where('source_type', 'transaction')
                ->where('source_id', $expense->posted_transaction_id)
                ->first();

            if ($event) {
                $journalEntries = JournalEntryV2::query()
                    ->where('accounting_event_id', $event->id)
                    ->orderBy('line_no')
                    ->get();
            }
        }

        return view('expenses.show', compact('expense', 'budgetInfo', 'journalEntries'));
    }

    public function store(Request $request): RedirectResponse
    {
        $unitId = session('current_unit_id');

        $validated = $request->validate([
            'fee_type_id' => ['required', \Illuminate\Validation\Rule::exists('fee_types', 'id')->where('unit_id', $unitId)],
            'entry_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
            'vendor_name' => ['nullable', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'internal_notes' => ['nullable', 'string', 'max:500'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        $feeType = FeeType::query()->with('expenseFeeSubcategory')->findOrFail((int) $validated['fee_type_id']);
        if (!$feeType->expense_fee_subcategory_id) {
            return back()->withInput()->withErrors(['fee_type_id' => 'Selected fee type is not configured as expense fee type.']);
        }

        $entry = $this->expenseManagementService->createDraft([
            ...$validated,
            'expense_fee_subcategory_id' => $feeType->expense_fee_subcategory_id,
        ], (int) auth()->id());

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('expense-attachments', 'local');
                ExpenseAttachment::create([
                    'expense_entry_id' => $entry->id,
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);
                $this->expenseManagementService->logAttachmentUploaded($entry, (int) auth()->id(), $file->getClientOriginalName());
            }
        }

        return redirect()->route('expenses.index')->with('success', __('message.expense_draft_created'));
    }

    public function approve(ExpenseEntry $expense): RedirectResponse
    {
        try {
            $this->expenseManagementService->approveAndPost($expense, (int) auth()->id());
            return redirect()->route('expenses.index')->with('success', __('message.expense_approved'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(ExpenseEntry $expense): RedirectResponse
    {
        try {
            $this->expenseManagementService->cancelDraft($expense, (int) auth()->id());
            return redirect()->route('expenses.index')->with('success', __('message.expense_cancelled'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelPosted(Request $request, ExpenseEntry $expense): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->expenseManagementService->cancelPosted($expense, (int) auth()->id(), $validated['reason']);
            return redirect()->route('expenses.index')->with('success', __('message.expense_voided'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function uploadAttachment(Request $request, ExpenseEntry $expense): RedirectResponse
    {
        // Immutability guard: locked entries cannot receive new attachments
        if ($expense->isLocked()) {
            return back()->with('error', __('message.expense_locked'));
        }

        $request->validate([
            'attachments' => ['required', 'array', 'max:5'],
            'attachments.*' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('expense-attachments', 'local');
            ExpenseAttachment::create([
                'expense_entry_id' => $expense->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
            $this->expenseManagementService->logAttachmentUploaded($expense, (int) auth()->id(), $file->getClientOriginalName());
        }

        return back()->with('success', __('message.expense_attachment_uploaded'));
    }

    public function deleteAttachment(ExpenseAttachment $attachment): RedirectResponse
    {
        $expense = $attachment->expenseEntry;

        // Immutability guard: locked entries cannot have attachments removed
        if ($expense && $expense->isLocked()) {
            return back()->with('error', __('message.expense_locked'));
        }

        $filename = $attachment->original_name;
        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();

        if ($expense) {
            $this->expenseManagementService->logAttachmentDeleted($expense, (int) auth()->id(), $filename);
        }

        return back()->with('success', __('message.expense_attachment_deleted'));
    }

    public function downloadAttachment(ExpenseAttachment $attachment): BinaryFileResponse
    {
        return response()->download(
            Storage::disk('local')->path($attachment->file_path),
            $attachment->original_name,
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $entries = ExpenseEntry::query()
            ->with(['subcategory.category', 'feeType'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->input('status')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('entry_date', '>=', (string) $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('entry_date', '<=', (string) $request->input('date_to')))
            ->when($request->filled('vendor'), fn ($q) => $q->where('vendor_name', 'like', '%' . (string) $request->input('vendor') . '%'))
            ->latest()
            ->get();

        $rows = [['Date', 'Category', 'Subcategory', 'Fee Type', 'Vendor', 'Payment Method', 'Amount', 'Status', 'Description']];
        foreach ($entries as $entry) {
            $rows[] = [
                $entry->entry_date?->format('Y-m-d'),
                $entry->subcategory?->category?->name ?? '-',
                $entry->subcategory?->name ?? '-',
                $entry->feeType?->name ?? '-',
                $entry->vendor_name ?: '-',
                strtoupper($entry->payment_method),
                (float) $entry->amount,
                strtoupper($entry->status),
                $entry->description ?: '-',
            ];
        }

        $format = ReportRowsExport::accountingNumberFormat();

        return Excel::download(
            new ReportRowsExport($rows, ['G' => $format]),
            'expense-entries-' . now()->format('Ymd-His') . '.xlsx',
        );
    }

    public function budgetVsRealization(Request $request): View
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $budgets = ExpenseBudget::query()
            ->with('subcategory.category')
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $realization = ExpenseEntry::query()
            ->select('expense_fee_subcategory_id', DB::raw('SUM(amount) as realized_amount'))
            ->whereIn('status', ['approved', 'posted'])
            ->whereMonth('entry_date', $month)
            ->whereYear('entry_date', $year)
            ->groupBy('expense_fee_subcategory_id')
            ->pluck('realized_amount', 'expense_fee_subcategory_id');

        $rows = $budgets->map(function (ExpenseBudget $budget) use ($realization) {
            $realized = (float) ($realization[$budget->expense_fee_subcategory_id] ?? 0);
            $planned = (float) $budget->budget_amount;

            return [
                'category' => $budget->subcategory?->category?->name ?? '-',
                'subcategory' => $budget->subcategory?->name ?? '-',
                'planned' => $planned,
                'realized' => $realized,
                'variance' => $planned - $realized,
            ];
        });

        $subcategories = ExpenseFeeSubcategory::query()->with('category')->where('is_active', true)->orderBy('name')->get();

        return view('expenses.budget-report', compact('rows', 'month', 'year', 'subcategories'));
    }

    public function exportBudgetReport(Request $request): BinaryFileResponse
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $budgets = ExpenseBudget::query()
            ->with('subcategory.category')
            ->where('month', $month)
            ->where('year', $year)
            ->get();

        $realization = ExpenseEntry::query()
            ->select('expense_fee_subcategory_id', DB::raw('SUM(amount) as realized_amount'))
            ->whereIn('status', ['approved', 'posted'])
            ->whereMonth('entry_date', $month)
            ->whereYear('entry_date', $year)
            ->groupBy('expense_fee_subcategory_id')
            ->pluck('realized_amount', 'expense_fee_subcategory_id');

        $rows = [['Category', 'Subcategory', 'Planned', 'Realized', 'Variance']];
        foreach ($budgets as $budget) {
            $realized = (float) ($realization[$budget->expense_fee_subcategory_id] ?? 0);
            $planned = (float) $budget->budget_amount;
            $rows[] = [
                $budget->subcategory?->category?->name ?? '-',
                $budget->subcategory?->name ?? '-',
                $planned,
                $realized,
                $planned - $realized,
            ];
        }

        $format = ReportRowsExport::accountingNumberFormat();

        return Excel::download(
            new ReportRowsExport($rows, ['C' => $format, 'D' => $format, 'E' => $format]),
            "budget-vs-realization-{$year}-{$month}.xlsx",
        );
    }

    public function storeBudget(Request $request): RedirectResponse
    {
        $unitId = session('current_unit_id');

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'expense_fee_subcategory_id' => ['required', \Illuminate\Validation\Rule::exists('expense_fee_subcategories', 'id')->where('unit_id', $unitId)],
            'budget_amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        ExpenseBudget::query()->updateOrCreate(
            [
                'unit_id' => $unitId,
                'year' => (int) $validated['year'],
                'month' => (int) $validated['month'],
                'expense_fee_subcategory_id' => (int) $validated['expense_fee_subcategory_id'],
            ],
            [
                'budget_amount' => $validated['budget_amount'],
                'notes' => $validated['notes'] ?? null,
                'updated_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]
        );

        return redirect()->route('expenses.budget-report', ['month' => $validated['month'], 'year' => $validated['year']])
            ->with('success', 'Budget saved.');
    }
}
