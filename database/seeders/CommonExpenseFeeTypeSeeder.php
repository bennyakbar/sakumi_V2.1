<?php

namespace Database\Seeders;

use App\Models\ExpenseFeeCategory;
use App\Models\ExpenseFeeSubcategory;
use App\Models\FeeType;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class CommonExpenseFeeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $taxonomy = [
            'A' => [
                'name' => 'Operasional Pendidikan',
                'subcategories' => [
                    'A1' => ['name' => 'SDM Pendidikan', 'fees' => [
                        'Honor Guru',
                        'Honor Guru Tidak Tetap / Eksternal',
                        'Honor Tenaga Kependidikan (TU, Admin)',
                        'Insentif Wali Kelas',
                        'Insentif Pembina Ekstrakurikuler',
                        'Lembur Pegawai',
                        'Tunjangan Transport',
                        'Tunjangan Makan',
                    ]],
                ],
            ],
            'B' => [
                'name' => 'Kegiatan Belajar Mengajar',
                'subcategories' => [
                    'B1' => ['name' => 'Operasional KBM', 'fees' => [
                        'Pengadaan Buku Pelajaran',
                        'Pengadaan Modul / LKS',
                        'ATK Kegiatan KBM',
                        'Pengadaan Media Pembelajaran',
                        'Fotokopi & Percetakan Materi',
                        'Biaya Praktikum',
                    ]],
                ],
            ],
            'C' => [
                'name' => 'Operasional Kantor & Umum',
                'subcategories' => [
                    'C1' => ['name' => 'Kantor Harian', 'fees' => [
                        'ATK Kantor',
                        'Kertas & Tinta Printer',
                        'Biaya Fotokopi',
                        'Biaya Internet',
                        'Biaya Telepon',
                        'Biaya Konsumsi Rapat',
                        'Biaya Kebersihan',
                    ]],
                ],
            ],
            'D' => [
                'name' => 'Utilitas (Rutin Bulanan)',
                'subcategories' => [
                    'D1' => ['name' => 'Tagihan Utilitas', 'fees' => [
                        'Listrik',
                        'Air',
                        'Internet',
                        'Telepon',
                        'Gas',
                    ]],
                ],
            ],
            'E' => [
                'name' => 'Sarana & Prasarana',
                'subcategories' => [
                    'E1' => ['name' => 'Pemeliharaan Fasilitas', 'fees' => [
                        'Pemeliharaan Gedung',
                        'Perbaikan Ruang Kelas',
                        'Perbaikan Atap / Plafon',
                        'Perbaikan Listrik',
                        'Perbaikan AC / Kipas',
                        'Pembelian Peralatan Sekolah',
                        'Pembelian Meja & Kursi',
                    ]],
                ],
            ],
            'F' => [
                'name' => 'Kebersihan & Keamanan',
                'subcategories' => [
                    'F1' => ['name' => 'Operasional Kebersihan/Keamanan', 'fees' => [
                        'Honor Petugas Kebersihan',
                        'Honor Satpam',
                        'Alat Kebersihan',
                        'Bahan Pembersih',
                    ]],
                ],
            ],
            'G' => [
                'name' => 'Administrasi & Legal',
                'subcategories' => [
                    'G1' => ['name' => 'Dokumen & Legalitas', 'fees' => [
                        'Materai',
                        'Biaya Notaris',
                        'Biaya Legalisir',
                        'Biaya Perizinan',
                        'Biaya Pengurusan Dokumen',
                    ]],
                ],
            ],
            'H' => [
                'name' => 'Kesiswaan & Kegiatan Sekolah',
                'subcategories' => [
                    'H1' => ['name' => 'Event Kesiswaan', 'fees' => [
                        'Kegiatan Class Meeting',
                        'Kegiatan PHBI / PHBN',
                        'Study Tour',
                        'Lomba / Kompetisi',
                        'Konsumsi Kegiatan',
                        'Transport Kegiatan',
                    ]],
                ],
            ],
            'I' => [
                'name' => 'Keagamaan',
                'subcategories' => [
                    'I1' => ['name' => 'Kegiatan Keagamaan', 'fees' => [
                        'Honor Imam / Ustadz',
                        'Pengajian / Kajian',
                        'Kegiatan Ramadhan',
                        'Zakat / Infak / Sedekah Operasional',
                        'Pengadaan Al-Qur’an',
                    ]],
                ],
            ],
            'J' => [
                'name' => 'IT & Sistem Informasi',
                'subcategories' => [
                    'J1' => ['name' => 'Infrastruktur & Aplikasi', 'fees' => [
                        'Hosting / VPS',
                        'Domain',
                        'Perawatan Sistem',
                        'Pengembangan Aplikasi',
                        'Pembelian Komputer / Laptop',
                        'Perangkat Jaringan',
                    ]],
                ],
            ],
            'K' => [
                'name' => 'Sosial & Kesejahteraan',
                'subcategories' => [
                    'K1' => ['name' => 'Bantuan Sosial', 'fees' => [
                        'Santunan Siswa',
                        'Bantuan Pegawai',
                        'Dana Sosial',
                        'Bantuan Kesehatan',
                    ]],
                ],
            ],
            'L' => [
                'name' => 'Lain-lain',
                'subcategories' => [
                    'L1' => ['name' => 'Biaya Umum Lainnya', 'fees' => [
                        'Biaya Tak Terduga',
                        'Biaya Administrasi Bank',
                        'Biaya Transfer',
                    ]],
                ],
            ],
        ];

        $units = Unit::query()->get(['id', 'code']);
        foreach ($units as $unit) {
            $categoryOrder = 1;
            foreach ($taxonomy as $categoryCode => $categoryData) {
                $category = ExpenseFeeCategory::query()->withoutGlobalScope('unit')->updateOrCreate(
                    [
                        'unit_id' => $unit->id,
                        'code' => $categoryCode,
                    ],
                    [
                        'name' => $categoryData['name'],
                        'sort_order' => $categoryOrder++,
                        'is_active' => true,
                    ]
                );

                $subcategoryOrder = 1;
                foreach ($categoryData['subcategories'] as $subcategoryCode => $subcategoryData) {
                    $subcategory = ExpenseFeeSubcategory::query()->withoutGlobalScope('unit')->updateOrCreate(
                        [
                            'unit_id' => $unit->id,
                            'code' => $subcategoryCode,
                        ],
                        [
                            'expense_fee_category_id' => $category->id,
                            'name' => $subcategoryData['name'],
                            'sort_order' => $subcategoryOrder++,
                            'is_active' => true,
                        ]
                    );

                    foreach (array_values($subcategoryData['fees']) as $idx => $feeName) {
                        $baseFeeCode = sprintf('EXP-%s-%03d', $categoryCode, $idx + 1);
                        $feeCode = $baseFeeCode.'-'.$unit->code;

                        FeeType::query()->withoutGlobalScope('unit')
                            ->where('unit_id', $unit->id)
                            ->where('code', $baseFeeCode)
                            ->update(['code' => $feeCode]);

                        FeeType::query()->withoutGlobalScope('unit')->updateOrCreate(
                            [
                                'code' => $feeCode,
                            ],
                            [
                                'unit_id' => $unit->id,
                                'expense_fee_subcategory_id' => $subcategory->id,
                                'name' => $feeName,
                                'description' => $categoryData['name'].' / '.$subcategoryData['name'],
                                'is_monthly' => false,
                                'is_active' => true,
                            ]
                        );
                    }
                }
            }
        }
    }
}
