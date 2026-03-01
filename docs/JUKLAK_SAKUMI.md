# JUKLAK SAKUMI
## Petunjuk Pelaksanaan — Sistem Keuangan Sekolah (SAKUMI)

**Versi:** 2.0  
**Tanggal:** 26 Februari 2026  
**Jenis Dokumen:** Petunjuk Pelaksanaan (JUKLAK) — Panduan Operasional Per Role

---

## PENDAHULUAN

Petunjuk Pelaksanaan (JUKLAK) ini adalah panduan praktis harian yang dipegang oleh masing-masing pengguna sesuai role-nya. JUKLAK berisi langkah-langkah ringkas, checklist, dan referensi cepat untuk pekerjaan rutin sehari-hari.

> **Hubungan Dokumen:**  
> SOP → kebijakan dan prinsip  
> JUKNIS → cara teknis penggunaan fitur  
> **JUKLAK → langkah pelaksanaan harian per role (dokumen ini)**

---

# JUKLAK-01: SUPER ADMINISTRATOR

**Nama Dokumen:** Petunjuk Pelaksanaan Super Administrator  
**Berlaku untuk:** Pengguna dengan role `super_admin`  
**Dokumen ini adalah panduan harian dan referensi cepat.**

---

## 1. Rutinitas Harian

### Pagi (sebelum operasional dimulai)
| # | Langkah | Menu/Fitur | Keterangan |
|---|---|---|---|
| 1 | Login ke sistem | Halaman Login | Pastikan URL benar |
| 2 | Cek Health Check | `/health` | DB, Storage, Queue, Cache harus OK |
| 3 | Cek status backup malam sebelumnya | Lihat log sistem | Jika gagal → tindak lanjut segera |
| 4 | Review audit log event kritis | Audit Log | Cari: perubahan role, cancel massal |

### Jam Operasional
| # | Langkah | Menu/Fitur |
|---|---|---|
| 5 | Proses permintaan akun baru yang sudah disetujui | Users → Create |
| 6 | Proses perubahan role yang diminta secara resmi | Users → Edit |
| 7 | Bantu Admin TU jika ada isu master data | Master Data |
| 8 | Monitor antrian job (pastikan failed_jobs ≤ 10) | `/health` |

### Akhir Hari
| # | Langkah |
|---|---|
| 9 | Review audit log hari ini |
| 10 | Konfirmasi scheduled backup sudah terjadwal |
| 11 | Logout |

---

## 2. Prosedur Khusus

### Membuat Akun Pengguna Baru
1. Terima permintaan resmi (tertulis) dari pimpinan unit.
2. Buka **Users → Create**.
3. Isi: Nama, Email, Password sementara, Role, Unit, Status aktif.
4. Klik **Save**.
5. Informasikan email + password sementara ke pengguna baru secara langsung.
6. Instruksikan pengguna untuk ganti password saat pertama login.

### Nonaktifkan Akun Pegawai yang Berhenti
1. Terima konfirmasi dari pimpinan.
2. Buka **Users → Edit** akun yang bersangkutan.
3. Hilangkan centang **Is Active**.
4. Klik **Update**.
5. Dokumentasikan tanggal penonaktifan untuk arsip.

---

## 3. Referensi Cepat Akses Menu

| Menu | Jalan Masuk |
|---|---|
| Dashboard | Sidebar → Dashboard |
| Users | Sidebar → Users |
| Settings | Sidebar → Settings |
| Audit Log | Sidebar → Audit |
| Health Check | Browser → `/health` (wajib login) |
| Permanent Delete | Settings → Permanent Delete (hati-hati!) |

---

## 4. Checklist Harian Super Admin

```
TANGGAL: _______________  PARAF: _______________

□ Health Check: DB OK | Storage OK | Queue OK | Cache OK
□ Status backup malam sebelumnya: □ Sukses / □ Gagal (tindak lanjut: ________)
□ Audit log event kritis diperiksa: □ Aman / □ Ada anomali (dicatat: ________)
□ Permintaan akun baru diproses: □ Ada □ Tidak ada
□ Monitor failed_jobs ≤ 10: □ OK / □ Melebihi (tindak lanjut: ________)
□ Logout sebelum meninggalkan workstation
```

---

# JUKLAK-02: ADMIN TATA USAHA

**Nama Dokumen:** Petunjuk Pelaksanaan Admin Tata Usaha  
**Berlaku untuk:** `admin_tu_mi` / `admin_tu_ra` / `admin_tu_dta`  
**Dokumen ini adalah panduan harian dan referensi cepat.**

---

## 1. Rutinitas Harian

### Pagi
| # | Langkah | Menu/Fitur |
|---|---|---|
| 1 | Login | Login |
| 2 | Cek Dashboard — lihat summary hari ini | Dashboard |
| 3 | Buka Arrears Report — catat siswa aging tertinggi | Reports → Arrears |

### Jam Operasional — Proses Pembayaran Siswa

**Langkah standar setiap kali ada pembayaran masuk:**

| # | Langkah | Menu/Fitur | Catatan Penting |
|---|---|---|---|
| 1 | Cari siswa berdasarkan nama/NIS | Invoices | Pastikan nama BENAR |
| 2 | Pilih invoice yang dibayar | Invoice Detail | Cek outstanding dan due date |
| 3 | Klik Pay Now → masuk form Settlement | Settlements → Create | — |
| 4 | Isi nominal ≤ outstanding | Form Settlement | JANGAN isi lebih dari outstanding |
| 5 | Pilih metode bayar | Form Settlement | Tunai / Transfer / QRIS |
| 6 | Klik Save Settlement | — | Sistem validasi otomatis |
| 7 | Cetak Kwitansi ORIGINAL | Receipts → Print | Serahkan kepada pembayar |
| 8 | Verifikasi outstanding berkurang | Invoice Detail | Pastikan sudah terkurangi |

### Akhir Hari
| # | Langkah | Menu/Fitur |
|---|---|---|
| 9 | Buka Daily Report hari ini | Reports → Daily |
| 10 | Cocokkan total dengan kas/bank fisik | — |
| 11 | Catat jika ada selisih | — |
| 12 | Logout | — |

---

## 2. Prosedur Khusus

### Jika Ada Kesalahan Transaksi
> ⚠ **JANGAN** mencoba memperbaiki dengan input ulang tanpa membatalkan yang lama.
1. **STOP** — jangan input transaksi tambahan.
2. Catat: nomor transaksi, nama siswa, nominal yang salah.
3. Batalkan transaksi: buka detail transaksi → klik **Cancel** → isi alasan → Confirm.
4. Buat transaksi baru yang benar.
5. Informasikan kepada Bendahara.

### Jika Ada Permintaan Reprint Kwitansi
1. Tanya kepada pemohon: alasan reprint apa? (hilang/rusak/permintaan ortu).
2. Buka detail transaksi yang kwitansinya ingin dicetak ulang.
3. Klik **Reprint Receipt** → pilih alasan dari dropdown → Confirm.
4. Kwitansi tercetak dengan status **COPY – Reprint #N**.
5. Audit log otomatis tercatat.

### Menambah Siswa Baru
1. Pastikan: NIS tidak duplikat, kelas dan kategori sudah ada di master data.
2. Buka **Master → Students → Create** → isi semua data → Save.

### Generate Invoice Bulanan
> Dilakukan di awal setiap bulan atau sesuai jadwal.
1. Buka **Invoices → Generate**.
2. Pilih periode (bulan/tahun), jenis biaya, kelas (opsional).
3. Cek preview: jumlah siswa dan total nominal.
4. Klik **Run Generation**.

---

## 3. Referensi Cepat Akses Menu

| Kebutuhan | Jalan Pintas |
|---|---|
| Input pembayaran baru | Invoices → [pilih siswa] → Pay Now |
| Cek tunggakan siswa | Reports → Arrears |
| Daftar siswa | Master → Students |
| Laporan hari ini | Reports → Daily |
| Cetak ulang kwitansi | Transactions → [pilih] → Reprint |
| Import siswa massal | Master → Students → Import |

---

## 4. Checklist Harian Admin TU

```
TANGGAL: _______________  UNIT: _______________  PARAF: _______________

PAGI:
□ Login dan cek Dashboard
□ Buka Arrears Report — catat 3 siswa aging tertinggi untuk follow-up

SIANG (Jam Operasional):
□ Semua pembayaran hari ini sudah diinput ke Settlement
□ Kwitansi ORIGINAL sudah dicetak dan diserahkan untuk setiap pembayaran
□ Tidak ada settlement gagal atau duplikat
□ Outstanding invoice berkurang sesuai pembayaran

SORE (Akhir Hari):
□ Daily Report hari ini sesuai dengan kas/bank fisik
□ Selisih (jika ada) sudah didokumentasikan: Rp _______________
□ Informasikan kepada Bendahara jika ada selisih atau anomali
□ Logout sebelum meninggalkan workstation
```

---

## 5. Hal yang DILARANG (Admin TU)
- ❌ Edit data keuangan historis langsung (harus cancel + buat ulang)
- ❌ Berbagi akun/password
- ❌ Menyetujui setiap pembatalan tanpa alasan jelas
- ❌ Input pembayaran hari esok dengan tanggal hari ini untuk mengejar target

---

# JUKLAK-03: BENDAHARA

**Nama Dokumen:** Petunjuk Pelaksanaan Bendahara  
**Berlaku untuk:** `bendahara`  
**Dokumen ini adalah panduan harian dan referensi cepat.**

---

## 1. Rutinitas Harian

### Pagi
| # | Langkah | Menu/Fitur |
|---|---|---|
| 1 | Login | Login |
| 2 | Buka Daily Report kemarin | Reports → Daily |
| 3 | Verifikasi total penerimaan kemarin vs mutasi bank | Bank Reconciliation |
| 4 | Review settlement bernilai besar/tidak wajar | Settlements |

### Jam Operasional
| # | Langkah | Menu/Fitur |
|---|---|---|
| 5 | Proses permintaan void/cancel dari Admin TU | Settlements → Void |
| 6 | Verifikasi dan approve expense yang pending | Expenses → Approve |
| 7 | Lakukan matching bank reconciliation (jika ada data import) | Bank Reconciliation |
| 8 | Tersedia untuk konsultasi Admin TU atas isu keuangan | — |

### Akhir Hari
| # | Langkah | Menu/Fitur |
|---|---|---|
| 9 | Rekonsiliasi final: total settlement vs mutasi bank | Bank Reconciliation |
| 10 | Export dan arsipkan Daily Report | Reports → Daily → Export |
| 11 | Logout | — |

---

## 2. Prosedur Khusus

### Void Settlement
1. Terima permintaan void dari Admin TU/Kasir (disertai alasan dan bukti).
2. Buka **Settlements** → cari settlement yang bersangkutan.
3. Klik **Void** → isi alasan → Confirm.
4. Verifikasi bahwa outstanding invoice sudah kembali bertambah.
5. Dokumen persetujuan void untuk arsip.

### Rekonsiliasi Bank Bulanan
1. Export mutasi bank dari internet banking periode bulan lalu.
2. Buka **Bank Reconciliation → New Session**.
3. Set periode, upload file mutasi bank.
4. Match setiap baris mutasi dengan transaksi di sistem.
5. Investigasi item unmatched (bisa jadi ada transaksi yang belum dicatat atau ada penerimaan lain).
6. Tutup sesi dan cetak laporan rekonsiliasi.

### Laporan Bulanan untuk Yayasan/Kepala Sekolah
1. Buka **Reports → Monthly** → pilih bulan.
2. Export dalam format Excel DAN PDF.
3. Siapkan juga: Arrears Report dan AR Outstanding Report.
4. Nama file: `UNITKODE_LaporanBulanan_YYYYMM_BDH.xlsx`
5. Serahkan kepada Kepala Sekolah untuk review.

---

## 3. Referensi Cepat Akses Menu

| Kebutuhan | Jalan Pintas |
|---|---|
| Rekonsiliasi bank | Bank Reconciliation |
| Approve expense | Expenses |
| Void settlement | Settlements → [pilih] → Void |
| Laporan tunggakan mingguan | Reports → Arrears |
| Laporan bulanan | Reports → Monthly |
| AR Outstanding | Reports → AR Outstanding |
| Audit trail keuangan | Audit Log (filter modul keuangan) |

---

## 4. Checklist Harian Bendahara

```
TANGGAL: _______________  PARAF: _______________

PAGI:
□ Daily Report kemarin diverifikasi
□ Total penerimaan kemarin cocok dengan mutasi bank: □ Ya / □ Selisih (Rp _______)
□ Review settlement besar/tidak wajar: □ Aman / □ Ada perlu investigasi

SIANG:
□ Permintaan void/cancel diproses: □ Ada (___ item) / □ Tidak ada
□ Expense pending diapprove/ditolak: □ Ada (___ item) / □ Tidak ada
□ Rekonsiliasi bank (jika ada)

SORE:
□ Daily Report hari ini berhasil diarsipkan
□ Tidak ada outstanding settlement yang belum terverifikasi
□ Logout sebelum meninggalkan workstation
```

## 5. Checklist Mingguan Bendahara

```
MINGGU KE: ___  BULAN: _______________  PARAF: _______________

□ Arrears Report dianalisis: bucket 0-30, 31-60, 61-90, >90 hari
□ Siswa aging >90 hari sudah dikomunikasikan ke Admin TU dan Kepala Sekolah
□ Tidak ada overpayment (outstanding negatif)
□ Seluruh pembatalan settlement memiliki alasan yang valid dan tercatat
□ Rekonsiliasi bank mingguan selesai
```

## 6. Checklist Bulanan Bendahara

```
BULAN: _______________  PARAF: _______________

□ Monthly Report diverifikasi dan diarsipkan
□ Total penerimaan bulan ini: Rp _______________
□ Total outstanding akhir bulan: Rp _______________
□ Laporan sudah diserahkan ke Kepala Sekolah
□ Fee matrix sudah direview (perlu update? □ Ya / □ Tidak)
□ Rekonsiliasi bank bulanan lengkap
□ Collection rate bulan ini: _____%
```

---

# JUKLAK-04: KEPALA SEKOLAH

**Nama Dokumen:** Petunjuk Pelaksanaan Kepala Sekolah  
**Berlaku untuk:** `kepala_sekolah`  
**Dokumen ini adalah panduan pengawasan dan referensi laporan.**

---

> **Catatan Penting:** Kepala Sekolah memiliki akses **view-only** (hanya baca). Tidak dapat membuat, mengubah, atau menghapus data.

---

## 1. Rutinitas Harian (±15 menit)

| # | Langkah | Menu/Fitur | Frekuensi |
|---|---|---|---|
| 1 | Login | Login | Harian |
| 2 | Cek ringkasan Dashboard | Dashboard | Harian |
| 3 | Perhatikan anomali (penerimaan rendah, pembatalan tinggi) | Dashboard | Harian |
| 4 | Instruksikan tindak lanjut ke Bendahara/Admin TU jika perlu | — | Sesuai kebutuhan |
| 5 | Logout | — | Harian |

---

## 2. Rutinitas Mingguan (±30 menit)

| # | Langkah | Menu/Fitur |
|---|---|---|
| 1 | Buka Arrears Report | Reports → Arrears |
| 2 | Filter per kelas — identifikasi pola tunggakan | Filter Kelas |
| 3 | Cek Student Statement untuk siswa dengan aging terbesar | Reports → Student Statement |
| 4 | Beri instruksi kepada Bendahara/Admin TU untuk follow-up | Lisan/tertulis |

---

## 3. Rutinitas Bulanan (±1 jam)

| # | Langkah | Menu/Fitur |
|---|---|---|
| 1 | Buka Monthly Report | Reports → Monthly |
| 2 | Review: total penerimaan, trend harian | Monthly Report |
| 3 | Buka Collection Report — cek % invoice terbayar | Reports → Collection |
| 4 | Buka AR Outstanding — cek piutang akhir bulan | Reports → AR Outstanding |
| 5 | Review performa vs bulan lalu — ada peningkatan/penurunan? | Perbandingan |
| 6 | Beri arahan kebijakan penagihan bulan berikutnya | — |
| 7 | Export laporan untuk dokumentasi | Export PDF/Excel |

---

## 4. Interpretasi Laporan

### Dashboard KPI

| Indikator | Kondisi Baik | Perlu Perhatian |
|---|---|---|
| Total Outstanding | Menurun bulan ke bulan | Naik signifikan |
| Invoice Overdue | Jumlah kecil | Bertambah banyak |
| Daily Penerimaan | Konsisten atau naik | Turun drastis tiba-tiba |
| Collection Rate | > 85% | < 70% |

### Arrears Aging

| Bucket | Tindakan |
|---|---|
| 0–30 hari | Normal — pantau saja |
| 31–60 hari | Reminder kepada wali murid |
| 61–90 hari | Koordinasi langsung dengan orang tua |
| > 90 hari | Kebijakan khusus (keringanan / cicilan / mediasi) |

---

## 5. Checklist Bulanan Kepala Sekolah

```
BULAN: _______________  PARAF: _______________

□ Monthly Report sudah diterima dan direview dari Bendahara
□ Penerimaan bulan ini: Rp _______________
□ Collection rate: _____%
□ Outstanding akhir bulan: Rp _______________
□ Jumlah siswa dengan aging >90 hari: ___ siswa
□ Arahan kebijakan penagihan bulan berikutnya sudah disampaikan
□ Laporan diarsipkan: UNITKODE_RingkasanKepsek_YYYYMM.pdf
```

---

# JUKLAK-05: OPERATOR TATA USAHA

**Nama Dokumen:** Petunjuk Pelaksanaan Operator Tata Usaha  
**Berlaku untuk:** `operator_tu`

---

## 1. Rutinitas Harian

### Pagi
| # | Langkah | Menu/Fitur |
|---|---|---|
| 1 | Login | Login |
| 2 | Cek Dashboard | Dashboard |
| 3 | Cek ada pendaftaran siswa baru yang perlu diinput | Master → Students |

### Jam Operasional
| # | Langkah | Menu/Fitur |
|---|---|---|
| 4 | Input data siswa baru (jika ada) | Master → Students → Create |
| 5 | Proses pembayaran siswa (ikuti alur standar Admin TU) | Invoices → Settlement |
| 6 | Cetak kwitansi ORIGINAL setiap transaksi | Receipts → Print |

**Penting:** Jika terjadi kesalahan input setelah simpan → JANGAN input ulang tanpa koordinasi. Segera laporkan kepada Admin TU.

### Akhir Hari
| # | Langkah | Menu/Fitur |
|---|---|---|
| 7 | Verifikasi Daily Report hari ini | Reports → Daily |
| 8 | Logout | — |

---

## 2. Prosedur Khusus: Import Siswa Massal

| # | Langkah |
|---|---|
| 1 | Unduh template CSV: **Master → Students → Import → Download Template** |
| 2 | Isi data siswa di template (NIS, Nama, Kelas, Kategori, Status) |
| 3 | Upload file CSV yang sudah diisi |
| 4 | Cek preview: ada error? Perbaiki dulu sebelum import |
| 5 | Klik **Confirm Import** jika semua sudah valid |
| 6 | Verifikasi: jumlah siswa di daftar bertambah |

---

## 3. Ketentuan dan Batasan Operator TU

| Yang BOLEH | Yang TIDAK BOLEH |
|---|---|
| Tambah/edit/hapus siswa | Batal (cancel) transaksi/settlement |
| Tambah/edit kelas dan kategori | Reprint kwitansi |
| Input transaksi pembayaran | Ubah fee matrix (hanya view) |
| Buat invoice | Kelola data pengguna |
| Cetak kwitansi ORIGINAL | Akses Settings |

---

## 4. Checklist Harian Operator TU

```
TANGGAL: _______________  PARAF: _______________

□ Input semua siswa baru (jika ada)
□ Semua pembayaran hari ini diinput ke sistem
□ Kwitansi ORIGINAL dicetak dan diserahkan untuk setiap pembayaran
□ Daily Report hari ini diverifikasi
□ Tidak ada error/duplikat transaksi
□ Logout
```

---

# JUKLAK-06: AUDITOR

**Nama Dokumen:** Petunjuk Pelaksanaan Auditor  
**Berlaku untuk:** `auditor`

---

> **Catatan Penting:** Auditor memiliki akses **view-only penuh** pada semua modul termasuk audit log. Tidak dapat membuat, mengubah, atau menghapus data apapun.

---

## 1. Alur Kerja Audit

| # | Tahapan | Keterangan |
|---|---|---|
| 1 | **Tentukan scope audit** | Periode, modul, dan aspek yang akan diaudit |
| 2 | **Login** dengan akun auditor | Akses sudah dibatasi read-only |
| 3 | **Akses data** sesuai scope | Lihat panduan akses di bawah |
| 4 | **Export laporan** yang relevan | Format Excel/PDF |
| 5 | **Analisis** di luar sistem | Di spreadsheet atau tool audit |
| 6 | **Dokumentasikan temuan** | Laporan audit terpisah (di luar SAKUMI) |

---

## 2. Akses Data per Modul

| Modul | Menu Akses | Data yang Bisa Dilihat |
|---|---|---|
| Transaksi | Transactions | Seluruh transaksi (income/expense) |
| Invoice | Invoices | Seluruh invoice dan statusnya |
| Settlement | Settlements | Seluruh settlement dan alokasi pembayaran |
| Kwitansi | Receipts | History cetak kwitansi dan reprint |
| Laporan Harian | Reports → Daily | Ringkasan kas harian |
| Laporan Bulanan | Reports → Monthly | Ringkasan penerimaan bulanan |
| Laporan Tunggakan | Reports → Arrears | Aging piutang |
| AR Outstanding | Reports → AR Outstanding | Piutang belum terbayar |
| Collection | Reports → Collection | Tingkat kolektibilitas |
| Cash Book | Reports → Cash Book | Buku kas kronologis |
| Audit Log | Audit | Seluruh aktivitas pengguna |
| Data Siswa | Master → Students | Data siswa (no edit) |
| Pengeluaran | Expenses | Daftar pengeluaran dan statusnya |
| Rekonsiliasi Bank | Bank Reconciliation | Sesi dan hasil rekonsiliasi |

---

## 3. Poin-Poin Audit Kritis

| Area | Yang Perlu Diperhatikan |
|---|---|
| **Kelengkapan transaksi** | Ada settlement tanpa kwitansi? Ada invoice tanpa settlement padahal outstanding = 0? |
| **Reprint kwitansi** | Reprint tanpa alasan? Print count terlalu tinggi? |
| **Pembatalan** | Cancel tranpa alasan? Cancel dilakukan oleh pihak yang tidak berwenang? |
| **Audit trail** | Ada periode tanpa aktivitas yang mencurigakan? Aksi di luar jam kerja normal? |
| **Rekonsiliasi bank** | Ada baris unmatched yang tidak dijelaskan? |
| **Expense** | Ada pengeluaran tanpa approval? Nominal tidak wajar? |
| **Aging piutang** | Ada siswa aging >90 hari yang belum ditindaklanjuti? |

---

## 4. Export Laporan Audit

| Laporan | Format Nama File |
|---|---|
| Transaksi periode X | `UNITKODE_Audit_Transaksi_YYYYMMDD_AUD.xlsx` |
| Settlement periode X | `UNITKODE_Audit_Settlement_YYYYMMDD_AUD.xlsx` |
| Activity Log | `UNITKODE_AuditLog_YYYYMMDD_AUD.xlsx` |
| Arrears Aging | `UNITKODE_Audit_Aging_YYYYMMDD_AUD.xlsx` |

---

# JUKLAK-07: KASIR

**Nama Dokumen:** Petunjuk Pelaksanaan Kasir  
**Berlaku untuk:** `cashier`

---

> **Catatan Penting:** Kasir hanya dapat mengakses menu: Dashboard, Transactions (create & view), dan Receipts (view & print). Kasir **tidak dapat** reprint kwitansi, void settlement, atau melihat laporan keuangan detail.

---

## 1. Rutinitas Harian Kasir

### Pembukaan Loket (Pagi)
| # | Langkah |
|---|---|
| 1 | Login ke SAKUMI |
| 2 | Verifikasi tanggal sesi sudah sesuai dengan hari ini |
| 3 | Siapkan saldo kas awal |
| 4 | Pastikan printer siap untuk cetak kwitansi |

### Jam Operasional (Setiap Pembayaran)

**Lakukan langkah-langkah berikut PERSIS dalam urutan ini:**

| # | Langkah | Penjelasan |
|---|---|---|
| 1 | Buka **Transactions → Create** | — |
| 2 | Isi **Student**: cari berdasarkan NIS atau nama | Pastikan nama benar sebelum lanjut |
| 3 | Isi **Type**: Income | — |
| 4 | Isi **Date**: tanggal hari ini | Jangan pernah isi tanggal mundur/maju |
| 5 | Tambah **Items**: pilih jenis biaya dan nominal | Pastikan nominal sesuai kuitansi/bukti |
| 6 | Pilih **Payment Method**: Tunai / Transfer / QRIS | — |
| 7 | **VERIFIKASI ULANG** sebelum simpan: nama siswa ✓ nominal ✓ | Langkah paling kritis |
| 8 | Klik **Save Transaction** | — |
| 9 | Klik **Print Receipt** → cetak kwitansi ORIGINAL | Serahkan kepada siswa/wali |
| 10 | Terima pembayaran dan berikan kembalian jika ada | — |

### Penutupan Loket (Akhir Hari)
| # | Langkah |
|---|---|
| 1 | Hitung total kas yang diterima (tunai + bukti transfer/QRIS) |
| 2 | Cek total transaksi hari ini di sistem |
| 3 | Bandingkan: cocok? ✓ Ada selisih? Catat nominal dan penyebab |
| 4 | Serahkan kas kepada Bendahara beserta catatan selisih (jika ada) |
| 5 | Logout |

---

## 2. Prosedur Darurat Kasir

### Jika Terjadi Kesalahan Setelah Simpan
> **SANGAT PENTING: Jangan panik. Jangan input transaksi lain untuk "mengimbangi".**
1. Catat nomor transaksi yang salah dan detail kesalahannya.
2. Hubungi Admin TU atau Bendahara **segera** (di shift yang sama).
3. Kasir menunggu sampai Admin TU/Bendahara memproses void/cancel.
4. Setelah dibatalkan: Admin TU/Bendahara yang akan membuat transaksi pengganti.

### Jika Siswa Meminta Reprint Kwitansi
> Kasir tidak berwenang melakukan reprint.
1. Catat: nama siswa, nomor transaksi, alasan minta reprint.
2. Sampaikan kepada Admin TU atau Bendahara.
3. Admin TU/Bendahara yang akan mengeksekusi reprint dengan alasan resmi.

### Jika Sistem Error / Tidak Bisa Login
1. Jangan terima pembayaran hingga sistem kembali normal.
2. Hubungi Super Admin/Admin TU untuk melaporkan masalah.
3. Jika terpaksa terima pembayaran manual: catat di formulir sementara (nama siswa, nominal, metode, waktu).
4. Setelah sistem normal: input semua data dari formulir sementara.

---

## 3. Yang BOLEH dan TIDAK BOLEH Dilakukan Kasir

| Yang BOLEH | Yang TIDAK BOLEH |
|---|---|
| Input transaksi pembayaran baru | Batalkan/void transaksi sendiri |
| Cetak kwitansi ORIGINAL | Reprint kwitansi (COPY) |
| Lihat daftar dan detail transaksi | Membuat/melihat invoice atau settlement |
| Lihat/cetak kwitansi | Mengakses laporan keuangan |
| — | Berbagi akun/password |
| — | Terima pembayaran tanpa diinput saat itu juga |

---

## 4. Checklist Harian Kasir

```
TANGGAL: _______________  NAMA KASIR: _______________

PEMBUKAAN:
□ Login berhasil
□ Tanggal sistem sesuai hari ini
□ Printer siap

OPERASIONAL (diisi per shift):
□ Setiap pembayaran langsung diinput dan kwitansi dicetak
□ Tidak ada pembayaran diterima tanpa diinput
□ Tidak ada pembayaran diinput dua kali

PENUTUPAN:
□ Total kas dihitung: Rp _______________
□ Cocok dengan sistem: □ Ya / □ Selisih Rp _______________ (penyebab: _____________)
□ Kas sudah diserahkan ke Bendahara
□ Catatan selisih sudah diserahkan ke Bendahara
□ Logout
```

---

# MATRIKS RINGKASAN JUKLAK

| Kegiatan | Super Admin | Admin TU | Bendahara | Kepala Sekolah | Operator TU | Auditor | Kasir |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Input transaksi pembayaran | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ✅ |
| Cetak kwitansi ORIGINAL | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ✅ |
| Reprint kwitansi (COPY) | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Buat/generate invoice | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| Buat settlement | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| Void/cancel settlement | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Cancel transaksi | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Approve expense | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Rekonsiliasi bank | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Lihat Daily Report | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Lihat Arrears Report | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Kelola master data | ✅ | ✅ | Sebagian | ❌ | ✅ | ❌ | ❌ |
| Kelola fee matrix | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Kelola pengguna | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Kelola settings | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Lihat audit log | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| Health check sistem | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

*JUKLAK ini adalah dokumen hidup — diperbarui bila ada perubahan alur atau fitur sistem SAKUMI.*  
*Konsultasikan dengan Super Admin atau Bendahara jika ada prosedur yang tidak jelas.*
