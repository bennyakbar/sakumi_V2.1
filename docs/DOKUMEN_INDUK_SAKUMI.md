# DOKUMEN INDUK SISTEM KEUANGAN SEKOLAH (SAKUMI)
**STANDAR TATA KELOLA, OPERASIONAL, DAN KEPATUHAN (AUDIT-LEVEL PROFESSIONAL VER.)**

---

## INFORMASI DOKUMEN
| Atribut | Keterangan |
|---|---|
| **Judul Dokumen** | Dokumen Induk Sistem Administrasi Keuangan Madrasah Ibtidaiyah (SAKUMI) |
| **Versi Dokumen** | 2.0 (Audit-Level Professional) |
| **Tanggal Efektif** | 26 Februari 2026 |
| **Pemilik Sistem** | Yayasan / Manajemen Eksekutif |
| **Klasifikasi** | Internal & Rahasia (Confidential) |
| **Sasaran Pengguna** | Auditor, Super Administrator, Kepala Sekolah, Bendahara |

---

## PERSETUJUAN DAN PENGESAHAN

| Jabatan | Nama Tanggung Jawab | Tanda Tangan & Tanggal |
|---|---|---|
| **Pimpinan Yayasan / Direktur** | __________________ | ______________________ |
| **Kepala Sekolah** | __________________ | ______________________ |
| **Ketua Tim Keuangan (Bendahara)** | __________________ | ______________________ |
| **Auditor Internal / Eksternal** | __________________ | ______________________ |

---

## DAFTAR ISI

1. [BAB I – PENDAHULUAN & KEBIJAKAN UMUM](#bab-i--pendahuluan--kebijakan-umum)
2. [BAB II – ARSITEKTUR & INTEGRITAS DATA (IT GOVERNANCE)](#bab-ii--arsitektur--integritas-data-it-governance)
3. [BAB III – STANDAR OPERASIONAL PROSEDUR (SOP) & MATRIX OTORISASI](#bab-iii--standar-operasional-prosedur-sop--matrix-otorisasi)
4. [BAB IV – PETUNJUK TEKNIS (JUKNIS) & PELAKSANAAN (JUKLAK)](#bab-iv--petunjuk-teknis-juknis--pelaksanaan-juklak)
5. [BAB V – KELANGSUNGAN LAYANAN & MITIGASI BENCANA (BCP/DRP)](#bab-v--kelangsungan-layanan--mitigasi-bencana-bcpdrp)
6. [BAB VI – AUDIT, KEPATUHAN, & PELAPORAN](#bab-vi--audit-kepatuhan--pelaporan)
7. [REFERENSI DOKUMEN TERKAIT](#referensi-dokumen-terkait)

---

## BAB I – PENDAHULUAN & KEBIJAKAN UMUM

### 1.1 Tujuan Dokumen Induk
Dokumen Induk SAKUMI (Sistem Administrasi Keuangan Untuk Madrasah Ibtidaiyah) mengonsolidasikan seluruh aspek tata kelola, kebijakan operasional, pemeliharaan sistem, dan kepatuhan (compliance) ke dalam satu kerangka dokumentasi tingkat-audit (audit-level professional). Dokumen ini menjadi rujukan tunggal (Single Source of Truth) bagi auditor internal/eksternal, manajemen tingkat atas, dan pemangku kepentingan TI dalam menilai keandalan, keamanan, dan akuntabilitas sistem.

### 1.2 Ruang Lingkup Sistem
SAKUMI adalah sistem multi-unit (Mengahandle RA, MI, DTA) yang dirancang untuk penerimaan siswa baru, pengkalkulasian biaya, pembuatan penagihan (Invoice), pencatatan penerimaan (Settlements), pelacakan pengeluaran, rekonsiliasi kas/bank, dan pelaporan keuangan real-time secara immutable dan aman.

### 1.3 Prinsip Keuangan Utama (Zero-Trust & Immutabilitas)
1. **Immutabilitas Data:** Seluruh data transaksi dilarang keras untuk dihapus permanen (*No Hard Delete*). Koreksi hanya dapat melalui mekanisme pembatalan (*voiding/cancellation*) tanpa merusak rekam jejak.
2. **Segregation of Duties (SoD):** Terdapat pemisahan tegas antara eksekutor (pembuat), reviewer (pemeriksa), dan approver (penyetuju) untuk semua manuver keuangan vital. 
3. **Kuitansi Tahan Manipulasi (Deterministic Verification):** Pencetakan kuitansi dilengkapi HMAC-SHA256 untuk memastikan keabsahan transaksi pasca-cetak.

---

## BAB II – ARSITEKTUR & INTEGRITAS DATA (IT GOVERNANCE)

### 2.1 Basis Teknologi dan Infrastruktur
- **Stack Teknologi:** Laravel (Application Layer), PostgreSQL (RDBMS dengan trigger immutabilitas tingkat engine), Tailwind CSS (Presentation), Spatie Permission (RBAC authorizations).
- **Environment:** Penerapan *Production-Grade* dengan pemisahan lingkup yang kaku. Seluruh operasi bersifat *Unit-Scoped* diisolasi menggunakan *global scopes* untuk mencegah resapan data antar-unit operasi (MI, RA, DTA).

### 2.2 Kontrol Integritas & Mekanisme Konkurensi
1. **Pessimistic Locking (`lockForUpdate`):** Saat alokasi dana berlangsung, baris database bersangkutan dikunci secara pesimistik agar tak terjadi *race condition* (menghindari duplikasi pembayaran atau alokasi berlebih).
2. **Database Constraints & Triggers:** Trigger tertanam secara fundamental di RDBMS (PostgreSQL) menghalangi mutasi langsung (`UPDATE`) pada kolom-kolom kritikal seperti `total_amount`, `transaction_date`, `student_id` setelah status transaksi terselesaikan (*completed*).
3. **Atomicity (DB Transactions):** Semua rentetan transaksi mutasi (kuitansi, saldo, ledger) disatukan pada *database transaction isolation block*. Gagal satu, gagal semua (*Rollback*).

### 2.3 Keamanan Sesi & Rate Limiting
- **Session Control:** Idle timeout paksa dalam durasi maksimum 7200 detik (2 jam) guna mencegah *session hijacking*.
- **Rate-Limiting Kritis:** Modul otentikasi (10 attempts/min), Pelaporan (60 requests/min), dan antarmuka umum (120 requests/min) untuk menghindari eskalasi serangan DDoS / *Brute-force*.

---

## BAB III – STANDAR OPERASIONAL PROSEDUR (SOP) & MATRIX OTORISASI

SOP operasional mengendalikan interaksi pengguna sesuai batas koridor jabatannya, menciptakan *Maker-Checker mechanism*.

### 3.1 Klasifikasi Role dan Kewenangan (RBAC)
Terdapat 6 level otorisasi inti:
1. **Super Administrator (SA):** Penanggung jawab penuh pada modul *System Configuration, User Management*, dan manajemen kesehatan operasional dasar (Health Checks). **TIDAK TERLIBAT** secara langsung pada penginputan keuangan.
2. **Bendahara (BDH):** *Approver* untuk pembatalan transaksi, rekonsiliasi Bank, persetujuan biaya pengeluaran, dan reprint kuitansi.
3. **Kepala Sekolah (KS):** Hanya hak observasi (View-Only / Executive Dashboarding).
4. **Admin Tata Usaha (Admin TU):** Eksekutor data master siswa, pembuat tagihan massal, eksekutor settlement.
5. **Operator TU (OP-TU):** Khusus hanya menangani input pendaftaran. Modifikasi dan pembatalan dilarang.
6. **Kasir (Cashier):** Terbatas pada layar input pembayaran akhir dan cetak perdana (Original) kuitansi.
7. **Auditor (AUD):** Hak observasi komprehensif tanpa kewenangan entri, modifikasi, apalagi penghapusan.

### 3.2 Matriks Persetujuan (Approval Matrix)
Setiap manuver yang merepresentasikan *fraud opportunity* dipagari oleh eskalasi vertikal:
| Aktivitas | Pembuat (Maker) | Pemeriksa (Checker/Approver) |
|---|---|---|
| Input transaksi pembayaran | Kasir / Operator TU / Admin TU | Bendahara (Via Daily Reconciliation) |
| Void Settlement | Admin TU / Bendahara | Bendahara / Super Admin |
| Reprint Kuitansi (Copy #N) | Admin TU / Kasir (Pengaju) | Bendahara (Mewajibkan Alasan Valid) |
| Approve Expense | Admin TU / Bendahara (Pengaju)| Bendahara |
| Perubahan Master Data Diskon | Admin TU | Kepala Sekolah |

---

## BAB IV – PETUNJUK TEKNIS (JUKNIS) & PELAKSANAAN (JUKLAK)

Sesuai dokumen turunan, pelaksanaan operasional dijabarkan secara ketat sebagai berikut:

### 4.1 Standar Alur Audit Tagihan dan Penagihan
- **Mekanisme Otomasi:** *Invoice bulanan* (*Arrears*) dibuat secara Batch (idempoten). Sistem mencocokkan `StudentFeeMapping` lalu `FeeMatrix` tanpa menimpa (`OVERWRITE`) record yang telah dilunasi di periode lampau.
- **Batasan Pembatalan:** Tagihan yang telah dialokasikan pembayaran *Partially Paid* atau *Fully Paid* **TIDAK BOLEH** dibatalkan hingga semua rincian Settlement (*ledger*) dicabut / di-*void* terlebih dahulu, menjaga agar nilai akuntansi tetap berimbang.

### 4.2 Pelaksanaan Harian Minimum (SLA)
- **Tutup Buku Harian (EOD):** Sebelum *Day-end*, Kasir dan Admin TU mendokumentasikan penerimaan dan diserahkan kepada Bendahara untuk dicocokkan dengan *Daily Report SAKUMI* sebelum status final.
- **Rekonsiliasi Bank:** Pencocokan (Reconciliation Session) wajib ditutup (*Closed Session*) sehingga berubah menjadi Read-Only secara permanen per periodik pencocokan rekening koran.

---

## BAB V – KELANGSUNGAN LAYANAN & MITIGASI BENCANA (BCP/DRP)

Pengamanan fisik arsip elektronik berada dalam cakupan *Disaster Recovery Plan (DRP)* SAKUMI:

### 5.1 Siklus Backup Produksi
- **Backup Berjenjang:**
  - *Automated Daily Backup* pada *Database* dan *App Files/Storage*, dijadwalkan cron dengan notifikasi ke Telegram/Slack.
  - *Offsite Replication:* Salinan didorong secara otomatis ke repositori Cloud terenkripsi (rclone/S3) untuk memitigasi *Hardware Theft* / *Ransomware*.
- **Rotasi Retensi Data:**
  - Harian (Disimpan 7 Hari terakhir)
  - Mingguan (Disimpan 4 Minggu terakhir)
  - Bulanan (Disimpan 12 Bulan terakhir)

### 5.2 Standar Maintenance (Health & Health Checks)
- Tim IT internal rutin memonitor `/health` *endpoint* setiap hari untuk memastikan: Database Status (OK), Redis Cache (OK), Storage Permission (OK), Message Queue/Workers (OK).

---

## BAB VI – AUDIT, KEPATUHAN, & PELAPORAN

### 6.1 Jejak Aktivitas (*Audit Trail System*)
SAKUMI difasilitasi dengan paket *Activity Log* komprehensif. Setiap tindakan manipulasi data (POST/PUT/PATCH/DELETE), re-print kuitansi, atau eskalasi role tertulis permanen ke dalam tabel observasi. 
- *Kolom Wajib:* `user_id`, `ip_address`, `user_agent`, `payload_before`, `payload_after`, `timestamp`.
- Fitur ekspor (*Export Excel/CSV*) ke dokumen eksternal dirancang khusus untuk memenuhi standar pengerjaan instansi Auditor Eksternal. 

### 6.2 Konvensi Penamaan Ekspor Laporan
Semua laporan finansial memiliki pedoman penamaan *File Export* guna standarisasi laci fisik / pengarsipan:
- `UNITKODE_LaporanHarian_YYYYMMDD_ROLE.xlsx`
- `UNITKODE_StatementSiswa_YYYYMMDD_ROLE.pdf`
- `UNITKODE_AuditLog_YYYYMMDD_ROLE.xlsx`

### 6.3 Tanggap Darurat Integritas Data
Jika diendus *Anomali Finansial* (pembatalan/Void masif di luar prosedur, anomali reprint log), langkah BCP Aktif (*Business Continuity*) mewajibkan:
1. Pembekuan otorisasi User yang dicurigai (Via Super Admin).
2. Penarikan arsip *Audit Log* 7 Hari Terakhir untuk analisis forensik internal.
3. Transparansi kepada Pengurus Eksekutif (Kepala Sekolah) pada kejanggalan Ledger.

---

## REFERENSI DOKUMEN TERKAIT
Dokumen ini secara inheren berkaitan kuat dan dilandaskan pada artefak spesifikasi berikut yang terletak di repositori `/docs/`:
- `SOP_SAKUMI.md` – *Standar Operasional Prosedur*
- `JUKNIS_SAKUMI.md` – *Petunjuk Teknis*
- `JUKLAK_SAKUMI.md` – *Petunjuk Pelaksanaan*
- `OPERATIONAL_HANDBOOK_ID.md` – *Buku Panduan Operasional Basis Teknis*
- `PRODUCTION_BACKUP_SYSTEM.md` – *Sistem Backup Produksi*
- `MAINTENANCE_CHECKLIST.md` – *Daftar Periksa Pemeliharaan IT*
- `SOP_BACKUP_MAINTENANCE.md` – *SOP Pemeliharaan Backup & Restorasi Database*
- `DATABASE_INTEGRITY_AUDIT.md` – *Audit Integritas Database & FK Hardening*
- `QA_FRONTEND_AUDIT.md` – *QA Frontend & Route Integrity Audit*
- `PANDUAN_MIGRASI_APLIKASI.md` – *Dokumentasi Migrasi*

> **Disclaimer:** Segala perubahan terhadap Dokumen Induk ini harus melalui *Board Meeting* dan tercatat pada *Document Revision History* Yayasan.
