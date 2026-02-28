<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\Settlement;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ControlledReceiptService
{
    public function __construct(
        private readonly ReceiptVerificationService $verificationService,
    ) {}

    public function issue(Transaction $transaction): Receipt
    {
        $issuedAt = $transaction->created_at ?? now();

        return DB::transaction(function () use ($transaction, $issuedAt): Receipt {
            $existing = Receipt::query()
                ->where('transaction_id', $transaction->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $referenceId = 'TXN-' . (string) $transaction->id;
            $verificationCode = $this->verificationService->makeDeterministicCode(
                referenceId: $referenceId,
                amount: (float) $transaction->total_amount,
                issuedAt: $issuedAt,
            );

            return Receipt::query()->create([
                'transaction_id' => $transaction->id,
                'invoice_id' => null,
                'issued_at' => $issuedAt,
                'verification_code' => $verificationCode,
                'print_count' => 0,
            ]);
        });
    }

    public function registerPrint(
        Transaction $transaction,
        User $user,
        ?string $reason,
        ?string $ipAddress,
        ?string $device
    ): Receipt {
        return DB::transaction(function () use ($transaction, $user, $reason, $ipAddress, $device): Receipt {
            $receipt = $this->issue($transaction);
            $receipt = Receipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            $isReprint = $receipt->print_count > 0;
            $isReprintAuthority = $user->hasAnyRole(['bendahara', 'super_admin', 'admin_tu_mi', 'admin_tu_ra', 'admin_tu_dta', 'admin_tu']);
            $isCashier = $user->hasRole('cashier');

            if (! $isReprintAuthority && ! $isCashier) {
                throw new AuthorizationException('Only cashier or bendahara/admin can print receipts.');
            }

            if ($isReprint && ! $isReprintAuthority) {
                throw new AuthorizationException('Only bendahara/admin can reprint receipts.');
            }

            if ($isReprint && blank($reason)) {
                throw ValidationException::withMessages([
                    'reason' => 'Reprint reason is required.',
                ]);
            }

            $now = now();
            $receipt->print_count = $receipt->print_count + 1;
            $receipt->printed_at = $now;
            $receipt->save();

            $receipt->printLogs()->create([
                'user_id' => $user->id,
                'printed_at' => $now,
                'ip_address' => $ipAddress,
                'device' => $device,
                'reason' => $isReprint ? trim((string) $reason) : null,
            ]);

            return $receipt->fresh();
        });
    }

    public function issueForSettlement(Settlement $settlement): Receipt
    {
        $issuedAt = $settlement->created_at ?? now();

        return DB::transaction(function () use ($settlement, $issuedAt): Receipt {
            $existing = Receipt::query()
                ->where('settlement_id', $settlement->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $referenceId = 'STL-' . (string) $settlement->id;
            $verificationCode = $this->verificationService->makeDeterministicCode(
                referenceId: $referenceId,
                amount: (float) $settlement->total_amount,
                issuedAt: $issuedAt,
            );

            return Receipt::query()->create([
                'transaction_id' => null,
                'invoice_id' => null,
                'settlement_id' => $settlement->id,
                'issued_at' => $issuedAt,
                'verification_code' => $verificationCode,
                'print_count' => 0,
            ]);
        });
    }

    public function registerPrintForSettlement(
        Settlement $settlement,
        User $user,
        ?string $reason,
        ?string $ipAddress,
        ?string $device
    ): Receipt {
        return DB::transaction(function () use ($settlement, $user, $reason, $ipAddress, $device): Receipt {
            $receipt = $this->issueForSettlement($settlement);
            $receipt = Receipt::query()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            $isReprint = $receipt->print_count > 0;
            $isReprintAuthority = $user->hasAnyRole(['bendahara', 'super_admin', 'admin_tu_mi', 'admin_tu_ra', 'admin_tu_dta', 'admin_tu']);
            $isCashier = $user->hasRole('cashier');

            if (! $isReprintAuthority && ! $isCashier) {
                throw new AuthorizationException('Only cashier or bendahara/admin can print settlement receipts.');
            }

            if ($isReprint && ! $isReprintAuthority) {
                throw new AuthorizationException('Only bendahara/admin can reprint settlement receipts.');
            }

            if ($isReprint && blank($reason)) {
                throw ValidationException::withMessages([
                    'reason' => 'Reprint reason is required.',
                ]);
            }

            $now = now();
            $receipt->print_count = $receipt->print_count + 1;
            $receipt->printed_at = $now;
            $receipt->save();

            $receipt->printLogs()->create([
                'user_id' => $user->id,
                'printed_at' => $now,
                'ip_address' => $ipAddress,
                'device' => $device,
                'reason' => $isReprint ? trim((string) $reason) : null,
            ]);

            return $receipt->fresh();
        });
    }

    public function printStatus(Receipt $receipt): string
    {
        if ($receipt->print_count <= 1) {
            return 'ORIGINAL';
        }

        return 'COPY - Reprint #' . ($receipt->print_count - 1);
    }
}
