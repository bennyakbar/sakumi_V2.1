<?php

namespace Tests\Unit;

use App\Support\CurrencyFormatter;
use PHPUnit\Framework\TestCase;

class CurrencyFormatterTest extends TestCase
{
    public function test_positive_value(): void
    {
        $this->assertSame('Rp 175.000,00', CurrencyFormatter::formatRupiah(175000));
    }

    public function test_negative_value(): void
    {
        $this->assertSame('(Rp 3.230.202,92)', CurrencyFormatter::formatRupiah(-3230202.92));
    }

    public function test_zero(): void
    {
        $this->assertSame('Rp 0,00', CurrencyFormatter::formatRupiah(0));
    }

    public function test_null(): void
    {
        $this->assertSame('Rp 0,00', CurrencyFormatter::formatRupiah(null));
    }

    public function test_fractional_value(): void
    {
        $this->assertSame('Rp 1.234,56', CurrencyFormatter::formatRupiah(1234.56));
    }

    public function test_custom_decimals(): void
    {
        $this->assertSame('Rp 175.000', CurrencyFormatter::formatRupiah(175000, 0));
    }

    public function test_negative_with_zero_decimals(): void
    {
        $this->assertSame('(Rp 50.000)', CurrencyFormatter::formatRupiah(-50000, 0));
    }

    public function test_global_helper_parity(): void
    {
        // The global helper should produce the same result
        $this->assertSame(
            CurrencyFormatter::formatRupiah(175000),
            formatRupiah(175000)
        );
    }

    public function test_excel_format_string(): void
    {
        $this->assertSame(
            '"Rp "#,##0.00_);[Red]("Rp "#,##0.00)',
            CurrencyFormatter::excelRupiahFormat()
        );
    }
}
