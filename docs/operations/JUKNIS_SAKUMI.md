# JUKNIS SAKUMI
## Petunjuk Teknis Penggunaan Sistem — SAKUMI (Sistem Keuangan Sekolah)

**Versi:** 2.0  
**Tanggal:** 26 Februari 2026  
**Jenis Dokumen:** Petunjuk Teknis (JUKNIS)

---

## PENDAHULUAN

Petunjuk Teknis (JUKNIS) ini menjelaskan cara teknis penggunaan setiap modul dalam aplikasi SAKUMI secara langkah demi langkah. JUKNIS ini merupakan panduan operasional yang merujuk pada SOP dan harus digunakan bersamaan dengannya.

**Target Pembaca:** Seluruh pengguna SAKUMI sesuai role masing-masing.

---

## MODUL 1: LOGIN DAN PROFIL

### 1.1 Cara Login
1. Buka browser, akses URL SAKUMI yang ditetapkan oleh administrator.
2. Masukkan **Email** dan **Password** yang telah diberikan.
3. Klik tombol **Login**.
4. Jika berhasil, Anda akan diarahkan ke halaman **Dashboard**.
5. Jika gagal login 5 kali, sistem akan mengunci akun sementara 15 menit.

### 1.2 Cara Ganti Password / Edit Profil
1. Klik nama pengguna di pojok kanan atas → pilih **Profile**.
2. Untuk ganti password: isi **Current Password**, **New Password**, dan **Confirm Password**.
3. Klik **Save**.
4. Password baru harus memenuhi ketentuan: minimal 8 karakter, mengandung huruf besar, huruf kecil, angka, dan karakter spesial (`@$!%*?&`).

### 1.3 Cara Logout
1. Klik nama pengguna di pojok kanan atas → pilih **Logout**.
2. Atau tunggu 2 jam tidak aktif — sistem akan logout otomatis.

---

## MODUL 2: DASHBOARD

**Akses:** Semua role (sesuai izin masing-masing)

### 2.1 Cara Membaca Dashboard
Dashboard menampilkan ringkasan keuangan unit Anda:
- **Total Pembayaran Hari Ini** — total transaksi yang berhasil hari ini
- **Total Outstanding** — total tagihan yang belum terbayar
- **Jumlah Invoice Overdue** — tagihan yang sudah melewati jatuh tempo
- **Invoice Terbit Bulan Ini** — total invoice yang sudah dibuat bulan ini

### 2.2 Cara Filter Dashboard (jika tersedia)
- Gunakan dropdown **Unit** (untuk Super Admin/Yayasan yang akses multi-unit)
- Gunakan filter **Tanggal** untuk melihat ringkasan periode tertentu

---

## MODUL 3: MASTER DATA

### 3.1 Manajemen Kelas
**Akses:** Admin TU, Operator TU, Super Admin

#### Cara Menambah Kelas Baru
1. Klik menu **Master → Classes**.
2. Klik tombol **+ New Class**.
3. Isi:
   - **Class Name**: nama kelas (contoh: "1A", "2B")
   - **Level**: tingkat kelas (contoh: 1, 2, 3, dst.)
   - **Is Active**: centang untuk kelas aktif
4. Klik **Save**.

#### Cara Edit Kelas
1. Di daftar kelas, klik ikon **Edit (pensil)** pada baris kelas yang bersangkutan.
2. Ubah data yang diperlukan.
3. Klik **Update**.

#### Cara Nonaktifkan Kelas
1. Klik **Edit** pada kelas yang ingin dinonaktifkan.
2. Hilangkan centang **Is Active**.
3. Klik **Update**.
> ⚠ Kelas dinonaktifkan tetap tersimpan untuk keperluan historis, tidak dihapus.

---

### 3.2 Manajemen Kategori Siswa
**Akses:** Admin TU, Operator TU, Super Admin

#### Cara Menambah Kategori
1. Klik menu **Master → Categories**.
2. Klik **+ New Category**.
3. Isi **Category Name** (contoh: "Reguler", "Beasiswa", "KIP").
4. Klik **Save**.

> **Catatan:** Kategori digunakan bersama kelas untuk menentukan tarif dari Fee Matrix.

---

### 3.3 Manajemen Jenis Biaya (Fee Types)
**Akses:** Admin TU, Bendahara, Super Admin

#### Cara Menambah Jenis Biaya
1. Klik menu **Master → Fee Types**.
2. Klik **+ New Fee Type**.
3. Isi:
   - **Name**: nama jenis biaya (contoh: "SPP Bulanan", "Uang Gedung")
   - **Description**: keterangan singkat
   - **Is Monthly**: centang jika biaya ini ditagihkan per bulan (untuk generate kewajiban otomatis)
4. Klik **Save**.

---

### 3.4 Manajemen Fee Matrix (Tarif Biaya)
**Akses:** Admin TU, Bendahara, Super Admin

Fee Matrix adalah tabel tarif yang menentukan berapa nominal tagihan berdasarkan kombinasi jenis biaya, kelas, dan kategori siswa.

#### Cara Menambah Tarif Baru
1. Klik menu **Master → Fee Matrix**.
2. Klik **+ New Fee Matrix**.
3. Isi:
   - **Fee Type**: pilih jenis biaya
   - **Class**: pilih kelas (kosongkan = berlaku untuk semua kelas)
   - **Category**: pilih kategori (kosongkan = berlaku untuk semua kategori)
   - **Amount**: nominal tarif
   - **Effective From**: tanggal mulai berlaku tarif ini (WAJIB diisi)
   - **Effective To**: tanggal berakhir tarif (kosongkan = berlaku selamanya)
   - **Is Active**: centang untuk mengaktifkan
4. Klik **Save**.

> **Aturan prioritas tarif:**  
> Tarif dengan kelas+kategori spesifik > tarif dengan kelas saja > tarif dengan kategori saja > tarif umum (tanpa kelas dan kategori).

#### Cara Edit Tarif
1. Klik **Edit** pada baris tarif yang bersangkutan.
2. Jika mengubah nominal, pastikan mengisi **Effective From** dengan tanggal mulai tarif baru.
3. Klik **Update**.

---

### 3.5 Manajemen Data Siswa
**Akses:** Admin TU, Operator TU, Super Admin

#### Cara Menambah Siswa Baru (Satu per Satu)
1. Klik menu **Master → Students**.
2. Klik **+ New Student**.
3. Isi data siswa:
   - **NIS**: Nomor Induk Siswa (unik)
   - **NISN**: Nomor Induk Siswa Nasional
   - **Name**: nama lengkap siswa
   - **Class**: pilih kelas
   - **Category**: pilih kategori
   - **Status**: pilih "Active"
   - **Parent WhatsApp**: nomor WA wali (format: 628XXXXXXXXX)
   - **Date of Birth**: tanggal lahir
4. Klik **Save**.

#### Cara Import Siswa Massal (CSV)
1. Klik menu **Master → Students**.
2. Klik tombol **Import**.
3. Klik **Download Template** untuk mendapatkan template CSV.
4. Buka template di Excel/Google Sheets, isi data siswa.  
   Kolom yang wajib diisi: `nis`, `name`, `class_id`, `category_id`, `status`.
5. Simpan sebagai format `.csv`.
6. Upload file CSV di halaman Import.
7. Sistem akan menampilkan preview hasil validasi.
8. Jika ada baris error, perbaiki file dan upload ulang.
9. Klik **Confirm Import** jika semua data valid.

#### Cara Export Data Siswa
1. Klik **Export** di halaman daftar siswa.
2. File Excel akan terunduh otomatis.

#### Cara Edit/Nonaktifkan Siswa
1. Di daftar siswa, klik **Edit** pada siswa yang bersangkutan.
2. Ubah data yang diperlukan; untuk meluluskan/menonaktifkan: ubah **Status** menjadi "Graduated" atau "Inactive".
3. Klik **Update**.

#### Cara Mengelola Pemetaan Biaya Siswa (Student Fee Mappings)
1. Buka halaman detail siswa (klik nama siswa).
2. Di bagian **Fee Mappings**, klik **+ Add Mapping**.
3. Pilih jenis biaya dan nominal override jika tarif siswa ini berbeda dari fee matrix umum.
4. Klik **Save**.

---

## MODUL 4: INVOICE

**Akses:** Admin TU, Bendahara, Operator TU, Super Admin  
**View only:** Kepala Sekolah, Auditor

### 4.1 Cara Membuat Invoice Manual
1. Klik menu **Invoices → Create**.
2. Pilih **Student** (cari berdasarkan NIS/nama).
3. Pilih **Fee Type** dan **Period** (bulan/tahun).
4. Sistem menampilkan nominal otomatis dari fee matrix. Sesuaikan jika perlu.
5. Isi **Due Date** (tanggal jatuh tempo).
6. Klik **Save Invoice**.

### 4.2 Cara Generate Invoice Massal
1. Klik menu **Invoices → Generate**.
2. Pilih:
   - **Period**: bulan dan tahun invoice
   - **Fee Type**: jenis biaya yang akan digenerate
   - **Class** (opsional): filter per kelas
3. Sistem menampilkan preview: berapa siswa yang akan dikenakan invoice dan total nominal.
4. Verifikasi preview — pastikan jumlah siswa dan nominal sudah benar.
5. Klik **Run Generation**.
6. Sistem akan membuat invoice untuk seluruh siswa aktif yang memenuhi kriteria.

> **Penting:** Generate invoice bersifat idempoten — jika dijalankan dua kali di periode yang sama, sistem hanya membuat invoice yang belum ada (tidak duplikat).

### 4.3 Cara Melihat Detail Invoice
1. Klik menu **Invoices**.
2. Gunakan filter (status, kelas, periode, siswa) untuk mempersempit pencarian.
3. Klik nomor invoice untuk melihat detail:
   - Invoice Total: nominal tagihan awal
   - Already Paid: total yang sudah dibayar
   - Outstanding: sisa tagihan
   - Daftar settlement terkait

### 4.4 Cara Cetak Invoice
1. Dari halaman detail invoice, klik **Print Invoice**.
2. Browser akan membuka halaman cetak PDF.
3. Gunakan fungsi cetak browser (Ctrl+P) atau tombol cetak yang tersedia.

### 4.5 Cara Membatalkan Invoice
**Akses:** Admin TU, Bendahara, Super Admin (jika invoice belum ada pembayaran)
1. Dari halaman detail invoice, klik **Cancel Invoice**.
2. Isi alasan pembatalan.
3. Klik **Confirm Cancel**.
> ⚠ Invoice yang sudah memiliki settlement tidak dapat dibatalkan langsung — void settlement terlebih dahulu.

---

## MODUL 5: TRANSAKSI (TRANSACTIONS)

**Akses:** Kasir, Admin TU, Operator TU, Bendahara, Super Admin  
**View only:** Kepala Sekolah, Auditor

### 5.1 Cara Mencatat Transaksi Pembayaran
1. Klik menu **Transactions → Create**.
2. Isi:
   - **Type**: Income (penerimaan)
   - **Student**: pilih siswa (cari NIS/nama)
   - **Date**: tanggal pembayaran
   - **Description**: keterangan transaksi
   - **Items**: tambahkan item pembayaran (jenis biaya dan nominal)
   - **Payment Method**: tunai / transfer / QRIS
3. Verifikasi total nominal.
4. Klik **Save Transaction**.
5. Sistem akan otomatis:
   - Generate nomor transaksi (format: NF-YYYY-NNNNNN)
   - Generate kwitansi PDF
   - Memperbarui status kewajiban (obligations) siswa

### 5.2 Cara Melihat Detail Transaksi
1. Klik menu **Transactions**.
2. Gunakan filter (tanggal, tipe, siswa, metode) untuk mencari transaksi.
3. Klik nomor transaksi untuk melihat detail.

### 5.3 Cara Membatalkan Transaksi
**Akses:** Admin TU, Bendahara, Super Admin
1. Buka halaman detail transaksi.
2. Klik **Cancel Transaction**.
3. Isi alasan pembatalan yang jelas.
4. Klik **Confirm**.
5. Sistem akan mengubah status transaksi menjadi "Cancelled" dan memperbarui outstanding invoice.

> ⚠ Transaksi yang sudah completed tidak bisa diedit — hanya bisa dibatalkan lalu dibuat ulang.

---

## MODUL 6: KWITANSI (RECEIPTS)

**Akses:** Admin TU, Bendahara, Kasir, Operator TU, Super Admin  
**View only:** Kepala Sekolah, Auditor

### 6.1 Cara Cetak Kwitansi (ORIGINAL)
1. Setelah transaksi berhasil disimpan, sistem otomatis menampilkan tombol **Print Receipt**.
2. Klik **Print Receipt**.
3. Halaman cetak kwitansi PDF terbuka di browser.
4. Gunakan Ctrl+P untuk mencetak.
5. Kwitansi pertama selalu berstatus **ORIGINAL**.

### 6.2 Cara Reprint Kwitansi (COPY)
**Akses:** Admin TU, Bendahara, Super Admin
1. Buka detail transaksi yang kwitansinya ingin dicetak ulang.
2. Klik **Reprint Receipt**.
3. Pilih alasan reprint dari dropdown:
   - Hilang
   - Rusak
   - Permintaan Orang Tua
   - Lainnya (isi keterangan)
4. Klik **Confirm Reprint**.
5. Halaman cetak terbuka. Kwitansi akan berstatus **COPY – Reprint #N**.
6. Audit log akan otomatis mencatat: nama user, waktu, alasan, dan nomor cetak.

### 6.3 Cara Verifikasi Kwitansi (Publik)
1. Scan QR code pada kwitansi, atau
2. Akses URL: `[url-sakumi]/verify-receipt/{kode-verifikasi}`.
3. Sistem akan menampilkan informasi kwitansi tanpa perlu login.

---

## MODUL 7: SETTLEMENT

**Akses:** Admin TU, Bendahara, Operator TU, Super Admin  
**View only:** Kepala Sekolah, Auditor

### 7.1 Cara Membuat Settlement
1. Klik menu **Settlements → Create**.
2. Pilih **Invoice** yang akan dialokasikan pembayarannya.
3. Isi data settlement:
   - **Payment Date**: tanggal diterima uang
   - **Amount**: nominal yang dibayarkan (≤ outstanding)
   - **Payment Method**: tunai / transfer / QRIS
   - **Notes**: keterangan tambahan
4. Sistem menampilkan: Invoice Total, Already Paid, Outstanding saat ini.
5. Klik **Save Settlement**.
6. Sistem memvalidasi: jika nominal > outstanding → ditolak.
7. Jika valid: outstanding invoice diperbarui otomatis.

### 7.2 Cara Melihat Detail Settlement
1. Klik menu **Settlements**.
2. Gunakan filter untuk mencari settlement.
3. Klik nomor settlement untuk melihat detail.

### 7.3 Cara Void Settlement
**Akses:** Bendahara, Super Admin
1. Buka halaman detail settlement.
2. Klik **Void Settlement**.
3. Isi alasan void.
4. Klik **Confirm**.
5. Sistem memperbarui outstanding invoice sesuai dengan settlement yang di-void.

---

## MODUL 8: LAPORAN (REPORTS)

**Akses:** Admin TU, Bendahara, Kepala Sekolah, Operator TU, Auditor, Super Admin

### 8.1 Daily Report (Laporan Harian)
1. Klik menu **Reports → Daily**.
2. Pilih **Tanggal** yang ingin dilihat (default: hari ini).
3. Laporan menampilkan:
   - Total penerimaan hari itu
   - Rincian per jenis biaya
   - Daftar transaksi
4. Klik **Export Excel** atau **Export PDF** untuk mengunduh.
5. Nama file: `UNITKODE_LaporanHarian_YYYYMMDD_ROLE.xlsx`

### 8.2 Monthly Report (Laporan Bulanan)
1. Klik menu **Reports → Monthly**.
2. Pilih **Bulan** dan **Tahun**.
3. Laporan menampilkan:
   - Total penerimaan per hari
   - Grafik tren harian
   - Ringkasan per jenis biaya
4. Export: **Export Excel** atau **Export PDF**.
5. Nama file: `UNITKODE_LaporanBulanan_YYYYMM_ROLE.xlsx`

### 8.3 Arrears Report (Laporan Tunggakan)
1. Klik menu **Reports → Arrears**.
2. Filter opsional: kelas, periode, status aging.
3. Laporan menampilkan:
   - Daftar siswa dengan tunggakan
   - Outstanding per siswa
   - Aging (hari sejak due date)
   - Bucket aging: 0-30, 31-60, 61-90, >90 hari
4. Export untuk tindak lanjut penagihan.

### 8.4 AR Outstanding Report
1. Klik menu **Reports → AR Outstanding**.
2. Filter berdasarkan kelas/tanggal.
3. Laporan menampilkan total piutang yang belum terbayar per siswa.
4. Export untuk rekonsiliasi.

### 8.5 Collection Report
1. Klik menu **Reports → Collection**.
2. Pilih periode.
3. Laporan menampilkan tingkat kolektibilitas: berapa % invoice sudah terbayar.

### 8.6 Student Statement
1. Klik menu **Reports → Student Statement**.
2. Pilih siswa (cari berdasarkan NIS/nama).
3. Tentukan **Periode Dari** – **Periode Sampai**.
4. Laporan menampilkan: seluruh invoice, pembayaran, dan saldo per siswa.
5. Berguna untuk konfirmasi kepada wali murid tentang posisi hutang.

### 8.7 Cash Book (Buku Kas)
1. Klik menu **Reports → Cash Book**.
2. Pilih rentang tanggal.
3. Laporan menampilkan: semua penerimaan dan pengeluaran berurutan secara kronologis.

---

## MODUL 9: PENGELUARAN (EXPENSES)

**Akses:** Admin TU, Bendahara, Super Admin (create/approve)  
**View only:** Kepala Sekolah, Operator TU, Auditor

### 9.1 Cara Mencatat Pengeluaran
1. Klik menu **Expenses**.
2. Klik **+ New Expense**.
3. Isi:
   - **Date**: tanggal pengeluaran
   - **Fee Type / Category**: jenis pengeluaran
   - **Amount**: nominal
   - **Description**: keterangan pengeluaran
   - **Attachment**: bukti pendukung (opsional)
4. Klik **Submit**.
5. Status expense: **Pending** (menunggu approval Bendahara).

### 9.2 Cara Menyetujui Pengeluaran
**Akses:** Bendahara, Super Admin
1. Buka daftar **Expenses**.
2. Cari expense dengan status **Pending**.
3. Klik nama expense untuk melihat detail.
4. Periksa kesesuaian: nominal, bukti, dan keterangan.
5. Klik **Approve** jika valid, atau tolak jika tidak sesuai.

### 9.3 Budget vs Realisasi
1. Klik menu **Expenses → Budget Report**.
2. Laporan menampilkan perbandingan anggaran vs realisasi pengeluaran per kategori.

---

## MODUL 10: REKONSILIASI BANK

**Akses:** Admin TU, Bendahara, Super Admin  
**View only:** Kepala Sekolah, Operator TU, Auditor

### 10.1 Cara Memulai Rekonsiliasi Bank
1. Klik menu **Bank Reconciliation**.
2. Klik **+ New Reconciliation Session**.
3. Isi:
   - **Period From**: tanggal awal periode rekonsiliasi
   - **Period To**: tanggal akhir
   - **Bank Account**: pilih rekening bank
4. Klik **Create Session**.

### 10.2 Cara Import Mutasi Bank
1. Di halaman sesi rekonsiliasi, klik **Import Bank Statement**.
2. Upload file mutasi bank (format CSV/Excel dari internet banking).
3. Sistem akan menampilkan daftar baris mutasi bank.

### 10.3 Cara Mencocokkan (Match) Transaksi
1. Di halaman sesi rekonsiliasi:
   - Sisi kiri: daftar mutasi bank
   - Sisi kanan: daftar transaksi sistem
2. Klik baris mutasi bank → klik transaksi sistem yang sesuai → klik **Match**.
3. Baris yang sudah dicocokkan akan berubah warna.
4. Ulangi untuk semua baris.
5. Jika ada baris di bank yang tidak ada di sistem: **Unmatched** — investigasi dan dokumentasikan.

### 10.4 Cara Menutup Sesi Rekonsiliasi
1. Setelah semua baris diproses, klik **Close Session**.
2. Sistem menyimpan ringkasan rekonsiliasi: total matched, unmatched, dan selisih.
3. Export laporan rekonsiliasi sebagai arsip.

---

## MODUL 11: MANAJEMEN PENGGUNA (USER MANAGEMENT)

**Akses:** Super Admin (create/edit/delete)  
**View only:** Admin TU, Bendahara, Kepala Sekolah, Operator TU

### 11.1 Cara Membuat Akun Pengguna Baru
1. Klik menu **Users → Create**.
2. Isi:
   - **Name**: nama lengkap
   - **Email**: email unik dan valid (digunakan untuk login)
   - **Password**: wajib memenuhi kebijakan password
   - **Role**: pilih role yang sesuai (Admin TU / Kasir / dst.)
   - **Unit**: pilih unit sekolah terkait
   - **Is Active**: centang untuk mengaktifkan
3. Klik **Save**.

### 11.2 Cara Edit / Nonaktifkan Pengguna
1. Klik menu **Users**.
2. Cari pengguna yang bersangkutan.
3. Klik **Edit**.
4. Ubah data atau hilangkan centang **Is Active** untuk menonaktifkan.
5. Klik **Update**.

### 11.3 Cara Reset Password Pengguna
1. Buka halaman detail pengguna.
2. Klik **Reset Password**.
3. Masukkan password baru.
4. Klik **Confirm**.
5. Informasikan password baru kepada pengguna secara langsung (jangan via chat umum).

### 11.4 Cara Export Daftar Pengguna
1. Di halaman Users, klik **Export**.
2. File Excel berisi seluruh daftar pengguna aktif akan terunduh.

---

## MODUL 12: PENGATURAN (SETTINGS)

**Akses:** Super Admin (edit)  
**View only:** Admin TU, Bendahara, Kepala Sekolah, dll.

### 12.1 Jenis Pengaturan yang Tersedia

| Kategori | Pengaturan |
|---|---|
| **Sekolah** | Nama sekolah, alamat, logo, nomor telepon |
| **Kwitansi** | Header kwitansi, tanda tangan, nomor rekening sekolah |
| **Notifikasi** | Template pesan WhatsApp, URL gateway |
| **Tunggakan** | Ambang bulan tunggakan sebelum notifikasi kirim |
| **Sistem** | Timezone, format tanggal |

### 12.2 Cara Mengubah Pengaturan
1. Klik menu **Settings**.
2. Ubah nilai pengaturan yang diinginkan.
3. Klik **Save Settings**.
> ⚠ Perubahan settings berlaku segera dan memengaruhi seluruh unit (jika multi-unit).

---

## MODUL 13: AUDIT LOG

**Akses:** Admin TU, Bendahara, Kepala Sekolah, Auditor, Super Admin

### 13.1 Cara Mengakses Audit Log
Audit log tersedia melalui menu atau embedded di halaman detail transaksi/user.

### 13.2 Informasi yang Tercatat
- Nama pengguna yang melakukan aksi
- Jenis aksi (buat, edit, hapus, login, logout, cetak)
- Waktu aksi
- Data sebelum dan sesudah perubahan (untuk edit)
- Modul yang terpengaruh

### 13.3 Cara Filter Audit Log
1. Gunakan filter: tanggal, pengguna, modul, jenis aksi.
2. Export hasil audit log untuk arsip atau investigasi.

---

## LAMPIRAN: KONVENSI PENAMAAN FILE EKSPOR

| Jenis Laporan | Format Nama File |
|---|---|
| Laporan Harian | `UNITKODE_LaporanHarian_YYYYMMDD_ROLE.xlsx` |
| Laporan Bulanan | `UNITKODE_LaporanBulanan_YYYYMM_ROLE.xlsx` |
| Laporan Tunggakan | `UNITKODE_Tunggakan_YYYYMMDD_ROLE.xlsx` |
| AR Outstanding | `UNITKODE_AROutstanding_YYYYMMDD_ROLE.xlsx` |
| Collection Report | `UNITKODE_Collection_YYYYMM_ROLE.xlsx` |
| Student Statement | `UNITKODE_StatementSiswa_YYYYMMDD_ROLE.pdf` |
| Cash Book | `UNITKODE_BukuKas_YYYYMMDD_ROLE.xlsx` |
| Audit Log | `UNITKODE_AuditLog_YYYYMMDD_ROLE.xlsx` |
| Data Siswa | `UNITKODE_DataSiswa_YYYYMMDD_ROLE.xlsx` |

**Kode Unit:**  
- MI = Madrasah Ibtidaiyah  
- RA = Raudhatul Athfal  
- DTA = Diniyah Takmiliyah Awaliyah

---

*Petunjuk Teknis ini adalah dokumen hidup — diperbarui setiap ada pembaruan fitur sistem SAKUMI.*
