# Daily Operations Guide - School Financial Management Web Application

## Version 1: Operational Wall Sheet (Print-Ready)

### English (One Page)

## School Financial Management System - Daily Operations Quick SOP

### Roles
- Admin: system access, master data, security/audit oversight
- Staff: general operational support (non-controlled receipt authority)
- Cashier: payment entry, first-time receipt printing, settlement submission
- Finance Officer: reconciliation, approvals, financial reporting
- School Principal: executive review and approval

### Daily Timeline
- Morning:
  - Admin: check health, users, permissions, backups
  - Cashier: verify date/session, opening balance, receipt sequence
  - Finance Officer: review exceptions/KPIs, invoice status
  - Principal: review dashboard and risk alerts
- Operational Hours:
  - Cashier posts payments and prints receipts
  - Finance Officer reviews exceptions, approves/rejects requests
  - Admin supports access/master data updates (approved only)
  - Principal reviews trend and summary reports
- End of Day:
  - Cashier submits settlement
  - Finance Officer reconciles and publishes daily finance report
  - Principal reviews/approves summary
  - Admin confirms audit log + backup status

### Approval Flow (Maker-Checker)
```text
Maker (Cashier/Admin/Finance) -> Submit Request
-> Checker (Finance/Admin) -> Approve/Reject
-> System Logs Audit Trail -> Principal Visibility (high-impact items)
```

### Segregation of Duties
- Cashier: can post, cannot self-approve cancellation/adjustment.
- Cashier: can print first-time receipt (`ORIGINAL`) only.
- Reprint (`COPY`) requires reason and must be executed by Finance/Admin authority.
- Finance Officer: can approve finance exceptions, not user-role administration.
- Admin: can manage access, not self-approve critical security changes.
- Principal: approves high-impact outcomes, not transactional input.

### Controls
- Always verify Student ID + amount before submit.
- Reprint reason is mandatory: lost, damaged, parent request, or other.
- Never share account/password.
- Use standard export filename:
  - `SchoolCode_ReportType_YYYYMMDD_Role.ext`
- Check audit trail daily for cancellations, role changes, and config edits.
- Logout after shift.

---

### Bahasa Indonesia (Satu Halaman)

## Sistem Manajemen Keuangan Sekolah - SOP Operasional Harian Ringkas

### Peran
- Admin: akses sistem, master data, pengawasan keamanan/audit
- Staff: dukungan operasional umum (tanpa otoritas kuitansi terkontrol)
- Kasir: input pembayaran, cetak pertama kuitansi, setoran harian
- Petugas Keuangan: rekonsiliasi, persetujuan, laporan keuangan
- Kepala Sekolah: review eksekutif dan persetujuan

### Alur Waktu Harian
- Pagi:
  - Admin: cek kesehatan sistem, user, hak akses, backup
  - Kasir: verifikasi tanggal/sesi, saldo awal, urutan kuitansi
  - Petugas Keuangan: cek exception/KPI, status invoice
  - Kepala Sekolah: cek dashboard dan alert risiko
- Jam Operasional:
  - Kasir input pembayaran dan cetak kuitansi
  - Petugas Keuangan review exception, setujui/tolak permintaan
  - Admin bantu perubahan akses/master data (yang disetujui)
  - Kepala Sekolah review tren dan ringkasan laporan
- Akhir Hari:
  - Kasir kirim setoran/settlement
  - Petugas Keuangan rekonsiliasi dan terbitkan laporan harian
  - Kepala Sekolah review/setujui ringkasan
  - Admin konfirmasi audit log + status backup

### Alur Persetujuan (Maker-Checker)
```text
Maker (Kasir/Admin/Keuangan) -> Ajukan Permintaan
-> Checker (Keuangan/Admin) -> Setujui/Tolak
-> Sistem Catat Audit Trail -> Visibilitas Kepala Sekolah (kasus berdampak tinggi)
```

### Pemisahan Tugas (SoD)
- Kasir: boleh input, tidak boleh menyetujui sendiri pembatalan/penyesuaian.
- Kasir: hanya boleh cetak kuitansi pertama (`ORIGINAL`).
- Reprint (`COPY`) wajib alasan dan dilakukan oleh otoritas Keuangan/Admin.
- Petugas Keuangan: boleh menyetujui exception keuangan, tidak kelola role user.
- Admin: boleh kelola akses, tidak self-approve perubahan keamanan kritikal.
- Kepala Sekolah: menyetujui keputusan berdampak tinggi, bukan input transaksi.

### Kontrol
- Selalu cek NIS/NISN + nominal sebelum simpan.
- Alasan reprint wajib diisi: hilang, rusak, permintaan orang tua, atau lainnya.
- Dilarang berbagi akun/password.
- Gunakan format nama file ekspor:
  - `KodeSekolah_JenisLaporan_YYYYMMDD_Role.ext`
- Cek audit trail harian untuk pembatalan, perubahan role, dan perubahan konfigurasi.
- Logout setelah selesai.

---

## Version 2: Detailed SOP Annex

### English (Detailed)

## A. Role-Based Daily Operations

### 1) Admin
#### Role Overview
- Purpose: Maintain system readiness, access control, and governance.
- Main responsibilities:
  - Manage users, roles, and permissions
  - Maintain master data and settings
  - Monitor audit logs, security alerts, and backup status

#### Daily Workflow
- Morning:
  1. Login and check dashboard/system health.
  2. Verify pending user/role requests.
  3. Confirm prior-night backup status.
- Operational hours:
  1. Process approved user/role changes.
  2. Maintain master data updates with ticket/reference.
  3. Support issue resolution with Finance/Cashier.
- End-of-day:
  1. Review critical audit events.
  2. Confirm backup queue/completion.
  3. Logout.

#### Proper Application Use
- Navigation flow: Dashboard -> Users/Roles -> Master Data -> Settings -> Audit
- Menus: Users, Roles/Permissions, Master Data, Settings, Audit, Backup
- Input standards:
  - Unique valid email for users
  - Role assignment follows least privilege
  - Use consistent naming for classes/fees/categories
- Common mistakes:
  - Over-permission assignment
  - Changing fee settings without effective date
  - Missing change reference number

#### Retrieve Reports/Information
1. Go to Reports -> System/Audit.
2. Set filters: date range, actor, module, action type.
3. Preview and verify.
4. Export PDF/Excel/CSV.
5. Naming: `SMA01_AuditLog_YYYYMMDD_Admin.xlsx`

#### Controls & Best Practices
- Apply maker-checker for sensitive changes.
- Enforce password hygiene and account ownership.
- Review audit trail daily.
- Confirm backup integrity awareness each day.

### 2) Cashier
#### Role Overview
- Purpose: Accurately post payments and issue receipts.
- Main responsibilities:
  - Record payment transactions
  - Print first-time receipts (`ORIGINAL`)
  - Submit reprint request with reason when copy is needed
  - Submit end-of-day settlement

#### Daily Workflow
- Morning:
  1. Login and verify session date.
  2. Check opening balance and receipt sequence.
- Operational hours:
  1. Search student by official ID.
  2. Select due invoices/fees.
  3. Input amount and payment method.
  4. Save, then print receipt.
  5. Escalate correction requests if mistakes occur.
- End-of-day:
  1. Reconcile totals (cash/non-cash) with system.
  2. Submit settlement with variance notes.
  3. Export daily logs and logout.

#### Proper Application Use
- Navigation flow: Dashboard -> Transactions -> Receipts -> Settlements -> Reports
- Menus: Transactions, Receipts, Settlements, Reports
- Input standards:
  - Mandatory student ID match
  - Amount and payment method required
  - Notes required for partial/exception entries
- Common mistakes:
  - Wrong student selection
  - Duplicate posting
  - Not validating totals before save

#### Retrieve Reports/Information
1. Open Reports -> Daily Transactions/Receipts.
2. Apply filters: date, cashier, method, class.
3. Cross-check totals with settlement screen.
4. Export PDF/Excel/CSV.
5. Naming:
  - `SMA01_DailyCash_YYYYMMDD_Cashier.pdf`
  - `SMA01_ReceiptLog_YYYYMMDD_Cashier.xlsx`

#### Controls & Best Practices
- Two-point check: student ID + amount.
- Never share account credentials.
- Reprint is not self-authorized for cashier; escalate to Finance/Admin with reason.
- Finish settlement before logout.

### 2a) Staff
#### Role Overview
- Purpose: Support daily operations without controlled receipt authority.
- Main responsibilities:
  - Assist data entry and non-critical operations
  - Coordinate corrections with Cashier/Admin
  - Escalate reprint requests to authorized roles

#### Controls & Best Practices
- Do not execute receipt reprint directly.
- Keep maker-checker references for correction requests.

### 3) Finance Officer
#### Role Overview
- Purpose: Ensure financial control, reconciliation, and reporting quality.
- Main responsibilities:
  - Manage invoice cycles
  - Approve/reject exceptions
  - Reconcile daily financial data
  - Publish formal finance reports

#### Daily Workflow
- Morning:
  1. Review KPI dashboard and exceptions queue.
  2. Validate invoice generation status.
- Operational hours:
  1. Review cancellation/adjustment requests.
  2. Reconcile settlements with transactions.
  3. Coordinate with cashier/admin for corrections.
- End-of-day:
  1. Final reconciliation and variance notes.
  2. Generate and publish daily finance pack.
  3. Logout.

#### Proper Application Use
- Navigation flow: Dashboard -> Invoices -> Transactions -> Settlements -> Reports
- Menus: Invoices, Transactions, Settlements, Reports
- Input standards:
  - Use approved fee codes and periods
  - Adjustment requires reason and reference
  - Reconciliation must match settlement totals
- Common mistakes:
  - Approving without evidence
  - Running reports before settlement close
  - Mixing period parameters

#### Retrieve Reports/Information
1. Open Reports: Daily, Monthly, Arrears, Collections.
2. Set filters: date range, class/grade, fee type, channel, status.
3. Validate totals against reconciliation.
4. Export PDF/Excel/CSV.
5. Naming:
  - `SMA01_FinanceDaily_YYYYMMDD_FO.xlsx`
  - `SMA01_Arrears_YYYYMMDD_FO.csv`

#### Controls & Best Practices
- Enforce maker-checker for exceptions.
- Reconcile system vs settlement/bank records daily.
- Keep support evidence for all adjustments.
- Review finance-related audit trail daily.

### 4) School Principal
#### Role Overview
- Purpose: Executive oversight and approval based on financial outcomes.
- Main responsibilities:
  - Review financial performance and arrears trends
  - Approve high-impact actions
  - Ensure governance/compliance visibility

#### Daily Workflow
- Morning:
  1. Review executive dashboard KPIs.
  2. Check risk alerts and anomalies.
- Operational hours:
  1. Review summaries by class/period.
  2. Request follow-up actions from Finance/Admin.
- End-of-day:
  1. Review daily executive summary.
  2. Approve/comment and logout.

#### Proper Application Use
- Navigation flow: Dashboard -> Reports -> Audit Summary -> Notifications
- Menus: Dashboard, Reports, Audit (view), Notifications
- Input standards:
  - Use official periods and approved filters
  - Add clear comments on approvals/rejections
- Common mistakes:
  - Decision based on unfiltered data
  - Using draft report for formal approval

#### Retrieve Reports/Information
1. Open Reports -> Executive Daily/Monthly/Arrears/Trends.
2. Set parameters: period, grade, fee type, payment status.
3. Export PDF/Excel/CSV.
4. Naming:
  - `SMA01_ExecDaily_YYYYMMDD_Principal.pdf`
  - `SMA01_CollectionTrend_YYYYMM_Principal.xlsx`

#### Controls & Best Practices
- Validate trends vs prior day/month baselines.
- Require rationale for major variances.
- Protect sensitive exports in restricted storage.

## B. SOP Checklists

### Admin Checklist
- [ ] Review system health and alerts
- [ ] Process approved access requests
- [ ] Validate and apply approved master data changes
- [ ] Check backup and audit logs
- [ ] Logout

### Cashier Checklist
- [ ] Verify session date/opening balance
- [ ] Post transactions and print receipts
- [ ] Flag exceptions immediately
- [ ] Complete reconciliation and settlement
- [ ] Logout

### Finance Officer Checklist
- [ ] Review exception queue
- [ ] Reconcile all totals
- [ ] Approve/reject requests with notes
- [ ] Publish daily reports
- [ ] Logout

### School Principal Checklist
- [ ] Review KPI dashboard
- [ ] Check summary and exception reports
- [ ] Approve/comment high-impact items
- [ ] Logout

## C. Approval Flow Between Roles

| Activity | Maker | Approver/Checker | Visibility |
|---|---|---|---|
| User/Role changes | Admin | Admin Lead/Principal (policy-based) | Principal |
| Fee/master data changes | Admin/Finance | Finance/Principal (policy-based) | Principal |
| Payment posting | Cashier | Finance (reconciliation) | Principal (summary) |
| Reprint/void/cancel | Cashier/Staff/Finance (request) | Finance/Admin (execute with mandatory reason) | Admin + Principal |
| Daily report release | Finance | Principal | Authorized roles |

## D. Segregation of Duties (SoD)
- Cashier records transactions, but does not self-approve exceptions.
- Cashier prints first receipt only; copy/reprint is restricted.
- Finance approves financial exceptions, but does not manage user roles.
- Admin manages access/configuration, but should not self-approve critical changes.
- Principal approves high-impact results, not transactional entries.

## E. ASCII Flow Diagrams

### 1) Payment to Reporting
```text
Cashier -> Record Payment -> Print Receipt -> Submit Settlement
   |                                |
   v                                v
Finance Officer -> Reconcile -> Approve Exceptions -> Generate Daily Report
   |
   v
Principal -> Review Summary -> Approve/Comment
   |
   v
Admin -> Monitor Audit/Access/Backup
```

### 2) Change Request Approval
```text
Request Raised
   |
   v
Maker (Admin/Finance/Cashier) -> Submit with reason/reference
   |
   v
Checker (Finance/Admin) -> Approve or Reject
   |
   +--> Reject -> Return to Maker with note
   |
   v
Apply Change -> Audit Trail Logged -> Principal Visibility (high-impact)
```

## F. Role Mapping Matrix

### English

| Role | Primary Scope | Can Print First Receipt | Can Reprint Receipt | Reprint Reason Required | Approval/Checker Authority |
|---|---|---|---|---|---|
| Admin TU | Master data + finance operations | Yes | Yes | Yes | Yes |
| Staff | Operational support | No | No | N/A | No |
| Cashier | Payment posting counter | Yes | No | N/A | No |
| Bendahara (Finance) | Finance control + reconciliation | Yes | Yes | Yes | Yes |
| Kepala Sekolah | Oversight/reporting | No | No | N/A | Policy-level only |
| Super Admin | System-wide governance | Yes | Yes | Yes | Yes |

### Bahasa Indonesia

| Role | Cakupan Utama | Boleh Cetak Pertama | Boleh Reprint | Alasan Reprint Wajib | Otoritas Approve/Checker |
|---|---|---|---|---|---|
| Admin TU | Master data + operasional keuangan | Ya | Ya | Ya | Ya |
| Staff | Dukungan operasional | Tidak | Tidak | N/A | Tidak |
| Kasir | Loket input pembayaran | Ya | Tidak | N/A | Tidak |
| Bendahara | Kontrol keuangan + rekonsiliasi | Ya | Ya | Ya | Ya |
| Kepala Sekolah | Pengawasan/laporan | Tidak | Tidak | N/A | Hanya tingkat kebijakan |
| Super Admin | Tata kelola lintas sistem | Ya | Ya | Ya | Ya |

---

### Bahasa Indonesia (Detail)

## A. Operasional Harian Berbasis Peran

### 1) Admin
#### Ringkasan Peran
- Tujuan: Menjaga kesiapan sistem, kontrol akses, dan tata kelola.
- Tanggung jawab utama:
  - Kelola user, role, dan permission
  - Kelola master data dan pengaturan
  - Pantau audit log, alert keamanan, dan backup

#### Alur Kerja Harian
- Pagi:
  1. Login dan cek dashboard/kesehatan sistem.
  2. Verifikasi permintaan user/role.
  3. Konfirmasi status backup malam sebelumnya.
- Jam operasional:
  1. Proses perubahan user/role yang disetujui.
  2. Update master data berdasarkan tiket/referensi.
  3. Dukung penyelesaian isu dengan tim Keuangan/Kasir.
- Akhir hari:
  1. Review audit event kritikal.
  2. Konfirmasi backup berjalan/sukses.
  3. Logout.

#### Cara Penggunaan Aplikasi
- Alur navigasi: Dashboard -> User/Role -> Master Data -> Settings -> Audit
- Menu: Users, Roles/Permissions, Master Data, Settings, Audit, Backup
- Standar input:
  - Email user harus unik dan valid
  - Pemberian role mengikuti prinsip least privilege
  - Konsisten dalam penamaan kelas/biaya/kategori
- Kesalahan umum:
  - Memberi akses berlebihan
  - Ubah pengaturan biaya tanpa tanggal efektif
  - Tidak mencatat referensi perubahan

#### Cara Ambil Laporan/Informasi
1. Buka Laporan -> Sistem/Audit.
2. Set filter: rentang tanggal, pelaku, modul, jenis aksi.
3. Preview dan verifikasi.
4. Ekspor PDF/Excel/CSV.
5. Penamaan: `SMA01_AuditLog_YYYYMMDD_Admin.xlsx`

#### Kontrol & Praktik Terbaik
- Terapkan maker-checker untuk perubahan sensitif.
- Terapkan disiplin password dan kepemilikan akun.
- Review audit trail setiap hari.
- Cek status/integritas backup setiap hari.

### 2) Kasir
#### Ringkasan Peran
- Tujuan: Input pembayaran akurat dan penerbitan kuitansi.
- Tanggung jawab utama:
  - Input transaksi pembayaran
  - Cetak kuitansi pertama (`ORIGINAL`)
  - Ajukan permintaan reprint bila dibutuhkan
  - Kirim settlement akhir hari

#### Alur Kerja Harian
- Pagi:
  1. Login dan verifikasi tanggal sesi.
  2. Cek saldo awal dan urutan kuitansi.
- Jam operasional:
  1. Cari siswa dengan ID resmi.
  2. Pilih tagihan yang jatuh tempo.
  3. Input nominal dan metode pembayaran.
  4. Simpan lalu cetak kuitansi.
  5. Ajukan koreksi jika ada kesalahan.
- Akhir hari:
  1. Rekonsiliasi total tunai/non-tunai dengan sistem.
  2. Kirim settlement dengan catatan selisih.
  3. Ekspor log harian dan logout.

#### Cara Penggunaan Aplikasi
- Alur navigasi: Dashboard -> Transaksi -> Kuitansi -> Settlement -> Laporan
- Menu: Transactions, Receipts, Settlements, Reports
- Standar input:
  - ID siswa wajib sesuai
  - Nominal dan metode bayar wajib diisi
  - Catatan wajib untuk transaksi parsial/exception
- Kesalahan umum:
  - Salah pilih siswa
  - Input transaksi ganda
  - Tidak cek total sebelum simpan

#### Cara Ambil Laporan/Informasi
1. Buka Laporan -> Transaksi Harian/Kuitansi.
2. Filter: tanggal, kasir, metode bayar, kelas.
3. Cocokkan total dengan layar settlement.
4. Ekspor PDF/Excel/CSV.
5. Penamaan:
  - `SMA01_KasHarian_YYYYMMDD_Kasir.pdf`
  - `SMA01_LogKuitansi_YYYYMMDD_Kasir.xlsx`

#### Kontrol & Praktik Terbaik
- Cek dua titik: ID siswa + nominal.
- Jangan pernah berbagi akun.
- Kasir tidak boleh reprint sendiri; ajukan ke Keuangan/Admin dengan alasan.
- Selesaikan settlement sebelum logout.

### 2a) Staff
#### Ringkasan Peran
- Tujuan: Mendukung operasional harian tanpa otoritas kuitansi terkontrol.
- Tanggung jawab utama:
  - Bantu input data dan tugas operasional non-kritis
  - Koordinasi koreksi transaksi dengan Kasir/Admin
  - Eskalasi kebutuhan reprint ke role berwenang

#### Kontrol & Praktik Terbaik
- Tidak melakukan reprint langsung.
- Gunakan referensi maker-checker untuk koreksi.

### 3) Petugas Keuangan
#### Ringkasan Peran
- Tujuan: Menjamin kontrol keuangan, rekonsiliasi, dan kualitas laporan.
- Tanggung jawab utama:
  - Kelola siklus invoice
  - Setujui/tolak exception
  - Rekonsiliasi harian
  - Terbitkan laporan resmi

#### Alur Kerja Harian
- Pagi:
  1. Review KPI dan antrean exception.
  2. Verifikasi status generate invoice.
- Jam operasional:
  1. Review permintaan pembatalan/penyesuaian.
  2. Rekonsiliasi settlement vs transaksi.
  3. Koordinasi perbaikan data dengan kasir/admin.
- Akhir hari:
  1. Rekonsiliasi final dan catatan selisih.
  2. Generate dan terbitkan paket laporan harian.
  3. Logout.

#### Cara Penggunaan Aplikasi
- Alur navigasi: Dashboard -> Invoice -> Transaksi -> Settlement -> Laporan
- Menu: Invoices, Transactions, Settlements, Reports
- Standar input:
  - Gunakan kode biaya dan periode yang disetujui
  - Penyesuaian wajib alasan dan referensi
  - Rekonsiliasi wajib cocok dengan settlement
- Kesalahan umum:
  - Menyetujui tanpa bukti pendukung
  - Menjalankan laporan sebelum settlement tutup
  - Mencampur parameter periode

#### Cara Ambil Laporan/Informasi
1. Buka Laporan: Harian, Bulanan, Tunggakan, Koleksi.
2. Set filter: rentang tanggal, kelas/tingkat, jenis biaya, channel, status.
3. Validasi total terhadap hasil rekonsiliasi.
4. Ekspor PDF/Excel/CSV.
5. Penamaan:
  - `SMA01_KeuanganHarian_YYYYMMDD_Keuangan.xlsx`
  - `SMA01_Tunggakan_YYYYMMDD_Keuangan.csv`

#### Kontrol & Praktik Terbaik
- Wajib maker-checker untuk exception.
- Rekonsiliasi sistem vs settlement/bank harian.
- Simpan bukti pendukung untuk semua penyesuaian.
- Review audit trail modul keuangan setiap hari.

### 4) Kepala Sekolah
#### Ringkasan Peran
- Tujuan: Pengawasan eksekutif dan persetujuan berbasis hasil keuangan.
- Tanggung jawab utama:
  - Review performa penerimaan dan tren tunggakan
  - Setujui tindakan berdampak tinggi
  - Pastikan visibilitas tata kelola/kepatuhan

#### Alur Kerja Harian
- Pagi:
  1. Review KPI dashboard eksekutif.
  2. Cek alert risiko dan anomali.
- Jam operasional:
  1. Review ringkasan per kelas/periode.
  2. Minta tindak lanjut ke tim Keuangan/Admin.
- Akhir hari:
  1. Review ringkasan eksekutif harian.
  2. Setujui/beri catatan lalu logout.

#### Cara Penggunaan Aplikasi
- Alur navigasi: Dashboard -> Laporan -> Ringkasan Audit -> Notifikasi
- Menu: Dashboard, Reports, Audit (view), Notifications
- Standar input:
  - Gunakan periode resmi dan filter yang disetujui
  - Berikan komentar jelas pada approval/reject
- Kesalahan umum:
  - Ambil keputusan dari data tanpa filter
  - Gunakan laporan draft untuk persetujuan final

#### Cara Ambil Laporan/Informasi
1. Buka Laporan -> Eksekutif Harian/Bulanan/Tunggakan/Tren.
2. Set parameter: periode, tingkat, jenis biaya, status bayar.
3. Ekspor PDF/Excel/CSV.
4. Penamaan:
  - `SMA01_RingkasanEksekutif_YYYYMMDD_Kepsek.pdf`
  - `SMA01_TrenKoleksi_YYYYMM_Kepsek.xlsx`

#### Kontrol & Praktik Terbaik
- Validasi tren terhadap baseline harian/bulanan.
- Wajib alasan untuk selisih signifikan.
- Simpan file ekspor sensitif di penyimpanan terbatas.

## B. Checklist SOP

### Checklist Admin
- [ ] Cek kesehatan sistem dan alert
- [ ] Proses request akses yang disetujui
- [ ] Validasi dan terapkan perubahan master data
- [ ] Cek backup dan audit log
- [ ] Logout

### Checklist Kasir
- [ ] Verifikasi tanggal sesi/saldo awal
- [ ] Input transaksi dan cetak kuitansi
- [ ] Tandai exception secepatnya
- [ ] Rekonsiliasi dan kirim settlement
- [ ] Logout

### Checklist Petugas Keuangan
- [ ] Review antrean exception
- [ ] Rekonsiliasi seluruh total
- [ ] Setujui/tolak request dengan catatan
- [ ] Terbitkan laporan harian
- [ ] Logout

### Checklist Kepala Sekolah
- [ ] Review KPI dashboard
- [ ] Cek ringkasan dan laporan exception
- [ ] Setujui/beri catatan pada item berdampak tinggi
- [ ] Logout

## C. Alur Persetujuan Antar Peran

| Aktivitas | Maker | Approver/Checker | Visibilitas |
|---|---|---|---|
| Perubahan user/role | Admin | Admin Lead/Kepsek (sesuai kebijakan) | Kepsek |
| Perubahan master data biaya | Admin/Keuangan | Keuangan/Kepsek (sesuai kebijakan) | Kepsek |
| Posting pembayaran | Kasir | Keuangan (rekonsiliasi) | Kepsek (ringkasan) |
| Reprint/void/batal | Kasir/Staff/Keuangan (request) | Keuangan/Admin (eksekusi dengan alasan wajib) | Admin + Kepsek |
| Publikasi laporan harian | Keuangan | Kepsek | Role berwenang |

## D. Pemisahan Tugas (SoD)
- Kasir mencatat transaksi, tetapi tidak boleh self-approve exception.
- Kasir hanya boleh cetak kuitansi pertama; copy/reprint dibatasi.
- Keuangan menyetujui exception keuangan, tetapi tidak kelola role user.
- Admin kelola akses/konfigurasi, tetapi tidak self-approve perubahan kritikal.
- Kepsek menyetujui hasil berdampak tinggi, bukan input transaksi harian.

## E. Diagram Alur ASCII

### 1) Alur Pembayaran ke Laporan
```text
Kasir -> Input Pembayaran -> Cetak Kuitansi -> Kirim Settlement
   |                                 |
   v                                 v
Keuangan -> Rekonsiliasi -> Approve Exception -> Generate Laporan Harian
   |
   v
Kepala Sekolah -> Review Ringkasan -> Setujui/Beri Catatan
   |
   v
Admin -> Monitor Audit/Akses/Backup
```

### 2) Alur Persetujuan Perubahan
```text
Permintaan Dibuat
   |
   v
Maker (Admin/Keuangan/Kasir) -> Submit + alasan/referensi
   |
   v
Checker (Keuangan/Admin) -> Setujui atau Tolak
   |
   +--> Tolak -> Kembali ke Maker dengan catatan
   |
   v
Perubahan Diterapkan -> Audit Trail Tercatat -> Visibilitas Kepsek (high-impact)
```
