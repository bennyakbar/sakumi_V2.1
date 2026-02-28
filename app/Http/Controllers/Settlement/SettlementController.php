<?php

namespace App\Http\Controllers\Settlement;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Settlement;
use App\Models\Student;
use App\Services\ControlledReceiptService;
use App\Services\ReceiptVerificationService;
use App\Services\SchoolIdentityService;
use App\Services\SettlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettlementController extends Controller
{
    public function __construct(
        private readonly SettlementService $settlementService,
        private readonly SchoolIdentityService $schoolIdentityService,
        private readonly ReceiptVerificationService $verificationService,
        private readonly ControlledReceiptService $controlledReceiptService,
    ) {}

    public function index(Request $request): View
    {
        $query = Settlement::with(['student.schoolClass', 'creator']);
        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search, $likeOperator) {
                $q->where('settlement_number', $likeOperator, "%{$search}%")
                    ->orWhereHas('student', fn ($sq) => $sq->where('name', $likeOperator, "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $settlements = $query->latest()->paginate(15)->withQueryString();

        return view('settlements.index', compact('settlements'));
    }

    public function create(Request $request): View
    {
        $students = Student::with('schoolClass')->where('status', 'active')->orderBy('name')->get();
        $selectedStudentId = $request->input('student_id');
        $selectedInvoiceId = $request->input('invoice_id');
        $outstandingInvoices = collect();

        if ($selectedStudentId) {
            $outstandingInvoices = Invoice::where('student_id', $selectedStudentId)
                ->withSum(['allocations as settled_amount' => function ($q) {
                    $q->whereHas('settlement', fn ($sq) => $sq->where('status', 'completed'));
                }], 'amount')
                ->with('items.feeType')
                ->orderBy('due_date')
                ->get()
                ->map(function (Invoice $invoice) {
                    $settled = (float) ($invoice->settled_amount ?? 0);
                    $outstanding = (float) $invoice->total_amount - $settled;
                    if ($outstanding < 0) {
                        Log::warning('Over-settled invoice detected', [
                            'invoice_id' => $invoice->id,
                            'total_amount' => $invoice->total_amount,
                            'settled_amount' => $settled,
                        ]);
                        $outstanding = 0;
                    }
                    $invoice->outstanding_amount = $outstanding;
                    return $invoice;
                })
                ->filter(fn (Invoice $invoice) => $invoice->outstanding_amount > 0)
                ->values();

            if (!$selectedInvoiceId && $outstandingInvoices->isNotEmpty()) {
                $selectedInvoiceId = (int) $outstandingInvoices->first()->id;
            }

            if ($selectedInvoiceId) {
                $outstandingInvoices = $outstandingInvoices
                    ->where('id', (int) $selectedInvoiceId)
                    ->values();
            }
        }

        return view('settlements.create', compact('students', 'outstandingInvoices', 'selectedStudentId', 'selectedInvoiceId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $unitId = session('current_unit_id');

        $validated = $request->validate([
            'student_id' => ['required', Rule::exists('students', 'id')->where('unit_id', $unitId)],
            'invoice_id' => ['required', Rule::exists('invoices', 'id')->where('unit_id', $unitId)],
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,transfer,qris',
            'amount' => 'required|numeric|min:1',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $invoice = Invoice::query()
            ->where('id', (int) $validated['invoice_id'])
            ->where('student_id', (int) $validated['student_id'])
            ->where('unit_id', $unitId)
            ->firstOrFail();

        $settledAmount = (float) $invoice->allocations()
            ->whereHas('settlement', fn ($q) => $q->where('status', 'completed'))
            ->sum('amount');
        $outstanding = (float) $invoice->total_amount - $settledAmount;
        if ($outstanding < 0) {
            Log::warning('Over-settled invoice detected', [
                'invoice_id' => $invoice->id,
                'total_amount' => $invoice->total_amount,
                'settled_amount' => $settledAmount,
            ]);
            $outstanding = 0;
        }
        $amount = (float) $validated['amount'];

        if ($amount > $outstanding) {
            return back()->withInput()->withErrors([
                'amount' => __('message.payment_exceeds_outstanding'),
            ]);
        }

        if ($outstanding <= 0) {
            return back()->withInput()->withErrors([
                'invoice_id' => __('message.invoice_no_balance'),
            ]);
        }

        $allocations = [(int) $validated['invoice_id'] => $amount];

        try {
            $settlement = $this->settlementService->createSettlement(
                data: [
                    'student_id' => (int) $validated['student_id'],
                    'payment_date' => $validated['payment_date'],
                    'payment_method' => $validated['payment_method'],
                    'total_amount' => $amount,
                    'reference_number' => $validated['reference_number'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ],
                allocations: $allocations,
                userId: (int) auth()->id(),
            );

            return redirect()->route('settlements.show', $settlement)
                ->with('success', __('message.settlement_created', ['number' => $settlement->settlement_number]));
        } catch (\Throwable $e) {
            Log::error('Failed to create settlement', ['message' => $e->getMessage()]);
            return back()->withInput()->with('error', __('message.settlement_create_failed', ['error' => $e->getMessage()]));
        }
    }

    public function show(Settlement $settlement): View
    {
        $settlement->load([
            'student.schoolClass',
            'allocations.invoice',
            'creator',
            'canceller',
            'voider',
        ]);

        return view('settlements.show', compact('settlement'));
    }

    public function print(Request $request, Settlement $settlement): View|RedirectResponse
    {
        $settlement->load(['student.schoolClass', 'allocations.invoice', 'creator']);
        $schoolData = $this->schoolIdentityService->resolve($settlement->unit_id);

        if (! Schema::hasTable('receipts')) {
            $verificationCode = $this->verificationService->makeSettlementCode($settlement);
            $verificationUrl = $this->verificationService->makeVerifyUrl($verificationCode);
            $watermarkText = $this->verificationService->makeWatermark($verificationCode, 'ORIGINAL');

            return view('settlements.print', [
                'settlement' => $settlement,
                'verificationCode' => $verificationCode,
                'verificationUrl' => $verificationUrl,
                'watermarkText' => $watermarkText,
                'receiptIssuedAt' => $settlement->created_at,
                'receiptPrintedAt' => now(),
                'receiptPrintStatus' => 'ORIGINAL',
                'schoolData' => $schoolData,
            ]);
        }

        $receipt = $this->controlledReceiptService->issueForSettlement($settlement);
        $reason = $this->resolveReprintReason($request);
        $isReprintAttempt = $receipt->print_count > 0;
        $isReprintAuthority = (bool) $request->user()?->hasAnyRole(['bendahara', 'super_admin', 'admin_tu_mi', 'admin_tu_ra', 'admin_tu_dta', 'admin_tu']);

        if ($isReprintAttempt && ! $isReprintAuthority) {
            abort(403, 'Only bendahara/admin can reprint settlement receipts.');
        }

        if ($isReprintAttempt && blank($reason)) {
            return view('receipts.reprint-reason', [
                'transaction' => null,
                'settlement' => $settlement,
                'receipt' => $receipt,
            ]);
        }

        $receipt = $this->controlledReceiptService->registerPrintForSettlement(
            settlement: $settlement,
            user: $request->user(),
            reason: $reason,
            ipAddress: $request->ip(),
            device: (string) $request->userAgent(),
        );

        $printStatus = $this->controlledReceiptService->printStatus($receipt);
        $verificationCode = $receipt->verification_code;
        $verificationUrl = $this->verificationService->makeVerifyUrl($verificationCode);
        $watermarkText = $this->verificationService->makeWatermark($verificationCode, $printStatus);

        return view('settlements.print', [
            'settlement' => $settlement,
            'verificationCode' => $verificationCode,
            'verificationUrl' => $verificationUrl,
            'watermarkText' => $watermarkText,
            'receiptIssuedAt' => $receipt->issued_at,
            'receiptPrintedAt' => $receipt->printed_at,
            'receiptPrintStatus' => $printStatus,
            'schoolData' => $schoolData,
        ]);
    }

    private function resolveReprintReason(Request $request): ?string
    {
        $reasonType = trim((string) $request->query('reason_type', ''));
        $reasonOther = trim((string) $request->query('reason_other', ''));

        if ($reasonType === '') {
            return null;
        }

        if ($reasonType === 'other') {
            return $reasonOther !== '' ? $reasonOther : null;
        }

        return $reasonType;
    }

    public function void(Request $request, Settlement $settlement): RedirectResponse
    {
        $request->validate([
            'void_reason' => 'required|string|max:1000',
        ]);

        try {
            $this->settlementService->void(
                settlement: $settlement,
                userId: (int) auth()->id(),
                reason: $request->input('void_reason'),
            );

            return redirect()->route('settlements.show', $settlement)
                ->with('success', __('message.settlement_voided'));
        } catch (\Throwable $e) {
            return back()->with('error', __('message.settlement_void_failed', ['error' => $e->getMessage()]));
        }
    }

    public function destroy(Request $request, Settlement $settlement): RedirectResponse
    {
        try {
            $this->settlementService->cancel(
                settlement: $settlement,
                userId: (int) auth()->id(),
                reason: (string) ($request->input('cancellation_reason') ?: __('message.cancelled_by_admin')),
            );

            return redirect()->route('settlements.index')
                ->with('success', __('message.settlement_cancelled'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
