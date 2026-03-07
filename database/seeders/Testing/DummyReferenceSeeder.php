<?php

namespace Database\Seeders\Testing;

use App\Models\Account;
use App\Models\Category;
use App\Models\FeeMatrix;
use App\Models\FeeType;
use App\Models\SchoolClass;
use App\Models\StudentCategory;

class DummyReferenceSeeder extends TestingSeeder
{
    public function run(): void
    {
        $this->ensureTestingEnvironment();

        $classes = [
            ['name' => 'A 1', 'level' => 1, 'academic_year' => '2025/2026', 'is_active' => true],
            ['name' => 'B 1', 'level' => 1, 'academic_year' => '2025/2026', 'is_active' => true],
            ['name' => 'A 2', 'level' => 2, 'academic_year' => '2025/2026', 'is_active' => true],
            ['name' => 'A 3', 'level' => 3, 'academic_year' => '2025/2026', 'is_active' => true],
            ['name' => 'A 4', 'level' => 4, 'academic_year' => '2025/2026', 'is_active' => true],
            ['name' => 'A 5', 'level' => 5, 'academic_year' => '2025/2026', 'is_active' => true],
            ['name' => 'A 6', 'level' => 6, 'academic_year' => '2025/2026', 'is_active' => true],
        ];

        foreach ($classes as $item) {
            SchoolClass::query()->firstOrCreate([
                'name' => $item['name'],
                'academic_year' => $item['academic_year'],
            ], $item);
        }

        $studentCategories = [
            ['code' => 'REG', 'name' => 'Reguler', 'discount_percentage' => 0],
            ['code' => 'YTM', 'name' => 'Yatim', 'discount_percentage' => 50],
            ['code' => 'DHF', 'name' => 'Dhuafa', 'discount_percentage' => 75],
            ['code' => 'PRS', 'name' => 'Prestasi', 'discount_percentage' => 30],
        ];

        foreach ($studentCategories as $item) {
            StudentCategory::query()->firstOrCreate([
                'code' => $item['code'],
            ], [
                'name' => $item['name'],
                'discount_percentage' => $item['discount_percentage'],
            ]);
        }

        $accounts = [
            ['code' => 'ACC-10001', 'name' => 'Kas Utama', 'type' => 'asset'],
            ['code' => 'ACC-10002', 'name' => 'Bank BSI', 'type' => 'asset'],
            ['code' => 'ACC-10003', 'name' => 'Piutang SPP', 'type' => 'asset'],
            ['code' => 'ACC-20001', 'name' => 'Titipan Orang Tua', 'type' => 'liability'],
            ['code' => 'ACC-30001', 'name' => 'Modal Yayasan', 'type' => 'equity'],
            ['code' => 'ACC-40001', 'name' => 'Pendapatan SPP', 'type' => 'income'],
            ['code' => 'ACC-40002', 'name' => 'Pendapatan Daftar Ulang', 'type' => 'income'],
            ['code' => 'ACC-40003', 'name' => 'Pendapatan Donasi', 'type' => 'income'],
            ['code' => 'ACC-50001', 'name' => 'Beban Listrik', 'type' => 'expense'],
            ['code' => 'ACC-50002', 'name' => 'Beban Air', 'type' => 'expense'],
            ['code' => 'ACC-50003', 'name' => 'Beban ATK', 'type' => 'expense'],
            ['code' => 'ACC-50004', 'name' => 'Beban Internet', 'type' => 'expense'],
        ];

        foreach ($accounts as $item) {
            Account::query()->updateOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'is_active' => true,
                ]
            );
        }

        $categories = [
            ['code' => 'INC-001', 'name' => 'SPP Bulanan', 'type' => 'income'],
            ['code' => 'INC-002', 'name' => 'Daftar Ulang', 'type' => 'income'],
            ['code' => 'INC-003', 'name' => 'Uang Kegiatan', 'type' => 'income'],
            ['code' => 'INC-004', 'name' => 'Donasi', 'type' => 'income'],
            ['code' => 'INC-005', 'name' => 'Dana BOS', 'type' => 'income'],
            ['code' => 'EXP-001', 'name' => 'Pembelian ATK', 'type' => 'expense'],
            ['code' => 'EXP-002', 'name' => 'Pembayaran Listrik', 'type' => 'expense'],
            ['code' => 'EXP-003', 'name' => 'Pembayaran Air', 'type' => 'expense'],
            ['code' => 'EXP-004', 'name' => 'Biaya Internet', 'type' => 'expense'],
            ['code' => 'EXP-005', 'name' => 'Perawatan Gedung', 'type' => 'expense'],
            ['code' => 'EXP-006', 'name' => 'Honor Guru', 'type' => 'expense'],
            ['code' => 'EXP-007', 'name' => 'Kegiatan Siswa', 'type' => 'expense'],
        ];

        foreach ($categories as $item) {
            Category::query()->updateOrCreate(
                ['code' => $item['code']],
                [
                    'name' => $item['name'],
                    'type' => $item['type'],
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }

        // ── Fee Types ──
        $feeTypes = [
            ['code' => 'SPP', 'name' => 'SPP Bulanan', 'description' => 'Sumbangan Pembinaan Pendidikan', 'is_monthly' => true, 'is_active' => true],
            ['code' => 'DU', 'name' => 'Daftar Ulang', 'description' => 'Biaya daftar ulang tahunan', 'is_monthly' => false, 'is_active' => true],
            ['code' => 'KGT', 'name' => 'Uang Kegiatan', 'description' => 'Biaya kegiatan ekstrakurikuler', 'is_monthly' => true, 'is_active' => true],
            ['code' => 'SRG', 'name' => 'Seragam', 'description' => 'Biaya seragam sekolah', 'is_monthly' => false, 'is_active' => true],
            ['code' => 'BKU', 'name' => 'Buku Paket', 'description' => 'Biaya buku paket pelajaran', 'is_monthly' => false, 'is_active' => true],
        ];

        foreach ($feeTypes as $item) {
            FeeType::query()->firstOrCreate(
                ['code' => $item['code']],
                $item
            );
        }

        // ── Fee Matrix (maps fee types to classes/categories with amounts) ──
        $sppId = FeeType::where('code', 'SPP')->value('id');
        $kgtId = FeeType::where('code', 'KGT')->value('id');
        $allClassIds = SchoolClass::pluck('id')->all();
        $effectiveFrom = '2025-07-01';

        // SPP: all classes, null category (applies to all), tiered by level
        $sppAmounts = [
            1 => 150000, // level 1
            2 => 175000, // level 2
            3 => 200000,
            4 => 200000,
            5 => 225000,
            6 => 225000,
        ];

        foreach (SchoolClass::all() as $class) {
            $amount = $sppAmounts[$class->level] ?? 200000;

            FeeMatrix::query()->firstOrCreate(
                ['fee_type_id' => $sppId, 'class_id' => $class->id, 'category_id' => null],
                [
                    'amount' => $amount,
                    'effective_from' => $effectiveFrom,
                    'effective_to' => null,
                    'is_active' => true,
                    'notes' => "SPP {$class->name} level {$class->level}",
                ]
            );
        }

        // Kegiatan: flat 25000/month for all classes
        foreach ($allClassIds as $classId) {
            FeeMatrix::query()->firstOrCreate(
                ['fee_type_id' => $kgtId, 'class_id' => $classId, 'category_id' => null],
                [
                    'amount' => 25000,
                    'effective_from' => $effectiveFrom,
                    'effective_to' => null,
                    'is_active' => true,
                    'notes' => 'Uang kegiatan bulanan',
                ]
            );
        }
    }
}
