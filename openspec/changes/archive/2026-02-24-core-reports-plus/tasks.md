## 1. Report Endpoints and Permissions

- [x] 1.1 Tambahkan permission dan middleware binding untuk AR outstanding, collection, student statement, dan cash book.
- [x] 1.2 Tambahkan route GET dan route export untuk seluruh laporan baru.

## 2. Query and Service Layer

- [x] 2.1 Implementasikan query AR outstanding dengan filter kelas/kategori/siswa/rentang tanggal.
- [x] 2.2 Implementasikan query collection report dengan filter payment method dan cashier.
- [x] 2.3 Implementasikan query student statement dengan running balance per siswa.
- [x] 2.4 Implementasikan query cash book dengan opening/closing balance harian.

## 3. UI and Export

- [x] 3.1 Buat Blade views untuk keempat laporan di `resources/views/reports/`.
- [x] 3.2 Tambahkan aksi export XLSX/CSV dan class export yang relevan.

## 4. Testing and Validation

- [x] 4.1 Tambahkan feature tests untuk filter, total agregat, dan otorisasi akses laporan.
- [x] 4.2 Tambahkan validasi performa dasar (pagination/default date range) pada dataset menengah.
