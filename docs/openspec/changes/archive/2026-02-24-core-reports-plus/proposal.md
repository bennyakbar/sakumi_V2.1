## Why

Laporan operasional bendahara saat ini belum cukup untuk proses penagihan dan kas harian sekolah. Kesenjangan ini menghambat monitoring tunggakan per siswa, evaluasi kolektor/cashier, dan penyusunan buku kas harian.

## What Changes

- Menambah paket laporan lanjutan: AR Outstanding, Collection Report, Student Statement, dan Cash Book harian.
- Menambahkan filter laporan per periode, kelas, kategori, metode pembayaran, cashier, dan siswa.
- Menyediakan export untuk laporan (XLSX/CSV) mengikuti pola laporan yang sudah ada.
- Menambah kontrol akses RBAC untuk laporan baru.

## Capabilities

### New Capabilities
- `advanced-financial-reports`: Pelaporan lanjutan piutang, koleksi pembayaran, mutasi siswa, dan buku kas harian.

### Modified Capabilities
- `core-reports`: Menambahkan endpoint, filter, dan tampilan laporan baru di modul report yang sudah ada.

## Impact

- Affected code: `app/Http/Controllers/Report/ReportController.php`, service/report query builder baru, `resources/views/reports/*`, `routes/web.php`, policy/permission seeder.
- Data dependencies: `invoices`, `settlements`, `settlement_allocations`, `transactions`, `students`, `classes`, `student_categories`, `users`.
- Non-functional: query agregasi dan pagination untuk menghindari beban tinggi pada dataset besar.
