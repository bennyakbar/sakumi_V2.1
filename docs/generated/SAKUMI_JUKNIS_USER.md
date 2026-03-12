# SAKUMI - Petunjuk Teknis (Juknis) Pengguna

## 1. Login dan Navigasi

### Login
- Akses halaman login melalui URL sistem
- Masukkan email dan password
- Sistem mengarahkan ke Dashboard sesuai role

### Dashboard
- Menampilkan ringkasan keuangan unit aktif
- Akses cepat ke fitur utama

### Pindah Unit
- Klik tombol **Switch Unit** di navigasi
- Pilih unit tujuan (MI / RA / DTA)
- Data yang ditampilkan otomatis berubah sesuai unit

### Bahasa
- Tersedia Bahasa Indonesia (`id`) dan English (`en`)
- Ganti melalui pengaturan locale

---

## 2. Master Data

### 2.1 Kelola Siswa

**Menu: Master > Students**

| Aksi | Tombol/Path | Keterangan |
|------|-------------|------------|
| Lihat daftar | `master/students` | Tabel siswa dengan search & filter |
| Tambah siswa | Tombol **Create** | Form input data lengkap |
| Edit siswa | Tombol **Edit** pada baris | Update data siswa |
| Hapus siswa | Tombol **Delete** pada baris | Soft delete |
| Import Excel | Tombol **Import** | Upload file Excel template |
| Export Excel | Tombol **Export** | Download data siswa |
| Download Template | Tombol **Template** | Template Excel untuk import |

**Field siswa:**
- NIS, NISN, Nama, Kelas, Kategori
- Jenis Kelamin, Tanggal/Tempat Lahir
- Nama Wali, No. HP, WhatsApp
- Alamat, Status, Tanggal Masuk

### 2.2 Kelola Kelas

**Menu: Master > Classes**

- CRUD kelas (contoh: Kelas 1, Kelas 2, Kelompok A)
- Kelas terhubung ke siswa dan fee matrix

### 2.3 Kelola Kategori Siswa

**Menu: Master > Categories**

- CRUD kategori (contoh: Reguler, Yatim, Dhuafa)
- Kategori menentukan tarif di fee matrix

### 2.4 Kelola Jenis Biaya (Fee Types)

**Menu: Master > Fee Types**

- Nama biaya (SPP, Uang Gedung, dll)
- Flag `is_monthly` — biaya berulang tiap bulan
- Flag `is_active` — aktif/non-aktif
- Kode biaya (untuk referensi internal)

### 2.5 Kelola Tarif (Fee Matrix)

**Menu: Master > Fee Matrix**

- Set tarif: Jenis Biaya × Kelas × Kategori = Jumlah
- Masa berlaku: `effective_from` s/d `effective_to`
- Kelas dan kategori bisa null (berlaku umum)
- Tarif lebih spesifik (ada kelas+kategori) didahulukan

### 2.6 Student Fee Mapping (Override Tarif Individu)

**Menu: Master > Students > [Siswa] > Fee Mappings**

- Override tarif fee matrix untuk siswa tertentu
- Contoh: siswa mendapat potongan SPP khusus
- Berlaku menggantikan fee matrix untuk fee type yang sama

---

## 3. Invoice (Penagihan)

### 3.1 Lihat Daftar Invoice

**Menu: Invoices**

- Filter: search (nomor/nama), status, period type, period identifier
- Pagination 15 per halaman

### 3.2 Generate Invoice Batch

**Menu: Invoices > Generate**

**Langkah:**
1. Pilih **Period Type**: Monthly / Annual
2. Isi **Period Identifier**: `2026-03` (untuk bulanan)
3. Set **Due Date**
4. Filter **Class** / **Category** (opsional)
5. Klik **Generate**

**Hasil:**
- Jumlah invoice yang dibuat (created)
- Jumlah yang di-skip (sudah ada)
- Error list (jika ada kegagalan per siswa)

### 3.3 Buat Invoice Manual

**Menu: Invoices > Create**

**Langkah:**
1. Pilih **Student** dari dropdown
2. Kewajiban unpaid yang belum di-invoice akan muncul otomatis
3. Centang kewajiban yang diinginkan
4. Set **Due Date** (minimal hari ini)
5. Tambah **Notes** (opsional)
6. Klik **Submit**

### 3.4 Detail Invoice

**Menu: Invoices > [Klik invoice]**

Menampilkan:
- Header: nomor, tanggal, jatuh tempo, status
- Data siswa
- Daftar item (fee type, jumlah, bulan/tahun)
- Riwayat pembayaran (settlement allocations)
- Tombol: Print, Cancel

### 3.5 Print Invoice

**Tombol Print pada detail invoice**

- Menampilkan invoice dalam format cetak
- Data sekolah, siswa, rincian tagihan

### 3.6 Cancel Invoice

**Tombol Cancel pada detail invoice**

- Isi alasan (opsional)
- Jika ada pembayaran: sistem otomatis void settlement terkait

---

## 4. Settlement (Pembayaran)

### 4.1 Lihat Daftar Settlement

**Menu: Settlements**

- Filter: search (nomor/nama), status
- Pagination 15 per halaman

### 4.2 Buat Settlement (Single)

**Menu: Settlements > Create**

**Langkah:**
1. Pilih **Student**
2. Invoice outstanding otomatis muncul
3. Pilih invoice (atau otomatis pilih yang pertama)
4. Isi: Payment Date, Method, Amount, Reference Number
5. Klik **Submit**

### 4.3 Buat Settlement (Multi Invoice)

**Menu: Settlements > Create (mode multi)**

**Langkah:**
1. Pilih **Student**
2. Semua invoice outstanding muncul
3. Isi **Payment Amount** (total uang diterima)
4. Alokasikan ke masing-masing invoice
5. Isi: Payment Date, Method, Reference Number
6. Klik **Submit**

### 4.4 Detail Settlement

**Menu: Settlements > [Klik settlement]**

Menampilkan:
- Header: nomor, tanggal, metode, status
- Data siswa
- Alokasi per invoice
- Informasi creator, voider, canceller
- Tombol: Print, Void, Cancel

### 4.5 Print Settlement Receipt

**Tombol Print pada detail settlement**

- Cetakan pertama: otomatis
- Cetak ulang: hanya bendahara/admin, wajib isi alasan

### 4.6 Void Settlement

**Tombol Void pada detail settlement**

- Wajib isi **Void Reason**
- Invoice terkait kembali ke status sebelumnya
- Kewajiban yang terkait kembali unpaid

---

## 5. Transaction (Kas Umum)

### 5.1 Lihat Daftar Transaction

**Menu: Transactions**

- Filter: search, status, payment method, class, date range
- Pagination 10 per halaman

### 5.2 Buat Transaction

**Menu: Transactions > Create**

**Langkah:**
1. Pilih **Type**: Income / Expense
2. Isi **Transaction Date**
3. Pilih **Payment Method**: cash / transfer / qris
4. Untuk Income: pilih Student (opsional)
5. Tambah item: Fee Type + Amount
6. Klik **Submit**

**Catatan penting:**
- Jika siswa punya invoice terbuka → redirect ke Settlement
- Fee type bulanan + student → wajib pakai Settlement
- Expense: butuh permission khusus

### 5.3 Cancel Transaction

**Tombol Cancel pada detail transaction**

- Isi alasan pembatalan
- Untuk income: kewajiban yang terhubung kembali unpaid
- Receipt di-generate ulang dengan watermark CANCELLED

---

## 6. Reports (Laporan)

### Menu: Reports

| Laporan | Path | Filter |
|---------|------|--------|
| Harian | `reports/daily` | Tanggal, scope |
| Bulanan | `reports/monthly` | Bulan/tahun, scope |
| Tunggakan | `reports/arrears` | Kelas, kategori |
| AR Outstanding | `reports/ar-outstanding` | Scope |
| Koleksi | `reports/collection` | Periode, scope |
| Student Statement | `reports/student-statement` | Siswa |
| Buku Kas | `reports/cash-book` | Periode, scope |

- Setiap laporan memiliki tombol **Export** untuk download Excel
- Super admin bisa lihat laporan **Consolidated** (semua unit)

---

## 7. Expense Management

### Menu: Expenses

| Aksi | Path | Keterangan |
|------|------|------------|
| Lihat daftar | `expenses/` | List pengeluaran |
| Buat pengeluaran | POST `expenses/` | Input form |
| Approve | `expenses/{id}/approve` | Persetujuan |
| Duplikat | `expenses/{id}/duplicate` | Copy pengeluaran |
| Budget Report | `expenses/budget-report` | Budget vs realisasi |
| Kelola Budget | POST `expenses/budgets` | Set anggaran |

---

## 8. Verifikasi Kuitansi (Publik)

- URL verifikasi tercetak pada setiap kuitansi
- Akses tanpa login
- Menampilkan status keaslian kuitansi
