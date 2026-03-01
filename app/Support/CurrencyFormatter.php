<?php

namespace App\Support;

class CurrencyFormatter
{
    /**
     * Format a numeric value as Indonesian Rupiah.
     *
     * Positive: Rp 175.000,00
     * Negative: (Rp 3.230.202,92)
     * Zero/null: Rp 0,00
     */
    public static function formatRupiah(float|int|null $value, int $decimals = 2): string
    {
        $value = $value ?? 0;
        $isNegative = $value < 0;
        $formatted = number_format(abs($value), $decimals, ',', '.');

        return $isNegative
            ? "(Rp {$formatted})"
            : "Rp {$formatted}";
    }

    /**
     * PhpSpreadsheet number format string for Rupiah.
     *
     * Keeps cell values numeric; applies Rp prefix + parenthetical negatives at display level.
     */
    public static function excelRupiahFormat(): string
    {
        return '"Rp "#,##0.00_);[Red]("Rp "#,##0.00)';
    }
}
