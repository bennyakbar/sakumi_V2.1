## ADDED Requirements

### Requirement: AR Outstanding Report
Sistem SHALL menyediakan laporan outstanding invoice dalam rentang tanggal dengan filter unit scope, kelas, kategori siswa, dan siswa, serta menampilkan total invoice, total settled, dan outstanding per baris serta total agregat.

#### Scenario: Filter AR outstanding per kelas dan kategori
- **WHEN** pengguna membuka laporan AR Outstanding dan memilih rentang tanggal, kelas, dan kategori
- **THEN** sistem menampilkan hanya invoice yang sesuai filter dengan kolom nomor invoice, siswa, kelas, kategori, jatuh tempo, total, settled, dan outstanding

#### Scenario: Export AR outstanding
- **WHEN** pengguna menekan tombol export pada laporan AR Outstanding
- **THEN** sistem menghasilkan file XLSX/CSV berisi dataset sesuai filter aktif

### Requirement: Collection Report by Payment Range
Sistem SHALL menyediakan laporan collection berdasarkan rentang tanggal pembayaran dengan filter metode bayar dan cashier, serta memisahkan sumber settlement dan transaksi income non-siswa.

#### Scenario: Filter collection by cashier
- **WHEN** pengguna memilih rentang tanggal dan cashier tertentu
- **THEN** sistem menampilkan total collection dan detail transaksi yang dibuat oleh cashier tersebut

#### Scenario: Filter collection by payment method
- **WHEN** pengguna memilih metode bayar tertentu (cash/transfer/qris)
- **THEN** sistem membatasi hasil ke transaksi dengan metode bayar yang dipilih

### Requirement: Student Statement Report
Sistem SHALL menyediakan student statement yang menggabungkan riwayat invoice dan settlement siswa dalam rentang tanggal, termasuk saldo awal, mutasi debit/kredit, dan saldo akhir per siswa.

#### Scenario: Lihat statement siswa dalam periode
- **WHEN** pengguna memilih satu siswa dan rentang tanggal
- **THEN** sistem menampilkan urutan kronologis invoice (debit) dan settlement (kredit) beserta saldo berjalan

#### Scenario: Export student statement
- **WHEN** pengguna melakukan export statement
- **THEN** sistem menghasilkan file yang memuat detail transaksi kronologis dan ringkasan saldo awal/akhir

### Requirement: Daily Cash Book Report
Sistem SHALL menyediakan buku kas harian yang menghitung saldo awal harian, total penerimaan, total pengeluaran, dan saldo akhir berdasarkan transaksi kas yang berstatus completed.

#### Scenario: Hitung saldo buku kas per hari
- **WHEN** pengguna memilih tanggal buku kas
- **THEN** sistem menghitung saldo awal dari saldo akhir hari sebelumnya lalu menampilkan penerimaan, pengeluaran, dan saldo akhir hari berjalan

#### Scenario: Tampilkan detail mutasi kas
- **WHEN** pengguna membuka detail buku kas harian
- **THEN** sistem menampilkan daftar mutasi kronologis dengan nomor dokumen, sumber transaksi, uraian, debit/kredit, dan saldo berjalan
