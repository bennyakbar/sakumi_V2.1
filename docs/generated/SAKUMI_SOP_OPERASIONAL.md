# SAKUMI - SOP Operasional

## SOP-01: Membuat Invoice (Batch)

### Tujuan
Generate invoice untuk seluruh siswa dalam satu periode secara massal.

### Penanggung Jawab
Admin TU / Bendahara

### Langkah-langkah

1. Buka menu **Invoices** > **Generate Invoice**
2. Pilih **Period Type**: `monthly` atau `annual`
3. Isi **Period Identifier**: format `YYYY-MM` (contoh: `2026-03`) untuk monthly
4. Set **Due Date** (tanggal jatuh tempo, minimal hari ini)
5. Filter opsional:
   - **Class** — filter per kelas
   - **Category** — filter per kategori siswa
6. Klik **Generate**
7. Sistem akan:
   - Generate kewajiban bulanan (jika belum ada) via ArrearsService
   - Membuat invoice untuk setiap siswa yang memiliki kewajiban belum di-invoice
   - Melewati siswa yang sudah punya invoice untuk periode tersebut
8. Hasil ditampilkan: jumlah created, skipped, dan errors (jika ada)

### Validasi Otomatis
- Kewajiban yang sudah di-invoice (non-cancelled) akan di-skip
- Siswa tidak aktif tidak dibuatkan invoice
- Nomor invoice otomatis: `INV-{UNIT}-{YEAR}-{SEQ}`

---

## SOP-02: Membuat Invoice (Manual/Individual)

### Tujuan
Membuat invoice untuk satu siswa tertentu.

### Penanggung Jawab
Admin TU / Bendahara

### Langkah-langkah

1. Buka menu **Invoices** > **Create Invoice**
2. Pilih **Student** dari dropdown
3. Sistem menampilkan daftar kewajiban yang belum di-invoice
4. Centang kewajiban yang ingin ditagihkan
5. Set **Due Date**
6. Tambahkan **Notes** (opsional)
7. Klik **Submit**
8. Invoice dibuat dan dialihkan ke halaman detail

### Catatan
- Hanya menampilkan kewajiban dengan `is_paid = false`
- Kewajiban yang sudah ada di invoice aktif (non-cancelled) tidak muncul

---

## SOP-03: Menerima Pembayaran (Single Invoice)

### Tujuan
Mencatat penerimaan pembayaran untuk satu invoice.

### Penanggung Jawab
Bendahara / Admin TU

### Langkah-langkah

1. Buka menu **Settlements** > **Create Settlement**
2. Pilih **Student**
3. Sistem menampilkan invoice yang memiliki sisa tagihan
4. Pilih invoice yang akan dibayar
5. Isi:
   - **Payment Date** — tanggal pembayaran
   - **Payment Method** — `cash` / `transfer` / `qris`
   - **Amount** — jumlah yang dibayar (bisa sebagian)
   - **Reference Number** — nomor referensi bank (untuk transfer/qris)
   - **Notes** — catatan (opsional)
6. Klik **Submit**
7. Sistem:
   - Validasi amount <= outstanding invoice
   - Buat Settlement + SettlementAllocation
   - Update status invoice (unpaid → partially_paid / paid)
   - Jika lunas, tandai kewajiban sebagai terbayar

---

## SOP-04: Menerima Pembayaran (Multi Invoice)

### Tujuan
Mencatat satu pembayaran yang dialokasikan ke beberapa invoice sekaligus.

### Penanggung Jawab
Bendahara / Admin TU

### Langkah-langkah

1. Buka menu **Settlements** > **Create Settlement**
2. Pilih **Student**
3. Aktifkan mode **Multi Invoice** (parameter `multi=1`)
4. Sistem menampilkan semua invoice outstanding siswa
5. Isi **Payment Amount** (total uang diterima)
6. Alokasikan jumlah ke masing-masing invoice
7. Isi Payment Date, Method, Reference Number
8. Klik **Submit**

### Validasi
- Total alokasi tidak boleh melebihi payment amount
- Setiap alokasi tidak boleh melebihi outstanding invoice tersebut
- Minimal satu alokasi dengan amount > 0

---

## SOP-05: Void Settlement (Pembatalan Pembayaran)

### Tujuan
Membatalkan pembayaran yang sudah tercatat karena kesalahan.

### Penanggung Jawab
Bendahara (memerlukan permission `settlements.void`)

### Langkah-langkah

1. Buka menu **Settlements** > Cari settlement yang akan di-void
2. Buka detail settlement
3. Klik **Void**
4. Isi **Void Reason** (wajib, maksimal 1000 karakter)
5. Konfirmasi
6. Sistem:
   - Update status settlement → `void`
   - Recalculate semua invoice terkait (bisa kembali ke unpaid/partially_paid)
   - Revert status paid pada kewajiban terkait
   - Buat jurnal reversal via AccountingEngine

### Peringatan
- Hanya settlement berstatus `completed` yang bisa di-void
- Void bersifat permanen, tidak bisa di-undo

---

## SOP-06: Cetak Kuitansi (Receipt)

### Tujuan
Mencetak bukti pembayaran untuk wali murid.

### Penanggung Jawab
Bendahara / Admin TU

### Langkah-langkah

#### Kuitansi Settlement:
1. Buka detail settlement
2. Klik **Print**
3. Cetakan pertama: status ORIGINAL
4. Cetak ulang:
   - Hanya bendahara/admin yang bisa cetak ulang
   - Wajib isi alasan cetak ulang
   - Status berubah sesuai tracking

#### Kuitansi Transaction:
1. Buka detail transaction
2. Klik **Print Receipt**
3. PDF otomatis di-generate saat transaction dibuat

### Isi Kuitansi
- Data sekolah (nama, alamat, logo)
- Data siswa (nama, kelas)
- Rincian pembayaran
- Terbilang dalam Bahasa Indonesia
- Verification code + QR
- Watermark (ORIGINAL / REPRINT / CANCELLED)

---

## SOP-07: Monitoring Tunggakan

### Tujuan
Memantau siswa yang memiliki kewajiban belum terbayar.

### Penanggung Jawab
Bendahara / Kepala Sekolah

### Langkah-langkah

1. Buka menu **Reports** > **Arrears**
2. Filter berdasarkan kelas, kategori, atau periode (opsional)
3. Lihat daftar siswa dengan tunggakan
4. Untuk detail per siswa: buka **Reports** > **Student Statement**
5. Export ke Excel untuk keperluan follow-up

### Laporan Terkait
- **AR Outstanding** — Aging analysis untuk melihat umur piutang
- **Collection Report** — Tingkat kolektabilitas per periode

---

## SOP-08: Generate Laporan

### Tujuan
Menghasilkan laporan keuangan untuk pelaporan dan audit.

### Penanggung Jawab
Bendahara / Kepala Sekolah / Auditor

### Jenis Laporan dan Langkah:

#### Laporan Harian:
1. Menu **Reports** > **Daily**
2. Pilih tanggal
3. Lihat atau Export Excel

#### Laporan Bulanan:
1. Menu **Reports** > **Monthly**
2. Pilih bulan dan tahun
3. Lihat atau Export Excel

#### Buku Kas:
1. Menu **Reports** > **Cash Book**
2. Pilih periode
3. Lihat arus kas masuk dan keluar

#### Student Statement:
1. Menu **Reports** > **Student Statement**
2. Pilih siswa
3. Lihat riwayat lengkap keuangan siswa

### Scope Laporan
- **Per Unit** — Default, hanya data unit aktif
- **Consolidated** — Hanya super_admin, gabungan semua unit

---

## SOP-09: Cancel Invoice

### Tujuan
Membatalkan invoice yang salah atau tidak diperlukan.

### Penanggung Jawab
Admin TU / Bendahara

### Langkah-langkah

1. Buka detail invoice
2. Klik **Cancel**
3. Isi alasan pembatalan (opsional)
4. Konfirmasi

### Skenario:
| Kondisi Invoice | Aksi Sistem |
|----------------|-------------|
| Unpaid (belum dibayar) | Langsung cancel |
| Partially paid | Void semua settlement terkait, lalu cancel |
| Paid (lunas) | Void semua settlement terkait, lalu cancel |

### Permission:
- Invoice unpaid: permission `invoices.cancel`
- Invoice paid/partially_paid: tambahan permission `invoices.cancel_paid` + `settlements.void`
