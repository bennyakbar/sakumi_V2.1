<?php

namespace App\Services;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReceiptService
{
    public function __construct(
        private readonly ReceiptVerificationService $verificationService,
        private readonly SchoolIdentityService $schoolIdentityService,
    ) {}

    public function generate(Transaction $transaction): string
    {
        $transaction->load('items.feeType', 'student.schoolClass', 'creator');
        $verificationCode = $this->verificationService->makeCode($transaction);
        $school = $this->schoolIdentityService->resolve($transaction->unit_id);

        $data = [
            'transaction' => $transaction,
            'footer_text' => getSetting('receipt_footer_text', ''),
            'show_logo' => getSetting('receipt_show_logo', true),
            'terbilang' => $this->terbilang($transaction->total_amount),
            'verification_code' => $verificationCode,
            'verification_url' => $this->verificationService->makeVerifyUrl($verificationCode),
            'watermark_text' => $this->verificationService->makeWatermark($verificationCode, 'ORIGINAL'),
            'cancelled' => false,
            ...$school,
        ];

        $pdf = Pdf::loadView('receipts.template', $data);
        $path = "receipts/{$transaction->transaction_number}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        $transaction->update(['receipt_path' => $path]);

        return $path;
    }

    public function generateCancelled(Transaction $transaction): string
    {
        $transaction->load('items.feeType', 'student.schoolClass', 'creator');
        $verificationCode = $this->verificationService->makeCode($transaction);
        $school = $this->schoolIdentityService->resolve($transaction->unit_id);

        $data = [
            'transaction' => $transaction,
            'footer_text' => getSetting('receipt_footer_text', ''),
            'show_logo' => getSetting('receipt_show_logo', true),
            'terbilang' => $this->terbilang($transaction->total_amount),
            'verification_code' => $verificationCode,
            'verification_url' => $this->verificationService->makeVerifyUrl($verificationCode),
            'watermark_text' => $this->verificationService->makeWatermark($verificationCode, 'CANCELLED'),
            'cancelled' => true,
            ...$school,
        ];

        $pdf = Pdf::loadView('receipts.template', $data);
        $path = "receipts/{$transaction->transaction_number}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    public function terbilang(float $amount): string
    {
        $amount = abs((int) $amount);
        $words = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

        if ($amount < 12) {
            return $words[$amount];
        }

        if ($amount < 20) {
            return $this->terbilang($amount - 10) . ' belas';
        }

        if ($amount < 100) {
            return $this->terbilang(intdiv($amount, 10)) . ' puluh' .
                ($amount % 10 ? ' ' . $this->terbilang($amount % 10) : '');
        }

        if ($amount < 200) {
            return 'seratus' . ($amount - 100 ? ' ' . $this->terbilang($amount - 100) : '');
        }

        if ($amount < 1000) {
            return $this->terbilang(intdiv($amount, 100)) . ' ratus' .
                ($amount % 100 ? ' ' . $this->terbilang($amount % 100) : '');
        }

        if ($amount < 2000) {
            return 'seribu' . ($amount - 1000 ? ' ' . $this->terbilang($amount - 1000) : '');
        }

        if ($amount < 1_000_000) {
            return $this->terbilang(intdiv($amount, 1000)) . ' ribu' .
                ($amount % 1000 ? ' ' . $this->terbilang($amount % 1000) : '');
        }

        if ($amount < 1_000_000_000) {
            return $this->terbilang(intdiv($amount, 1_000_000)) . ' juta' .
                ($amount % 1_000_000 ? ' ' . $this->terbilang($amount % 1_000_000) : '');
        }

        return $this->terbilang(intdiv($amount, 1_000_000_000)) . ' milyar' .
            ($amount % 1_000_000_000 ? ' ' . $this->terbilang($amount % 1_000_000_000) : '');
    }
}
