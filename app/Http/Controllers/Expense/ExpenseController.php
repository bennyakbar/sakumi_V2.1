<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\ExpenseBudget;
use App\Models\ExpenseEntry;
use App\Models\ExpenseFeeSubcategory;
use App\Models\FeeType;
use App\Services\ExpenseManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseManagementService $expenseManagementService,
    ) {
    }

    public function index(Request $request): View
    {
        $entries = ExpenseEntry::query()
            ->with(['subcategory.category', 'feeType', 'postedTransaction', 'approver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->input('status')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('entry_date', '>=', (string) $request->input('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('entry_date', '<=', (string) $request->input('date_to')))
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
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'supporting_doc' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        $feeType = FeeType::query()->with('expenseFeeSubcategory')->findOrFail((int) $validated['fee_type_id']);
        if (!$feeType->expense_fee_subcategory_id) {
            return back()->withInput()->withErrors(['fee_type_id' => 'Selected fee type is not configured as expense fee type.']);
        }

        $budgetWarning = $this->expenseManagementService->checkBudget(
            (int) $feeType->expense_fee_subcategory_id,
            $validated['entry_date'],
            (float) $validated['amount'],
        );

        if ($budgetWarning && ! $request->boolean('confirm_over_budget')) {
            return back()->withInput()->with('budget_warning', $budgetWarning);
        }

        $data = [
            ...$validated,
            'expense_fee_subcategory_id' => $feeType->expense_fee_subcategory_id,
        ];

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('expenses/receipts', 'public');
        }
        if ($request->hasFile('supporting_doc')) {
            $data['supporting_doc_path'] = $request->file('supporting_doc')->store('expenses/supporting-docs', 'public');
        }

        $this->expenseManagementService->createDraft($data, (int) auth()->id());

        return redirect()->route('expenses.index')->with('success', 'Expense draft created.');
    }

    public function approve(ExpenseEntry $expense): RedirectResponse
    {
        try {
            $this->expenseManagementService->approveAndPost($expense, (int) auth()->id());
            return redirect()->route('expenses.index')->with('success', 'Expense approved and posted.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function duplicate(ExpenseEntry $expense): RedirectResponse
    {
        return redirect()->route('expenses.index')->withInput([
            'fee_type_id' => $expense->fee_type_id,
            'payment_method' => $expense->payment_method,
            'vendor_name' => $expense->vendor_name,
            'amount' => $expense->amount,
            'description' => $expense->description,
            'entry_date' => now()->toDateString(),
        ]);
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
            ->select(
                'expense_fee_subcategory_id',
                DB::raw('SUM(amount) as realized_amount'),
                DB::raw('SUM(estimated_amount) as estimated_total'),
            )
            ->whereIn('status', ['approved', 'posted'])
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->groupBy('expense_fee_subcategory_id')
            ->get()
            ->keyBy('expense_fee_subcategory_id');

        $rows = $budgets->map(function (ExpenseBudget $budget) use ($realization) {
            $row = $realization[$budget->expense_fee_subcategory_id] ?? null;
            $realized = (float) ($row?->realized_amount ?? 0);
            $estimated = (float) ($row?->estimated_total ?? 0);
            $planned = (float) $budget->budget_amount;

            return [
                'category' => $budget->subcategory?->category?->name ?? '-',
                'subcategory' => $budget->subcategory?->name ?? '-',
                'planned' => $planned,
                'estimated' => $estimated,
                'realized' => $realized,
                'variance' => $planned - $realized,
            ];
        });

        $subcategories = ExpenseFeeSubcategory::query()->with('category')->where('is_active', true)->orderBy('name')->get();

        return view('expenses.budget-report', compact('rows', 'month', 'year', 'subcategories'));
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
