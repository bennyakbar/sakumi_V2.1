<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentObligation;
use App\Models\Transaction;
use App\Models\FeeType;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $with = ['student.schoolClass', 'items', 'creator'];
        if (Schema::hasTable('receipts')) {
            $with[] = 'receipt';
        }

        $query = Transaction::with($with);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search): void {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('student', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', (string) $request->input('payment_method'));
        }

        if ($request->filled('class_id')) {
            $classId = (int) $request->input('class_id');
            $query->whereHas('student', fn($sq) => $sq->where('class_id', $classId));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', (string) $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', (string) $request->input('date_to'));
        }

        $transactions = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();
        $transactions->getCollection()->transform(function (Transaction $transaction) {
            $transaction->setAttribute('receipt_print_count', (int) ($transaction->receipt?->print_count ?? 0));

            return $transaction;
        });

        $classes = SchoolClass::query()->orderBy('name')->get(['id', 'name']);

        return view('transactions.index', compact('transactions', 'classes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $students = Student::query()
            ->with('schoolClass')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'class_id']);
        $activeInvoiceHints = Invoice::query()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->orderBy('due_date')
            ->get(['id', 'student_id', 'invoice_number', 'total_amount', 'paid_amount'])
            ->groupBy('student_id')
            ->map(function ($invoices): array {
                $first = $invoices->first();
                $outstandingTotal = (float) $invoices->sum(
                    fn (Invoice $invoice): float => max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount)
                );

                return [
                    'count' => $invoices->count(),
                    'first_invoice_id' => (int) $first->id,
                    'first_invoice_number' => (string) $first->invoice_number,
                    'outstanding_total' => $outstandingTotal,
                ];
            })
            ->all();

        $incomeFeeTypes = FeeType::query()
            ->where('is_active', true)
            ->where('code', 'not like', 'EXP-%')
            ->orderBy('name')
            ->get(['id', 'name']);
        $expenseFeeTypes = FeeType::query()
            ->with(['expenseFeeSubcategory.category'])
            ->where('is_active', true)
            ->whereNotNull('expense_fee_subcategory_id')
            ->get()
            ->sortBy(fn(FeeType $feeType): string => sprintf(
                '%03d-%03d-%s',
                (int) ($feeType->expenseFeeSubcategory?->category?->sort_order ?? 999),
                (int) ($feeType->expenseFeeSubcategory?->sort_order ?? 999),
                $feeType->name
            ))
            ->map(fn(FeeType $feeType): array => [
                'id' => $feeType->id,
                'name' => $feeType->name,
                'category' => $feeType->expenseFeeSubcategory?->category?->name ?? __('message.uncategorized'),
                'subcategory' => $feeType->expenseFeeSubcategory?->name ?? __('message.general'),
            ])->values();
        $canCreateExpense = auth()->user()?->can('transactions.expense.create') ?? false;

        return view('transactions.create', compact('students', 'activeInvoiceHints', 'incomeFeeTypes', 'expenseFeeTypes', 'canCreateExpense'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $unitId = session('current_unit_id');
        $transactionType = (string) $request->input('type', 'income');

        if (!in_array($transactionType, ['income', 'expense'], true)) {
            return back()->withInput()->withErrors(['type' => __('message.invalid_transaction_type')]);
        }

        if ($transactionType === 'expense' && !(auth()->user()?->can('transactions.expense.create'))) {
            abort(403, __('message.expense_not_authorized'));
        }

        $validated = $request->validate([
            'type' => 'nullable|in:income,expense',
            'student_id' => [
                'nullable',
                Rule::prohibitedIf($transactionType === 'expense'),
                Rule::exists('students', 'id')->where('unit_id', $unitId),
            ],
            'transaction_date' => 'required|date',
            'payment_method' => 'required|in:cash,transfer,qris',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.fee_type_id' => ['required', Rule::exists('fee_types', 'id')->where('unit_id', $unitId)],
            'items.*.amount' => 'required|numeric|gt:0',
            'items.*.description' => 'nullable|string',
        ]);

        try {
            $items = collect($validated['items'])
                ->values()
                ->map(fn(array $item): array => [
                    'fee_type_id' => (int) $item['fee_type_id'],
                    'amount' => (float) $item['amount'],
                    'description' => $item['description'] ?? null,
                ])->all();
            $studentId = isset($validated['student_id']) ? (int) $validated['student_id'] : null;
            $feeTypeIds = collect($items)->pluck('fee_type_id')->map(fn ($id) => (int) $id)->unique()->values();
            $hasMonthlyFee = FeeType::query()
                ->whereIn('id', $feeTypeIds)
                ->where('is_monthly', true)
                ->exists();

            if ($transactionType === 'income' && $studentId) {
                $openInvoice = Invoice::query()
                    ->where('student_id', $studentId)
                    ->whereIn('status', ['unpaid', 'partially_paid'])
                    ->orderBy('due_date')
                    ->orderBy('id')
                    ->first();

                if ($openInvoice) {
                    return redirect()->route('settlements.create', [
                        'student_id' => $studentId,
                        'invoice_id' => $openInvoice->id,
                    ])->with('status', __('message.transaction_redirect_to_settlement', [
                        'invoice' => $openInvoice->invoice_number,
                    ]));
                }

                $hasUnpaidObligation = StudentObligation::query()
                    ->where('student_id', $studentId)
                    ->where('is_paid', false)
                    ->exists();
                if ($hasUnpaidObligation) {
                    return back()->withInput()->withErrors([
                        'student_id' => __('message.student_has_unpaid_obligations_use_invoice'),
                    ]);
                }

                if ($hasMonthlyFee) {
                    return back()->withInput()->withErrors([
                        'student_id' => __('message.monthly_fee_must_use_invoice'),
                    ]);
                }
            }

            if ($transactionType === 'income' && !$studentId) {
                if ($hasMonthlyFee) {
                    return back()->withInput()->withErrors([
                        'student_id' => __('message.student_required_for_monthly_fee'),
                    ]);
                }
            }

            $transaction = $transactionType === 'expense'
                ? $this->transactionService->createExpense(
                    data: [
                        'transaction_date' => $validated['transaction_date'],
                        'payment_method' => $validated['payment_method'],
                        'description' => $validated['description'] ?? null,
                    ],
                    items: $items,
                    userId: (int) auth()->id(),
                )
                : $this->transactionService->createIncome(
                    data: [
                        'student_id' => $studentId,
                        'accounting_event_type' => 'payment.direct.posted',
                        'transaction_date' => $validated['transaction_date'],
                        'payment_method' => $validated['payment_method'],
                        'description' => $validated['description'] ?? null,
                    ],
                    items: $items,
                    userId: (int) auth()->id(),
                );

            return redirect()->route('transactions.show', $transaction)
                ->with('success', __('message.transaction_created', ['number' => $transaction->transaction_number]));
        } catch (\Throwable $e) {
            Log::error('Failed to create transaction', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', __('message.transaction_create_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction): View
    {
        $transaction->load(['student.schoolClass', 'items.feeType', 'creator']);
        if (Schema::hasTable('receipts')) {
            $transaction->load('receipt');
        }
        $transaction->setAttribute('receipt_print_count', (int) ($transaction->receipt?->print_count ?? 0));

        return view('transactions.show', compact('transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Transactions usually shouldn't be edited directly to maintain audit trail.
        // If needed, can implement void/cancel logic instead.
        return back()->with('error', __('message.transaction_no_edit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        try {
            $this->transactionService->cancel(
                transaction: $transaction->load('items'),
                userId: (int) auth()->id(),
                reason: (string) ($request->input('cancellation_reason') ?: __('message.cancelled_by_admin')),
            );

            return redirect()->route('transactions.index')
                ->with('success', __('message.transaction_cancelled'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

    }
}
