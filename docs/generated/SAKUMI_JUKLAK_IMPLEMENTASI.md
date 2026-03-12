# SAKUMI - Petunjuk Pelaksanaan (Juklak) Implementasi

## 1. Role dan Permission

### Daftar Role

| Role | Kode | Deskripsi |
|------|------|-----------|
| Super Admin | `super_admin` | Akses penuh seluruh unit dan fitur |
| Admin TU MI | `admin_tu_mi` | Admin Tata Usaha unit MI |
| Admin TU RA | `admin_tu_ra` | Admin Tata Usaha unit RA |
| Admin TU DTA | `admin_tu_dta` | Admin Tata Usaha unit DTA |
| Bendahara | `bendahara` | Pengelola keuangan |
| Kepala Sekolah | `kepala_sekolah` | Pengawas dan approver |
| Operator TU | `operator_tu` | Staff administrasi |
| Auditor | `auditor` | Pemeriksa keuangan (read-only) |
| Cashier | `cashier` | Kasir (transaksi saja) |

### Matriks Akses per Modul

| Modul | Super Admin | Admin TU | Bendahara | Kepsek | Operator | Auditor |
|-------|:-----------:|:--------:|:---------:|:------:|:--------:|:-------:|
| Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Master Students | ✅ | ✅ | ❌ | ❌ | ✅ | ❌ |
| Master Fee Types | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Master Fee Matrix | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Invoices | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ (view) |
| Settlements | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ (view) |
| Transactions | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ (view) |
| Reports | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Expenses | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ (view) |
| User Management | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Settings | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

### Permission Granular

Setiap modul memiliki permission granular:
- `*.view` — Lihat data
- `*.create` — Buat data baru
- `*.edit` — Edit data
- `*.delete` — Hapus data
- `*.cancel` — Batalkan data
- `*.void` — Void data (khusus settlement)

**Permission khusus:**
- `invoices.cancel_paid` — Cancel invoice yang sudah dibayar
- `invoices.generate` — Generate invoice batch
- `invoices.print` — Cetak invoice
- `settlements.void` — Void settlement
- `transactions.expense.create` — Buat transaksi pengeluaran
- `receipts.print` — Cetak kuitansi
- `expenses.approve` — Approve pengeluaran
- `expenses.budget.manage` — Kelola anggaran
- `bank-reconciliation.manage` — Kelola rekonsiliasi bank
- `bank-reconciliation.close` — Tutup sesi rekonsiliasi

---

## 2. Tanggung Jawab per Role

### Admin TU (admin_tu_mi / admin_tu_ra / admin_tu_dta)
- Mengelola data master siswa (CRUD, import, export)
- Mengelola kelas dan kategori siswa
- Membuat dan mengelola invoice
- Menerima pembayaran (settlement)
- Mencetak kuitansi
- Melihat laporan unit

### Bendahara
- Mengelola jenis biaya (fee types) dan tarif (fee matrix)
- Menerima dan memvalidasi pembayaran
- Void settlement jika ada kesalahan
- Cetak dan cetak ulang kuitansi (reprint)
- Mengelola pengeluaran (expenses)
- Approve pengeluaran
- Mengelola rekonsiliasi bank
- Generate laporan keuangan
- Mengelola anggaran (budget)

### Kepala Sekolah
- Melihat invoice, settlement, dan transaksi
- Monitoring tunggakan siswa
- Melihat seluruh laporan keuangan
- Approve pengeluaran
- Pengawasan operasional keuangan

### Auditor
- Melihat seluruh data keuangan (read-only)
- Memeriksa laporan keuangan
- Memeriksa audit trail (activity log)
- Verifikasi kuitansi
- Tidak bisa membuat/mengubah/menghapus data

### Yayasan
- Menerima laporan dari Kepala Sekolah / Bendahara
- Mereview laporan konsolidasi (jika memiliki akses super_admin)
- Evaluasi kinerja keuangan sekolah

---

## 3. Kebijakan Validasi Keuangan

### 3.1 Validasi Invoice
- Invoice tidak bisa di-hard delete (hanya cancel)
- Invoice yang di-cancel tidak bisa digunakan kembali
- Kewajiban yang sudah di-invoice tidak bisa di-invoice ulang (sampai invoice di-cancel)
- Nomor invoice otomatis dan unik per unit per tahun

### 3.2 Validasi Settlement
- Settlement tidak bisa di-hard delete (hanya void/cancel)
- Alokasi tidak boleh melebihi sisa tagihan invoice
- Total alokasi tidak boleh melebihi total pembayaran
- Alokasi hanya boleh ke invoice milik siswa yang sama (BR-07)
- Concurrent access dilindungi oleh database lock (`lockForUpdate`)

### 3.3 Validasi Transaction
- Transaction tidak bisa di-edit (untuk menjaga audit trail)
- Pembayaran siswa dengan fee bulanan wajib melalui settlement
- Siswa dengan invoice terbuka diarahkan ke settlement
- Transaction expense memerlukan permission terpisah

### 3.4 Validasi Tarif
- Kewajiban yang sudah pernah di-invoice (termasuk cancelled) tidak bisa diubah tarifnya
- Kewajiban yang belum pernah di-invoice bisa diupdate otomatis saat tarif berubah
- Student Fee Mapping override Fee Matrix untuk siswa tertentu

---

## 4. Kebijakan Audit

### 4.1 Activity Log
- Setiap perubahan pada Invoice, Settlement, dan Student dicatat oleh Spatie Activity Log
- Yang dicatat: field yang berubah (`logOnlyDirty`)
- Invoice log: status, paid_amount, total_amount, student_id, dates, notes
- Settlement log: status, amounts, payment_method, reasons

### 4.2 Tracking Perubahan
- Setiap invoice dan settlement mencatat `created_by` dan `updated_by`
- Settlement void mencatat `voided_by`, `voided_at`, `void_reason`
- Settlement cancel mencatat `cancelled_by`, `cancelled_at`, `cancellation_reason`
- `updated_by` otomatis diisi saat update oleh user yang login

### 4.3 Receipt Audit Trail
- Setiap cetak kuitansi dicatat: user, waktu, IP, device
- Cetak ulang wajib alasan
- Hanya role tertentu yang bisa cetak ulang
- Verification code unik untuk verifikasi keaslian

### 4.4 Accounting Engine
- Setiap operasi keuangan menghasilkan jurnal via AccountingEngine
- Idempotency key mencegah duplikasi jurnal
- Reversal otomatis saat void/cancel

---

## 5. Panduan Implementasi untuk Sekolah

### 5.1 Tahap Persiapan

1. **Setup User Accounts**
   - Buat akun untuk setiap penanggung jawab
   - Assign role sesuai tugas
   - Pastikan setiap unit memiliki admin TU

2. **Setup Master Data**
   - Input daftar kelas
   - Input kategori siswa
   - Input daftar jenis biaya
   - Setup fee matrix (tarif per kelas/kategori)

3. **Input Data Siswa**
   - Import dari Excel atau input manual
   - Pastikan kelas dan kategori sudah benar
   - Buat student fee mapping untuk siswa dengan tarif khusus

### 5.2 Tahap Operasional Bulanan

1. **Awal Bulan:**
   - Generate invoice batch untuk bulan berjalan
   - Review hasil generation (jumlah, skip, error)
   - Distribusikan tagihan ke wali murid

2. **Selama Bulan:**
   - Terima pembayaran → buat settlement
   - Cetak kuitansi untuk wali
   - Monitor tunggakan via laporan arrears

3. **Akhir Bulan:**
   - Generate laporan bulanan
   - Rekonsiliasi bank (jika ada pembayaran transfer)
   - Review AR outstanding untuk follow-up tunggakan
   - Generate buku kas

### 5.3 Tahap Awal Tahun Ajaran

1. **Kenaikan Kelas:**
   - Gunakan fitur Promotion Batch untuk naik kelas massal
   - Review dan approve promotion
   - Apply promotion

2. **Update Tarif:**
   - Update fee matrix jika ada perubahan tarif
   - Set `effective_from` dan `effective_to` dengan benar
   - Update student fee mapping jika ada perubahan individual

3. **PSB (Penerimaan Siswa Baru):**
   - Buat periode admission
   - Input data calon siswa (applicant)
   - Proses review → accept/reject → enroll

### 5.4 Training Plan

| Sesi | Target | Materi | Durasi |
|------|--------|--------|--------|
| 1 | Admin TU | Master data, student management | 2 jam |
| 2 | Bendahara | Fee setup, invoice, settlement | 3 jam |
| 3 | Bendahara | Reports, reconciliation, expense | 2 jam |
| 4 | Kepala Sekolah | Dashboard, reports, monitoring | 1 jam |
| 5 | All users | Praktek end-to-end | 2 jam |

### 5.5 Data Migration Checklist

- [ ] Export data siswa dari sistem lama
- [ ] Mapping field ke template SAKUMI
- [ ] Import data siswa
- [ ] Verifikasi jumlah dan kelengkapan data
- [ ] Setup fee types dan fee matrix
- [ ] Input saldo awal (jika ada tunggakan dari sistem lama)
- [ ] Test end-to-end: invoice → settlement → receipt → report
- [ ] Training pengguna
- [ ] Go-live
