<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\Transaction;
use App\Services\ControlledReceiptService;
use App\Services\ReceiptVerificationService;
use App\Services\SchoolIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function __construct(
        private readonly ControlledReceiptService $controlledReceiptService,
        private readonly ReceiptVerificationService $verificationService,
        private readonly SchoolIdentityService $schoolIdentityService,
    ) {}

    /**
     * Handle the incoming request.
     */
    public function print(Request $request, Transaction $transaction): View|RedirectResponse
    {
        $transaction->load(['student.schoolClass', 'items.feeType', 'creator']);

        if (! Schema::hasTable('receipts')) {
            $verificationCode = $this->verificationService->makeCode($transaction);
            $verificationUrl = $this->verificationService->makeVerifyUrl($verificationCode);
            $watermarkText = $this->verificationService->makeLegacyWatermark($transaction);
            $school = $this->schoolIdentityService->resolve($transaction->unit_id);

            $view = $transaction->type === 'expense'
                ? 'receipts.print-expense'
                : 'receipts.print';

            return view($view, [
                'transaction' => $transaction,
                'verificationCode' => $verificationCode,
                'verificationUrl' => $verificationUrl,
                'watermarkText' => $watermarkText,
                'receiptIssuedAt' => $transaction->created_at,
                'receiptPrintedAt' => now(),
                'receiptPrintStatus' => 'ORIGINAL',
                ...$school,
            ]);
        }

        $receipt = $this->controlledReceiptService->issue($transaction);
        $reason = $this->resolveReprintReason($request);
        $isReprintAttempt = $receipt->print_count > 0;
        $isReprintAuthority = (bool) $request->user()?->hasAnyRole(['bendahara', 'super_admin', 'admin_tu_mi', 'admin_tu_ra', 'admin_tu_dta', 'admin_tu']);

        if ($isReprintAttempt && ! $isReprintAuthority) {
            abort(403, 'Only bendahara/admin can reprint receipts.');
        }

        if ($isReprintAttempt && blank($reason)) {
            return view('receipts.reprint-reason', [
                'transaction' => $transaction,
                'receipt' => $receipt,
            ]);
        }

        $receipt = $this->controlledReceiptService->registerPrint(
            transaction: $transaction,
            user: $request->user(),
            reason: $reason,
            ipAddress: $request->ip(),
            device: (string) $request->userAgent(),
        );

        $printStatus = $this->controlledReceiptService->printStatus($receipt);
        $verificationCode = $receipt->verification_code;
        $verificationUrl = $this->verificationService->makeVerifyUrl($verificationCode);
        $watermarkText = $this->verificationService->makeWatermark($verificationCode, $printStatus);
        $school = $this->schoolIdentityService->resolve($transaction->unit_id);

        $view = $transaction->type === 'expense'
            ? 'receipts.print-expense'
            : 'receipts.print';

        return view($view, [
            'transaction' => $transaction,
            'verificationCode' => $verificationCode,
            'verificationUrl' => $verificationUrl,
            'watermarkText' => $watermarkText,
            'receiptIssuedAt' => $receipt->issued_at,
            'receiptPrintedAt' => $receipt->printed_at,
            'receiptPrintStatus' => $printStatus,
            ...$school,
        ]);
    }

    public function verify(Request $request, string $transactionNumber): View
    {
        $transaction = Transaction::query()
            ->withoutGlobalScope('unit')
            ->with(['student.schoolClass', 'items.feeType', 'creator'])
            ->where('transaction_number', $transactionNumber)
            ->firstOrFail();

        $receipt = null;
        if (Schema::hasTable('receipts')) {
            $receipt = Receipt::query()
                ->with('transaction')
                ->where('transaction_id', $transaction->id)
                ->first();
        }
        $code = (string) $request->query('code', '');
        $expectedCode = $receipt?->verification_code ?? $this->verificationService->makeCode($transaction);
        $isValid = $this->verificationService->isValid($expectedCode, $code);

        return view('receipts.verify', compact('transaction', 'code', 'isValid', 'expectedCode'));
    }

    public function verifyByCode(string $code): View
    {
        if (! Schema::hasTable('receipts')) {
            abort(404);
        }

        $receipt = Receipt::query()
            ->with([
                'transaction' => fn ($q) => $q->withoutGlobalScope('unit')->with(['student.schoolClass']),
            ])
            ->where('verification_code', strtoupper(trim($code)))
            ->firstOrFail();

        $transaction = $receipt->transaction;
        $isVoided = ! $transaction || $transaction->status === 'cancelled';
        $status = $isVoided ? 'VOIDED' : 'VALID';

        return view('receipts.verify-public', [
            'receipt' => $receipt,
            'transaction' => $transaction,
            'status' => $status,
            'isVoided' => $isVoided,
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
}
