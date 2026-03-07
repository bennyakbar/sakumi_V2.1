<?php

namespace App\Http\Controllers\Reconciliation;

use App\Http\Controllers\Controller;
use App\Models\BankReconciliationLine;
use App\Models\BankReconciliationSession;
use App\Models\Transaction;
use App\Services\BankReconciliationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BankReconciliationController extends Controller
{
    public function __construct(
        private readonly BankReconciliationService $bankReconciliationService,
    ) {
    }

    public function index(): View
    {
        $sessions = BankReconciliationSession::query()
            ->withCount([
                'lines as unmatched_count' => fn ($q) => $q->where('match_status', 'unmatched'),
                'lines as matched_count' => fn ($q) => $q->where('match_status', 'matched'),
                'lines as adjusted_count' => fn ($q) => $q->where('match_status', 'adjusted'),
            ])
            ->latest()
            ->paginate(15);

        return view('bank-reconciliation.index', compact('sessions'));
    }

    public function storeSession(Request $request): RedirectResponse
    {
        $unitId = session('current_unit_id');

        $validated = $request->validate([
            'bank_account_name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('bank_reconciliation_sessions', 'bank_account_name')
                    ->where('unit_id', $unitId)
                    ->where('period_year', (int) $request->input('period_year'))
                    ->where('period_month', (int) $request->input('period_month')),
            ],
            'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $session = $this->bankReconciliationService->createSession([
            ...$validated,
            'opening_balance' => $validated['opening_balance'] ?? 0,
        ], (int) auth()->id());

        return redirect()->route('bank-reconciliation.show', $session)->with('success', 'Reconciliation session created.');
    }

    public function show(BankReconciliationSession $bankReconciliation): View
    {
        $bankReconciliation->load(['lines.matchedTransaction', 'logs.actor']);

        $transactions = Transaction::query()
            ->whereMonth('transaction_date', $bankReconciliation->period_month)
            ->whereYear('transaction_date', $bankReconciliation->period_year)
            ->where('status', 'completed')
            ->orderByDesc('transaction_date')
            ->limit(200)
            ->get();

        $summary = [
            'total_debit' => (float) $bankReconciliation->lines->where('type', 'debit')->sum('amount'),
            'total_credit' => (float) $bankReconciliation->lines->where('type', 'credit')->sum('amount'),
            'unmatched_count' => $bankReconciliation->lines->where('match_status', 'unmatched')->count(),
            'matched_count' => $bankReconciliation->lines->where('match_status', 'matched')->count(),
            'adjusted_count' => $bankReconciliation->lines->where('match_status', 'adjusted')->count(),
        ];
        $summary['difference'] = (float) $bankReconciliation->lines
            ->where('match_status', 'unmatched')
            ->sum(fn ($line) => $line->type === 'debit' ? (float) $line->amount : -(float) $line->amount);

        return view('bank-reconciliation.show', compact('bankReconciliation', 'transactions', 'summary'));
    }

    public function import(BankReconciliationSession $bankReconciliation, Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        try {
            $count = $this->bankReconciliationService->importCsv($bankReconciliation, $request->file('file'), (int) auth()->id());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('bank-reconciliation.show', $bankReconciliation)
            ->with('success', "Imported {$count} line(s).");
    }

    public function match(BankReconciliationSession $bankReconciliation, BankReconciliationLine $line, Request $request): RedirectResponse
    {
        abort_unless($line->bank_reconciliation_session_id === $bankReconciliation->id, 404);

        $validated = $request->validate([
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
        ]);

        try {
            $this->bankReconciliationService->matchLine($line, (int) $validated['transaction_id'], (int) auth()->id());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('bank-reconciliation.show', $bankReconciliation)->with('success', 'Line matched.');
    }

    public function unmatch(BankReconciliationSession $bankReconciliation, BankReconciliationLine $line): RedirectResponse
    {
        abort_unless($line->bank_reconciliation_session_id === $bankReconciliation->id, 404);

        try {
            $this->bankReconciliationService->unmatchLine($line, (int) auth()->id());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('bank-reconciliation.show', $bankReconciliation)->with('success', 'Line unmatched.');
    }

    public function close(BankReconciliationSession $bankReconciliation): RedirectResponse
    {
        try {
            $this->bankReconciliationService->closeSession($bankReconciliation, (int) auth()->id());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('bank-reconciliation.show', $bankReconciliation)->with('success', 'Session closed.');
    }
}
