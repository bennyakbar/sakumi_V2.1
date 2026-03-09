# SAKUMI — Operational Roles & Workflows

> Dokumen referensi untuk pembuatan SOP, JUKNIS, dan JUKLAK oleh Codex.
> Disusun berdasarkan analisis sistem SAKUMI (Laravel 11 + Spatie Permission).

---

## 1. System Roles (Peran Sistem)

### 1.1. Super Admin (Operator Sistem)
**Deskripsi:** Administrator tertinggi sistem dengan akses penuh ke seluruh modul dan fitur.

**Tanggung Jawab:**
- Mengelola seluruh konfigurasi sistem (settings, backup, health check)
- Mengelola akun pengguna dan assignment role
- Memiliki akses penuh ke semua modul keuangan, master data, laporan, dan audit
- Melakukan permanent delete data jika diperlukan
- Troubleshooting dan eskalasi masalah sistem
- Mengelola notifikasi dan retry proses yang gagal

### 1.2. Bendahara
**Deskripsi:** Penanggung jawab keuangan sekolah. Mengelola seluruh operasi keuangan termasuk invoice, pembayaran, pengeluaran, dan rekonsiliasi bank.

**Tanggung Jawab:**
- Membuat dan mengelola fee matrix (tarif biaya per kelas/kategori)
- Membuat dan mengelola student fee mappings (pemetaan biaya per siswa)
- Membuat, meng-generate, dan membatalkan invoice
- Menerima dan memproses pembayaran (settlement)
- Menyetujui pengeluaran (expenses) dan mengelola anggaran
- Melakukan rekonsiliasi bank (import, match, close)
- Mencetak kuitansi dan invoice
- Memonitor laporan keuangan harian, bulanan, dan tunggakan
- Memonitor audit trail aktivitas keuangan

### 1.3. Kepala Sekolah
**Deskripsi:** Pimpinan sekolah dengan akses view-only untuk monitoring dan pengawasan keuangan.

**Tanggung Jawab:**
- Monitoring dashboard keuangan (pendapatan, tunggakan, pengeluaran)
- Mereview laporan harian, bulanan, tunggakan, dan buku kas
- Mereview data siswa dan master data
- Mengawasi aktivitas keuangan melalui audit log
- Mencetak invoice untuk kebutuhan verifikasi
- View-only akses ke semua modul (tidak dapat membuat/mengubah data)

### 1.4. Admin TU (Tata Usaha) — Per Unit
**Deskripsi:** Administrator operasional per unit pendidikan (MI, RA, atau DTA). Memiliki akses penuh ke operasi keuangan dan master data dalam lingkup unitnya.

**Varian:**
- `admin_tu_mi` — Admin TU Madrasah Ibtidaiyah
- `admin_tu_ra` — Admin TU Raudatul Atfal
- `admin_tu_dta` — Admin TU Diniyah Takmiliyah Awaliyah

**Tanggung Jawab:**
- Mengelola data siswa (CRUD, import, export) dalam unitnya
- Mengelola kelas, kategori, fee types, dan fee matrix
- Membuat dan meng-generate invoice
- Menerima pembayaran dan membuat settlement
- Membuat dan menyetujui pengeluaran
- Mengelola rekonsiliasi bank
- Mencetak kuitansi dan invoice
- Mengelola proses penerimaan siswa baru (PSB/Admission)
- Mereview laporan keuangan unit
- Membatalkan invoice dan settlement dalam unitnya

### 1.5. Operator TU
**Deskripsi:** Staf operasional TU yang menangani entry data harian. Memiliki akses create untuk transaksi keuangan tetapi tidak dapat membatalkan.

**Tanggung Jawab:**
- Mengelola data siswa (CRUD, import, export)
- Mengelola kelas dan kategori
- Membuat transaksi pembayaran (tidak dapat membatalkan)
- Membuat invoice dan settlement (tidak dapat membatalkan)
- Mencetak kuitansi pembayaran
- Mengelola proses penerimaan siswa baru (PSB) secara lengkap
- View-only akses ke fee types, fee matrix, dan pengeluaran

### 1.6. Kasir (Cashier)
**Deskripsi:** Petugas penerima pembayaran di loket. Akses terbatas hanya untuk menerima pembayaran dan mencetak kuitansi.

**Tanggung Jawab:**
- Menerima pembayaran dari wali murid
- Membuat transaksi pembayaran
- Mencetak kuitansi pembayaran (print pertama kali)
- Melihat daftar transaksi

### 1.7. Auditor (Auditor Internal)
**Deskripsi:** Pengawas internal dengan akses view-only ke seluruh data keuangan dan audit trail.

**Tanggung Jawab:**
- Mereview seluruh transaksi keuangan (view-only)
- Memeriksa audit trail dan log aktivitas
- Mereview laporan keuangan (daily, monthly, arrears, AR outstanding, collection, student statement, cash book)
- Mereview data master dan invoice
- Mengekspor laporan untuk keperluan audit
- Memverifikasi kepatuhan prosedur keuangan

---

## 2. System Access per Role (Akses Sistem per Peran)

### 2.1. Super Admin

| Modul | Akses | Permission |
|-------|-------|------------|
| Dashboard | View | `dashboard.view` |
| Master Data — Students | Full CRUD + Import/Export | `master.students.*` |
| Master Data — Classes | Full CRUD | `master.classes.*` |
| Master Data — Categories | Full CRUD | `master.categories.*` |
| Master Data — Fee Types | Full CRUD | `master.fee-types.*` |
| Master Data — Fee Matrix | Full CRUD | `master.fee-matrix.*` |
| Master Data — Student Fee Mappings | Full CRUD | `master.student-fee-mappings.*` |
| Transactions | View, Create, Cancel | `transactions.view`, `transactions.create`, `transactions.expense.create`, `transactions.cancel` |
| Invoices | Full (Create, Generate, Print, Cancel, Cancel Paid) | `invoices.*` |
| Settlements | Full (Create, Cancel, Void) | `settlements.*` |
| Expenses | Full (Create, Approve, Budget Manage, Report) | `expenses.*` |
| Bank Reconciliation | Full (View, Manage, Close) | `bank-reconciliation.*` |
| Receipts | Full (View, Print, Reprint) | `receipts.*` |
| Reports | All Reports + Export | `reports.*` |
| Admission (PSB) | Full CRUD + Workflow | `admission.*` |
| Users | Full CRUD + Manage Roles | `users.*` |
| Settings | View + Edit | `settings.*` |
| Backup | View + Create | `backup.*` |
| Audit Log | View | `audit.view` |
| Notifications | View + Retry | `notifications.*` |
| Health | View | `health.view` |

### 2.2. Bendahara

| Modul | Akses | Permission |
|-------|-------|------------|
| Dashboard | View | `dashboard.view` |
| Master Data — Students | View only | `master.students.view` |
| Master Data — Classes | View only | `master.classes.view` |
| Master Data — Categories | View only | `master.categories.view` |
| Master Data — Fee Types | View only | `master.fee-types.view` |
| Master Data — Fee Matrix | Full CRUD | `master.fee-matrix.*` |
| Master Data — Student Fee Mappings | Full CRUD | `master.student-fee-mappings.*` |
| Transactions | View, Create, Cancel | `transactions.view`, `transactions.create`, `transactions.expense.create`, `transactions.cancel` |
| Invoices | Full (Create, Generate, Print, Cancel, Cancel Paid) | `invoices.*` |
| Settlements | Full (Create, Cancel, Void) | `settlements.*` |
| Expenses | Full (Create, Approve, Budget Manage, Report) | `expenses.*` |
| Bank Reconciliation | Full (View, Manage, Close) | `bank-reconciliation.*` |
| Receipts | Full (View, Print, Reprint) | `receipts.*` |
| Reports | All Reports + Export | `reports.*` |
| Admission | View only | `admission.periods.view`, `admission.applicants.view` |
| Users | View only | `users.view` |
| Settings | View only | `settings.view` |
| Audit Log | View | `audit.view` |

### 2.3. Kepala Sekolah

| Modul | Akses | Permission |
|-------|-------|------------|
| Dashboard | View | `dashboard.view` |
| Master Data — All | View only | `master.*.view` |
| Transactions | View only | `transactions.view` |
| Invoices | View + Print | `invoices.view`, `invoices.print` |
| Settlements | View only | `settlements.view` |
| Expenses | View + Report | `expenses.view`, `expenses.report.view` |
| Bank Reconciliation | View only | `bank-reconciliation.view` |
| Receipts | View only | `receipts.view` |
| Reports | All Reports + Export | `reports.*` |
| Admission | View only | `admission.*.view` |
| Users | View only | `users.view` |
| Settings | View only | `settings.view` |
| Audit Log | View | `audit.view` |

### 2.4. Admin TU (MI/RA/DTA)

| Modul | Akses | Permission |
|-------|-------|------------|
| Dashboard | View | `dashboard.view` |
| Master Data — Students | Full CRUD + Import/Export | `master.students.*` |
| Master Data — Classes | Full CRUD | `master.classes.*` |
| Master Data — Categories | Full CRUD | `master.categories.*` |
| Master Data — Fee Types | Full CRUD | `master.fee-types.*` |
| Master Data — Fee Matrix | Full CRUD | `master.fee-matrix.*` |
| Master Data — Student Fee Mappings | Full CRUD | `master.student-fee-mappings.*` |
| Transactions | View, Create, Cancel | `transactions.*` |
| Invoices | Create, Generate, Print, Cancel (tanpa Cancel Paid) | `invoices.view/create/generate/print/cancel` |
| Settlements | Create, Cancel (tanpa Void) | `settlements.view/create/cancel` |
| Expenses | Full (Create, Approve, Budget Manage, Report) | `expenses.*` |
| Bank Reconciliation | Full (View, Manage, Close) | `bank-reconciliation.*` |
| Receipts | Full (View, Print, Reprint) | `receipts.*` |
| Reports | All Reports + Export | `reports.*` |
| Admission (PSB) | Full CRUD + Workflow | `admission.*` |
| Users | View only | `users.view` |
| Settings | View only | `settings.view` |
| Audit Log | View | `audit.view` |
| Notifications | View | `notifications.view` |

### 2.5. Operator TU

| Modul | Akses | Permission |
|-------|-------|------------|
| Dashboard | View | `dashboard.view` |
| Master Data — Students | Full CRUD + Import/Export | `master.students.*` |
| Master Data — Classes | Full CRUD | `master.classes.*` |
| Master Data — Categories | Full CRUD | `master.categories.*` |
| Master Data — Fee Types | View only | `master.fee-types.view` |
| Master Data — Fee Matrix | View only | `master.fee-matrix.view` |
| Master Data — Student Fee Mappings | View only | `master.student-fee-mappings.view` |
| Transactions | View, Create (tanpa Cancel) | `transactions.view`, `transactions.create` |
| Invoices | Create, Generate, Print (tanpa Cancel) | `invoices.view/create/generate/print` |
| Settlements | Create (tanpa Cancel/Void) | `settlements.view`, `settlements.create` |
| Expenses | View only | `expenses.view` |
| Receipts | View + Print | `receipts.view`, `receipts.print` |
| Reports | All Reports (tanpa Export) | `reports.daily/monthly/arrears/ar-outstanding/collection/student-statement/cash-book` |
| Admission (PSB) | Full CRUD + Workflow | `admission.*` |
| Users | View only | `users.view` |
| Settings | View only | `settings.view` |

### 2.6. Kasir (Cashier)

| Modul | Akses | Permission |
|-------|-------|------------|
| Dashboard | View | `dashboard.view` |
| Transactions | View + Create | `transactions.view`, `transactions.create` |
| Receipts | View + Print | `receipts.view`, `receipts.print` |

### 2.7. Auditor

| Modul | Akses | Permission |
|-------|-------|------------|
| Dashboard | View | `dashboard.view` |
| Master Data — All | View only | `master.*.view` |
| Transactions | View only | `transactions.view` |
| Invoices | View + Print | `invoices.view`, `invoices.print` |
| Settlements | View only | `settlements.view` |
| Expenses | View + Report | `expenses.view`, `expenses.report.view` |
| Bank Reconciliation | View only | `bank-reconciliation.view` |
| Receipts | View only | `receipts.view` |
| Reports | All Reports + Export | `reports.*` |
| Admission | View only | `admission.*.view` |
| Audit Log | View | `audit.view` |

---

## 3. Daily Operational Workflow (Alur Kerja Harian)

### 3.1. Kasir — Alur Harian

**Pagi (07:00 - 08:00):**
1. Login ke sistem SAKUMI
2. Buka halaman Dashboard — periksa ringkasan transaksi hari ini
3. Siapkan loket pembayaran

**Jam Operasional (08:00 - 14:00):**
1. Terima pembayaran dari wali murid
2. Buka menu **Transactions > Create**
3. Pilih siswa, masukkan nominal pembayaran, pilih metode pembayaran
4. Simpan transaksi
5. Cetak kuitansi pembayaran via menu **Receipts > Print**
6. Serahkan kuitansi ke wali murid
7. Ulangi untuk setiap pembayaran masuk

**Akhir Hari (14:00 - 15:00):**
1. Buka Dashboard — verifikasi total transaksi hari ini
2. Serahkan catatan pembayaran dan uang tunai ke Bendahara
3. Logout dari sistem

---

### 3.2. Operator TU — Alur Harian

**Pagi (07:00 - 08:00):**
1. Login ke sistem SAKUMI
2. Periksa Dashboard — lihat ringkasan keuangan hari ini
3. Periksa apakah ada data siswa baru yang perlu diinput

**Jam Operasional (08:00 - 14:00):**
1. **Entry Data Siswa:**
   - Input data siswa baru via **Master Data > Students > Create**
   - Import data siswa massal via **Master Data > Students > Import** (jika ada file Excel)
   - Update data siswa yang berubah (kelas, status, dll)

2. **Proses Penerimaan Siswa Baru (PSB):**
   - Review pendaftar baru via **Admission > Applicants**
   - Proses penerimaan: Review → Accept → Enroll

3. **Entry Transaksi Pembayaran:**
   - Terima pembayaran dan input ke **Transactions > Create**
   - Buat invoice baru jika belum ada via **Invoices > Create**
   - Buat settlement pembayaran via **Settlements > Create**
   - Cetak kuitansi via **Receipts > Print**

**Akhir Hari (14:00 - 15:00):**
1. Periksa laporan harian via **Reports > Daily**
2. Pastikan semua entry hari ini sudah benar
3. Laporkan ke Bendahara atau Admin TU jika ada ketidaksesuaian
4. Logout dari sistem

---

### 3.3. Admin TU (MI/RA/DTA) — Alur Harian

**Pagi (07:00 - 08:00):**
1. Login ke sistem SAKUMI — pastikan unit yang dipilih sesuai (MI/RA/DTA)
2. Periksa Dashboard — review ringkasan keuangan unit
3. Periksa tunggakan siswa yang sudah jatuh tempo

**Jam Operasional (08:00 - 14:00):**
1. **Kelola Master Data:**
   - Update data siswa, kelas, kategori sesuai kebutuhan
   - Update fee types dan fee matrix jika ada perubahan tarif

2. **Proses Keuangan:**
   - Generate invoice untuk siswa yang belum memiliki tagihan
   - Terima dan proses pembayaran (create transaction + settlement)
   - Input pengeluaran operasional via **Expenses > Create**
   - Approve pengeluaran yang sudah dientry
   - Cetak kuitansi dan invoice

3. **Kelola PSB:**
   - Monitor dan proses pendaftar baru
   - Review → Accept/Reject → Enroll siswa yang diterima

4. **Rekonsiliasi Bank:**
   - Import mutasi bank via **Bank Reconciliation > Import**
   - Match transaksi bank dengan transaksi sistem
   - Selesaikan rekonsiliasi jika semua sudah match

**Akhir Hari (14:00 - 15:00):**
1. Review laporan harian via **Reports > Daily**
2. Periksa outstanding invoice via **Reports > AR Outstanding**
3. Pastikan kas tunai sesuai dengan catatan sistem
4. Verifikasi semua transaksi hari ini via audit trail
5. Logout dari sistem

---

### 3.4. Bendahara — Alur Harian

**Pagi (07:00 - 08:00):**
1. Login ke sistem SAKUMI
2. Periksa Dashboard — review ringkasan konsolidasi semua unit
3. Periksa total tunggakan (Total Arrears) dan kas masuk hari sebelumnya
4. Review notifikasi dan anomali

**Jam Operasional (08:00 - 14:00):**
1. **Supervisi Keuangan:**
   - Monitor transaksi pembayaran yang masuk secara real-time
   - Verifikasi pembayaran besar atau tidak biasa
   - Approve pengeluaran yang diajukan oleh Admin TU/Operator TU

2. **Operasi Keuangan:**
   - Buat/generate invoice bulanan jika belum dilakukan
   - Proses pembatalan invoice jika ada kesalahan
   - Void/cancel settlement jika ada pembayaran yang salah
   - Kelola fee matrix jika ada penyesuaian tarif
   - Kelola student fee mappings untuk kasus khusus (diskon, beasiswa)

3. **Rekonsiliasi:**
   - Import mutasi bank dan lakukan matching
   - Close rekonsiliasi yang sudah selesai

4. **Pengeluaran:**
   - Review dan approve pengeluaran yang pending
   - Input pengeluaran langsung jika diperlukan
   - Monitor budget vs realization

**Akhir Hari (14:00 - 15:00):**
1. Review Laporan Harian (**Reports > Daily**) — pastikan income dan expense balance
2. Cetak/export laporan harian untuk arsip
3. Rekonsiliasi kas (saldo sistem vs kas fisik)
4. Review audit log untuk aktivitas tidak wajar
5. Logout dari sistem

---

### 3.5. Kepala Sekolah — Alur Harian

**Pagi (08:00 - 09:00):**
1. Login ke sistem SAKUMI
2. Buka Dashboard — review ringkasan keuangan keseluruhan
3. Perhatikan trend: pendapatan hari ini, tunggakan, pengeluaran

**Jam Operasional (Sesekali Selama Hari):**
1. Review laporan keuangan jika diperlukan
2. Periksa status tunggakan siswa via **Reports > Arrears**
3. Periksa realisasi pengeluaran via **Expenses > Budget Report**
4. Monitor aktivitas via **Audit Log** jika ada laporan ketidaksesuaian

**Akhir Hari:**
1. Review ringkasan akhir hari dari Dashboard (opsional)
2. Logout dari sistem

---

### 3.6. Auditor — Alur Harian

**Pagi:**
1. Login ke sistem SAKUMI
2. Buka **Audit Log** — review aktivitas yang terjadi sejak review terakhir
3. Buka Dashboard — periksa ringkasan keuangan

**Jam Operasional:**
1. Review transaksi yang mencurigakan atau tidak wajar
2. Cross-check laporan harian dengan transaksi individual
3. Verifikasi kesesuaian settlement dengan invoice
4. Periksa pengeluaran yang sudah diapprove — apakah sesuai prosedur
5. Export laporan untuk working paper audit

**Akhir Hari:**
1. Catat temuan audit hari ini
2. Logout dari sistem

---

## 4. Weekly Operational Workflow (Alur Kerja Mingguan)

### 4.1. Bendahara — Mingguan

| Hari | Aktivitas |
|------|-----------|
| Senin | Review konsolidasi keuangan minggu sebelumnya. Periksa outstanding invoices. |
| Rabu | Rekonsiliasi bank mid-week. Import mutasi bank terbaru, match transaksi. |
| Jumat | Generate laporan mingguan: AR Outstanding, Collection Report. Review budget vs realization. Serahkan ringkasan ke Kepala Sekolah. |

### 4.2. Admin TU — Mingguan

| Hari | Aktivitas |
|------|-----------|
| Senin | Periksa daftar tunggakan siswa. Kirim pengingat pembayaran jika diperlukan. |
| Rabu | Update data siswa (mutasi, perubahan kelas, status). |
| Jumat | Rekap transaksi mingguan. Serahkan laporan ke Bendahara. |

### 4.3. Kepala Sekolah — Mingguan

| Hari | Aktivitas |
|------|-----------|
| Jumat | Review laporan keuangan mingguan dari Bendahara. Review tingkat tunggakan via Arrears Report. Diskusi dengan Bendahara jika ada masalah keuangan. |

### 4.4. Auditor — Mingguan

| Hari | Aktivitas |
|------|-----------|
| Senin | Review audit trail minggu sebelumnya. Identifikasi anomali. |
| Jumat | Buat ringkasan temuan mingguan. Cross-check saldo kas dengan catatan sistem. |

---

## 5. Monthly Operational Workflow (Alur Kerja Bulanan)

### 5.1. Awal Bulan (Tanggal 1-5)

| Role | Aktivitas |
|------|-----------|
| **Bendahara** | Generate invoice bulanan untuk seluruh siswa aktif (otomatis via scheduler atau manual via **Invoices > Generate Monthly**). Pastikan tarif dan fee matrix sudah benar. Review dan approve budget bulan berjalan. |
| **Admin TU** | Verifikasi daftar siswa aktif per unit sebelum generate invoice. Pastikan data kelas dan kategori sudah terupdate. Distribusikan tagihan ke wali murid. |
| **Operator TU** | Bantu generate invoice jika diminta. Mulai menerima pembayaran bulan berjalan. |
| **Kepala Sekolah** | Review ringkasan keuangan bulan sebelumnya. |

### 5.2. Pertengahan Bulan (Tanggal 10-15)

| Role | Aktivitas |
|------|-----------|
| **Bendahara** | Review tingkat collection rate via **Reports > Collection**. Identifikasi siswa dengan tunggakan besar via **Reports > Arrears**. Rekonsiliasi bank tengah bulan. |
| **Admin TU** | Follow-up pembayaran yang belum masuk. Update data siswa jika ada mutasi. |
| **Auditor** | Mid-month review: verifikasi kesesuaian transaksi dengan dokumen pendukung. |

### 5.3. Akhir Bulan (Tanggal 25-31)

| Role | Aktivitas |
|------|-----------|
| **Bendahara** | Tutup buku bulanan: Generate **Monthly Report** final. Export dan arsipkan laporan bulanan. Rekonsiliasi bank akhir bulan — pastikan semua transaksi matched, lalu **close** sesi rekonsiliasi. Review **Cash Book** report. Review dan finalize **Budget vs Realization** report. Serahkan laporan keuangan bulanan ke Kepala Sekolah. |
| **Admin TU** | Rekap pembayaran unit. Serahkan laporan unit ke Bendahara. Update data siswa akhir bulan (kenaikan kelas, mutasi). |
| **Kepala Sekolah** | Review laporan keuangan bulanan konsolidasi. Evaluasi performa keuangan: income vs target, collection rate, outstanding. Tanda tangani laporan keuangan bulanan. |
| **Auditor** | Full monthly review: Audit seluruh transaksi bulan berjalan. Cross-check laporan keuangan dengan bukti transaksi. Buat laporan temuan bulanan. Verifikasi rekonsiliasi bank sudah closed dengan benar. |
| **Super Admin** | Backup database bulanan. Review system health dan performance. Update konfigurasi sistem jika diperlukan. |

### 5.4. Akhir Tahun Ajaran / Periode Fiskal

| Role | Aktivitas |
|------|-----------|
| **Bendahara** | Generate laporan akhir tahun konsolidasi. Selesaikan seluruh rekonsiliasi bank yang outstanding. Finalisasi seluruh tunggakan — identifikasi piutang macet. Siapkan anggaran tahun ajaran baru (budget). Update fee matrix untuk tahun ajaran baru. |
| **Admin TU** | Proses kenaikan kelas siswa via **Promotion Batches**. Update status siswa (aktif, lulus, keluar). Siapkan data siswa baru dari PSB. |
| **Operator TU** | Bantu proses kenaikan kelas. Input data siswa baru dari PSB ke master data. |
| **Kepala Sekolah** | Review laporan akhir tahun. Evaluasi performa keuangan tahunan. Approve anggaran tahun ajaran baru. |
| **Auditor** | Audit akhir tahun komprehensif. Verifikasi saldo akhir vs catatan fisik. Laporan temuan dan rekomendasi tahunan. |
| **Super Admin** | Full backup sistem. Arsipkan data tahun ajaran lama. Persiapkan konfigurasi tahun ajaran baru. Review dan update user access. |

---

## 6. SOP per Role (Standard Operating Procedure)

### 6.1. SOP — Bendahara

**SOP-BND-01: Pengelolaan Invoice Bulanan**
- Memastikan data siswa aktif dan fee matrix sudah benar sebelum generate invoice
- Generate invoice bulanan pada tanggal 1 setiap bulan (otomatis atau manual)
- Memverifikasi invoice yang ter-generate sudah sesuai jumlah siswa aktif
- Menangani kasus khusus: siswa dengan diskon, beasiswa, atau tarif berbeda

**SOP-BND-02: Penerimaan Pembayaran**
- Memverifikasi invoice yang akan dibayar sudah benar
- Memproses pembayaran via settlement dengan metode yang sesuai (tunai, transfer, dll)
- Menerbitkan kuitansi resmi untuk setiap pembayaran
- Merekonsiliasi kas tunai pada akhir hari

**SOP-BND-03: Pengelolaan Pengeluaran**
- Menerima dan mereview pengajuan pengeluaran
- Memverifikasi ketersediaan anggaran sebelum approve
- Menyetujui pengeluaran yang sesuai prosedur
- Menolak atau mengembalikan pengajuan yang tidak lengkap
- Memonitor realisasi vs anggaran secara berkala

**SOP-BND-04: Rekonsiliasi Bank**
- Melakukan rekonsiliasi bank minimal 2x per bulan (pertengahan dan akhir bulan)
- Import mutasi bank dari file statement
- Match setiap baris mutasi dengan transaksi di sistem
- Investigasi unmatched items
- Close sesi rekonsiliasi setelah semua items resolved
- Mengarsipkan bukti rekonsiliasi

**SOP-BND-05: Pelaporan Keuangan**
- Generate laporan harian pada akhir hari kerja
- Generate laporan bulanan pada akhir bulan
- Review tunggakan dan AR outstanding secara berkala
- Export dan arsipkan laporan untuk dokumentasi
- Serahkan laporan ke Kepala Sekolah sesuai jadwal

### 6.2. SOP — Admin TU

**SOP-ATU-01: Pengelolaan Data Siswa**
- Input data siswa baru dengan data lengkap dan akurat
- Verifikasi tidak ada duplikasi data sebelum menyimpan
- Update data siswa (kelas, status, kategori) tepat waktu
- Proses kenaikan kelas melalui fitur Promotion Batches
- Import data massal menggunakan template yang sudah disediakan

**SOP-ATU-02: Pengelolaan Invoice**
- Generate invoice bulanan setelah memastikan data siswa terupdate
- Verifikasi tarif sesuai fee matrix sebelum generate
- Distribusikan informasi tagihan ke wali murid
- Membatalkan invoice yang salah dengan alasan yang jelas

**SOP-ATU-03: Penerimaan Pembayaran Unit**
- Terima pembayaran dan catat di sistem secara real-time
- Cocokkan pembayaran dengan invoice yang tepat
- Cetak kuitansi dan serahkan ke pembayar
- Serahkan kas tunai ke Bendahara pada akhir hari

**SOP-ATU-04: Proses Penerimaan Siswa Baru (PSB)**
- Buka periode pendaftaran via Admission Periods
- Input atau review data pendaftar
- Proses workflow: Review → Accept/Reject → Enroll
- Enroll siswa yang diterima ke dalam master data

### 6.3. SOP — Operator TU

**SOP-OTU-01: Entry Data Harian**
- Input data siswa baru sesuai formulir pendaftaran
- Update data siswa yang berubah berdasarkan dokumen resmi
- Input transaksi pembayaran dengan teliti
- Cetak kuitansi untuk setiap pembayaran

**SOP-OTU-02: Pengelolaan Master Data**
- Kelola data kelas (buat baru, edit, hapus) sesuai struktur sekolah
- Kelola kategori biaya sesuai kebutuhan
- Import data siswa menggunakan template Excel yang benar
- Export data untuk kebutuhan pelaporan

### 6.4. SOP — Kasir

**SOP-KSR-01: Penerimaan Pembayaran Loket**
- Terima pembayaran dari wali murid
- Verifikasi identitas pembayar dan siswa yang dibayarkan
- Input transaksi ke sistem dengan nominal yang tepat
- Pilih metode pembayaran yang sesuai
- Cetak kuitansi dan serahkan ke pembayar
- Jangan menerima pembayaran tanpa input ke sistem terlebih dahulu

### 6.5. SOP — Kepala Sekolah

**SOP-KS-01: Monitoring Keuangan**
- Review dashboard keuangan secara berkala (minimal harian)
- Review laporan keuangan mingguan dari Bendahara
- Review dan approve laporan keuangan bulanan
- Evaluasi tingkat tunggakan dan collection rate
- Ambil keputusan kebijakan keuangan berdasarkan data

### 6.6. SOP — Auditor

**SOP-AUD-01: Audit Rutin**
- Review audit trail secara berkala (minimal mingguan)
- Verifikasi kesesuaian transaksi dengan prosedur
- Cross-check laporan keuangan dengan bukti transaksi
- Identifikasi anomali atau ketidaksesuaian
- Dokumentasikan temuan dan rekomendasi

---

## 7. JUKNIS per Role (Petunjuk Teknis — Langkah Sistem)

### 7.1. JUKNIS — Bendahara

**JUKNIS-BND-01: Generate Invoice Bulanan**
1. Login ke SAKUMI → Pilih unit (MI/RA/DTA) atau "Semua Unit"
2. Navigasi ke menu **Invoices**
3. Klik tombol **Generate Invoices** (hijau)
4. Pilih bulan dan tahun tagihan
5. Pilih kelas (opsional — kosongkan untuk semua kelas)
6. Klik **Generate**
7. Sistem akan membuat invoice untuk setiap siswa aktif berdasarkan fee matrix
8. Verifikasi jumlah invoice yang ter-generate di halaman daftar invoice
9. Filter berdasarkan bulan untuk melihat invoice yang baru dibuat

**JUKNIS-BND-02: Proses Pembayaran (Settlement)**
1. Navigasi ke menu **Settlements**
2. Klik tombol **Add Payment** (biru)
3. Cari dan pilih siswa
4. Pilih invoice yang akan dibayar (bisa multi-invoice)
5. Masukkan nominal pembayaran
6. Pilih metode pembayaran (Cash, Transfer, QRIS, dll)
7. Klik **Simpan**
8. Sistem otomatis mengalokasikan pembayaran ke invoice
9. Cetak kuitansi via ikon printer di daftar settlement

**JUKNIS-BND-03: Approve Pengeluaran**
1. Navigasi ke menu **Expenses**
2. Lihat daftar pengeluaran dengan status "Draft"
3. Klik tombol **Approve** (hijau) pada pengeluaran yang akan disetujui
4. Sistem akan mengubah status menjadi "Posted/Approved"
5. Pengeluaran akan masuk ke laporan keuangan

**JUKNIS-BND-04: Rekonsiliasi Bank**
1. Navigasi ke menu **Bank Reconciliation**
2. Klik **Buat Sesi Baru** — masukkan periode dan saldo awal
3. Klik **Import Statement** — upload file mutasi bank (CSV/Excel)
4. Untuk setiap baris mutasi, klik **Match** untuk mencocokkan dengan transaksi sistem
5. Jika tidak ada match, investigasi perbedaan
6. Setelah semua baris resolved, klik **Close Reconciliation**
7. Sesi tidak bisa di-reopen setelah closed

**JUKNIS-BND-05: Cancel/Void Settlement**
1. Navigasi ke menu **Settlements**
2. Cari settlement yang akan dibatalkan
3. Klik ikon aksi → pilih **Void** (membatalkan tapi tetap terlihat di catatan) atau **Cancel** (hapus dari catatan)
4. Masukkan alasan pembatalan
5. Sistem akan otomatis mengembalikan status invoice menjadi "Unpaid"
6. **Catatan:** Cancel Paid Invoice hanya bisa dilakukan oleh Bendahara (bukan Admin TU)

### 7.2. JUKNIS — Admin TU

**JUKNIS-ATU-01: Input Data Siswa Baru**
1. Navigasi ke **Master Data > Students**
2. Klik **Tambah Siswa**
3. Isi formulir: Nama, NIS, Kelas, Kategori, data orang tua/wali
4. Klik **Simpan**
5. Sistem otomatis scope data ke unit yang aktif

**JUKNIS-ATU-02: Import Data Siswa Massal**
1. Navigasi ke **Master Data > Students**
2. Klik **Import**
3. Download template Excel terlebih dahulu via **Download Template**
4. Isi template dengan data siswa (sesuai format kolom)
5. Upload file Excel yang sudah diisi
6. Review preview data — perbaiki jika ada error
7. Klik **Process Import**

**JUKNIS-ATU-03: Proses PSB (Penerimaan Siswa Baru)**
1. Buat Periode PSB: **Admission > Periods > Create** — isi nama, tanggal buka/tutup
2. Input Pendaftar: **Admission > Applicants > Create** — isi data calon siswa
3. Review: Klik **Review** pada pendaftar → isi catatan review
4. Accept/Reject: Klik **Accept** (terima) atau **Reject** (tolak)
5. Enroll: Untuk pendaftar yang diterima, klik **Enroll** untuk memasukkan ke master data siswa

**JUKNIS-ATU-04: Proses Kenaikan Kelas**
1. Navigasi ke **Master Data > Promotion Batches**
2. Klik **Create** — pilih tahun ajaran dan kelas asal
3. Sistem menampilkan daftar siswa yang akan naik kelas
4. Review dan sesuaikan kelas tujuan per siswa
5. Klik **Approve** untuk mengonfirmasi batch
6. Klik **Apply** untuk mengeksekusi kenaikan kelas

### 7.3. JUKNIS — Operator TU

**JUKNIS-OTU-01: Buat Transaksi Pembayaran**
1. Navigasi ke menu **Transactions**
2. Klik **Create**
3. Pilih siswa dari dropdown
4. Masukkan detail pembayaran (nominal, metode, catatan)
5. Klik **Simpan**
6. Cetak kuitansi via **Receipts > Print**

**JUKNIS-OTU-02: Generate Invoice**
1. Navigasi ke menu **Invoices**
2. Klik **Generate Invoices**
3. Pilih bulan, tahun, dan kelas (opsional)
4. Klik **Generate**
5. Verifikasi hasil di halaman daftar invoice

### 7.4. JUKNIS — Kasir

**JUKNIS-KSR-01: Terima Pembayaran dan Cetak Kuitansi**
1. Login ke SAKUMI
2. Navigasi ke **Transactions > Create**
3. Cari siswa berdasarkan nama atau NIS
4. Masukkan nominal pembayaran
5. Pilih metode pembayaran (Cash/Transfer/QRIS)
6. Klik **Simpan**
7. Klik **Print Receipt** pada transaksi yang baru dibuat
8. Serahkan kuitansi ke pembayar

### 7.5. JUKNIS — Kepala Sekolah

**JUKNIS-KS-01: Review Dashboard**
1. Login ke SAKUMI
2. Dashboard otomatis menampilkan: Net Cash Hari Ini, Net Cash Bulanan, Total Tunggakan, Total Pengeluaran
3. Scroll ke bawah untuk melihat grafik tren pendapatan vs pengeluaran
4. Lihat breakdown per unit (MI/RA/DTA)
5. Lihat daftar transaksi terbaru

**JUKNIS-KS-02: Review Laporan**
1. Navigasi ke menu **Reports**
2. Pilih jenis laporan yang diinginkan:
   - **Daily** — rekap transaksi per hari
   - **Monthly** — rekap bulanan
   - **Arrears** — daftar tunggakan siswa
   - **AR Outstanding** — piutang yang belum tertagih
   - **Collection** — tingkat kolektibilitas
   - **Student Statement** — riwayat transaksi per siswa
   - **Cash Book** — buku kas
3. Atur filter (tanggal, unit, kelas)
4. Klik **Export** untuk mengunduh dalam format Excel/PDF

### 7.6. JUKNIS — Auditor

**JUKNIS-AUD-01: Review Audit Trail**
1. Login ke SAKUMI
2. Navigasi ke **Audit Log**
3. Filter berdasarkan tanggal, user, atau modul
4. Review detail setiap aktivitas: siapa, kapan, apa yang diubah
5. Catat temuan yang perlu diinvestigasi

**JUKNIS-AUD-02: Cross-Check Laporan**
1. Buka **Reports > Daily** — catat total income dan expense
2. Buka **Settlements** — hitung total pembayaran
3. Bandingkan angka di laporan dengan transaksi individual
4. Buka **Reports > Cash Book** — verifikasi saldo kas
5. Export laporan untuk working paper

---

## 8. JUKLAK per Role (Petunjuk Pelaksanaan — Pedoman Operasional)

### 8.1. JUKLAK — Bendahara

**JUKLAK-BND-01: Kebijakan Pembayaran**
- Setiap pembayaran WAJIB dicatat dalam sistem sebelum diterima
- Kuitansi resmi WAJIB dicetak dan diserahkan untuk setiap pembayaran
- Pembayaran tunai harus disetor ke bank maksimal H+1
- Pembatalan transaksi hanya boleh dilakukan pada hari yang sama dengan alasan yang jelas
- Void settlement hanya dilakukan jika ada kesalahan pencatatan, bukan untuk refund

**JUKLAK-BND-02: Kebijakan Pengeluaran**
- Semua pengeluaran > Rp 500.000 harus disetujui oleh Bendahara
- Pengeluaran harus sesuai dengan anggaran yang telah ditetapkan
- Jika melebihi anggaran, harus ada persetujuan Kepala Sekolah
- Bukti pengeluaran (nota/faktur) harus dilampirkan
- Pengeluaran tanpa bukti tidak boleh diapprove

**JUKLAK-BND-03: Kebijakan Rekonsiliasi**
- Rekonsiliasi bank WAJIB dilakukan setiap akhir bulan
- Selisih rekonsiliasi harus diinvestigasi dan diselesaikan sebelum close
- Sesi rekonsiliasi yang sudah di-close tidak bisa dibuka kembali
- Hasil rekonsiliasi harus dilaporkan ke Kepala Sekolah

**JUKLAK-BND-04: Kebijakan Invoice**
- Invoice bulanan digenerate paling lambat tanggal 5 setiap bulan
- Jatuh tempo pembayaran standar: tanggal 10 bulan berjalan
- Invoice yang salah dibatalkan (cancel), bukan diedit
- Cancel paid invoice hanya untuk kasus force majeure dengan approval Kepala Sekolah

### 8.2. JUKLAK — Admin TU

**JUKLAK-ATU-01: Kebijakan Data Siswa**
- Data siswa harus diinput dalam 3 hari kerja sejak pendaftaran
- Perubahan data siswa harus berdasarkan dokumen resmi (surat mutasi, akte, dll)
- Penghapusan data siswa hanya boleh dilakukan oleh Super Admin
- Backup data harus dilakukan sebelum import massal
- Data siswa yang keluar diubah statusnya, bukan dihapus

**JUKLAK-ATU-02: Kebijakan Operasional Unit**
- Admin TU hanya mengoperasikan data dalam unit yang menjadi tanggung jawabnya
- Serah terima kas tunai ke Bendahara dilakukan setiap akhir hari kerja
- Laporan rekap unit diserahkan ke Bendahara setiap akhir minggu
- Perubahan tarif (fee matrix) harus dikoordinasikan dengan Bendahara

**JUKLAK-ATU-03: Kebijakan PSB**
- Periode pendaftaran harus dibuat sebelum menerima pendaftar
- Setiap pendaftar harus melalui proses review sebelum diterima/ditolak
- Pendaftar yang diterima harus di-enroll dalam 7 hari kerja
- Data pendaftar yang ditolak tetap disimpan dalam sistem

### 8.3. JUKLAK — Operator TU

**JUKLAK-OTU-01: Kebijakan Entry Data**
- Setiap entry data harus dilakukan dengan teliti dan double-check
- Operator TU TIDAK boleh membatalkan transaksi — harus minta Bendahara/Admin TU
- Jika terjadi kesalahan entry, segera lapor ke Admin TU atau Bendahara
- Semua transaksi harus dientry pada hari yang sama (tidak boleh backdate)

**JUKLAK-OTU-02: Kebijakan Cetak Kuitansi**
- Kuitansi hanya dicetak satu kali (first print)
- Reprint harus diminta ke Bendahara atau Admin TU yang memiliki permission reprint
- Kuitansi yang salah cetak harus dimusnahkan dan dilaporkan

### 8.4. JUKLAK — Kasir

**JUKLAK-KSR-01: Kebijakan Loket Pembayaran**
- Kasir WAJIB login dengan akun pribadi (tidak boleh sharing akun)
- Setiap pembayaran HARUS diinput ke sistem sebelum menerima uang
- Kuitansi WAJIB dicetak dan diserahkan untuk setiap pembayaran
- Uang tunai harus dihitung dan diverifikasi di depan pembayar
- Kas tunai harus diserahkan ke Bendahara pada akhir shift
- Kasir TIDAK boleh membatalkan transaksi — lapor ke Bendahara

**JUKLAK-KSR-02: Kebijakan Keamanan**
- Jangan tinggalkan sistem dalam keadaan login tanpa pengawasan
- Logout dari sistem saat istirahat atau meninggalkan loket
- Jangan berikan password ke siapapun
- Laporkan aktivitas mencurigakan ke Bendahara atau Super Admin

### 8.5. JUKLAK — Kepala Sekolah

**JUKLAK-KS-01: Kebijakan Pengawasan**
- Review dashboard keuangan minimal 1x per hari
- Review laporan keuangan bulanan dan tanda tangani
- Jika menemukan ketidaksesuaian, eskalasi ke Bendahara dan/atau Auditor
- Keputusan kebijakan keuangan (perubahan tarif, diskon, penghapusan piutang) harus terdokumentasi

### 8.6. JUKLAK — Auditor

**JUKLAK-AUD-01: Kebijakan Audit**
- Audit trail harus direview minimal 1x per minggu
- Temuan audit harus didokumentasikan secara tertulis
- Temuan material harus dilaporkan ke Kepala Sekolah dalam 2 hari kerja
- Auditor TIDAK boleh memiliki akses create/edit/delete — hanya view
- Independensi auditor harus dijaga: tidak merangkap sebagai operator
- Laporan audit bulanan harus diserahkan paling lambat tanggal 10 bulan berikutnya

**JUKLAK-AUD-02: Checklist Audit Bulanan**
- [ ] Verifikasi total transaksi pembayaran = total settlement
- [ ] Verifikasi saldo kas sistem = saldo kas bank (rekonsiliasi)
- [ ] Verifikasi pengeluaran yang diapprove memiliki bukti lengkap
- [ ] Verifikasi tidak ada invoice yang digenerate ganda (duplicate)
- [ ] Verifikasi void/cancel settlement memiliki alasan yang valid
- [ ] Cross-check laporan daily aggregat = laporan monthly
- [ ] Review user access: apakah sesuai dengan role yang ditetapkan

### 8.7. JUKLAK — Super Admin

**JUKLAK-SA-01: Kebijakan Sistem**
- Backup database dilakukan otomatis harian, diverifikasi manual mingguan
- Akun pengguna harus segera dinonaktifkan ketika staf berhenti/mutasi
- Password reset hanya dilakukan setelah verifikasi identitas pemohon
- Perubahan konfigurasi sistem harus didokumentasikan
- Permanent delete hanya dilakukan jika benar-benar diperlukan dan sudah diapprove
- Health check sistem harus dimonitor secara berkala
- Update sistem dilakukan di luar jam operasional

---

## Lampiran: Matriks RACI

| Aktivitas | Super Admin | Bendahara | Admin TU | Operator TU | Kasir | Kepala Sekolah | Auditor |
|-----------|:-----------:|:---------:|:--------:|:-----------:|:-----:|:--------------:|:-------:|
| Generate Invoice | I | R/A | R | C | - | I | I |
| Terima Pembayaran | - | R/A | R | R | R | I | I |
| Approve Pengeluaran | - | R/A | R | - | - | I | I |
| Rekonsiliasi Bank | I | R/A | R | - | - | I | C |
| Cancel/Void Transaksi | I | R/A | R | - | - | I | I |
| Generate Laporan | I | R | R | C | - | A | C |
| Kelola Data Siswa | I | - | R/A | R | - | I | I |
| Proses PSB | I | - | R/A | R | - | I | I |
| Kelola User | R/A | C | - | - | - | I | I |
| Audit Review | I | C | C | C | C | I | R/A |
| Backup Sistem | R/A | - | - | - | - | I | I |

**Keterangan RACI:**
- **R** = Responsible (Pelaksana)
- **A** = Accountable (Penanggung jawab)
- **C** = Consulted (Dikonsultasikan)
- **I** = Informed (Diinformasikan)

---

> **Catatan:** Dokumen ini disusun berdasarkan analisis teknis sistem SAKUMI dan ditujukan sebagai bahan referensi untuk pembuatan dokumen SOP, JUKNIS, dan JUKLAK formal oleh Codex. Penyesuaian dengan kebijakan spesifik sekolah mungkin diperlukan.
