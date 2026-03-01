<?php

namespace App\Services;

use App\Models\Settlement;
use App\Models\Transaction;
use Carbon\CarbonInterface;

class ReceiptVerificationService
{
    public function makeDeterministicCode(string $referenceId, float $amount, CarbonInterface $issuedAt): string
    {
        $payload = implode('|', [
            $referenceId,
            number_format($amount, 2, '.', ''),
            $issuedAt->format('Y-m-d H:i:s'),
        ]);

        $hmacKey = (string) (config('sakumi.receipt_hmac_key') ?: config('app.key', 'sakumi-default-key'));
        $raw = hash_hmac('sha256', $payload, $hmacKey);

        return strtoupper(substr($raw, 0, 16));
    }

    public function makeCode(Transaction $transaction): string
    {
        $issuedAt = $transaction->created_at ?? now();

        return $this->makeDeterministicCode(
            referenceId: 'TXN-' . (string) $transaction->id,
            amount: (float) $transaction->total_amount,
            issuedAt: $issuedAt,
        );
    }

    public function makeSettlementCode(Settlement $settlement): string
    {
        return $this->makeDeterministicCode(
            referenceId: 'STL-' . (string) $settlement->id,
            amount: (float) $settlement->total_amount,
            issuedAt: $settlement->created_at ?? now(),
        );
    }

    public function makeWatermark(string $verificationCode, string $printStatus): string
    {
        return sprintf(
            '%s • %s',
            $printStatus,
            $verificationCode,
        );
    }

    public function makeLegacyWatermark(Transaction $transaction): string
    {
        return sprintf(
            '%s • %s • %s',
            __('message.watermark_original'),
            $this->makeCode($transaction),
            now()->format('Ymd-His')
        );
    }

    public function makeVerifyUrl(string $code): string
    {
        return route('receipts.verify.public', ['code' => $code]);
    }

    public function isValid(string $expectedCode, ?string $code): bool
    {
        if (! $code) {
            return false;
        }

        return hash_equals($expectedCode, strtoupper(trim($code)));
    }
}
