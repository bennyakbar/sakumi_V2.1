# SAKUMI - Business Process Flow

## Siklus Keuangan Lengkap

```mermaid
flowchart TD
    A[Setup Master Data] --> B[Generate Obligations]
    B --> C[Generate Invoice]
    C --> D[Distribusi Invoice ke Wali]
    D --> E{Wali Bayar?}
    E -->|Ya| F[Settlement - Terima Pembayaran]
    E -->|Belum| G[Monitoring Tunggakan]
    G --> D
    F --> H[Alokasi ke Invoice]
    H --> I[Update Status Invoice]
    I --> J[Cetak Kuitansi]
    J --> K[Laporan Keuangan]
```

## 1. Setup Master Data (Awal Tahun Ajaran)

### Alur:
1. Admin TU mendaftarkan/mengupdate data siswa
2. Admin TU mengatur kelas dan kategori siswa
3. Bendahara mengatur jenis biaya (Fee Types)
4. Bendahara mengatur tarif per kelas/kategori (Fee Matrix)
5. Jika ada siswa dengan tarif khusus, buat Student Fee Mapping

### Data yang Dikelola:
- **Students** — NIS, NISN, nama, kelas, kategori, data wali
- **School Classes** — Kelas 1-6 (MI), kelompok A/B (RA), dll
- **Student Categories** — Reguler, yatim, dhuafa, dll
- **Fee Types** — SPP, uang gedung, kegiatan, dll (flag `is_monthly`)
- **Fee Matrix** — Tarif: fee_type × class × category = amount

## 2. Generate Kewajiban (Obligation)

### Alur:
```mermaid
flowchart LR
    A[ArrearsService] --> B{Student Fee Mapping?}
    B -->|Ada| C[Gunakan mapping individual]
    B -->|Tidak ada| D[Gunakan Fee Matrix]
    C --> E[Buat StudentObligation]
    D --> E
    E --> F[Kewajiban per siswa per bulan]
```

### Mekanisme:
- `ArrearsService::generateMonthlyObligations()` berjalan otomatis saat invoice generation
- Mengecek enrollment aktif siswa pada tanggal periode
- Prioritas tarif: Student Fee Mapping > Fee Matrix (class+category > class > category > default)
- Idempoten — tidak membuat duplikat, tapi bisa update tarif selama belum pernah di-invoice

## 3. Invoice (Penagihan)

### Batch Generation:
```mermaid
flowchart TD
    A[Pilih Periode & Filter] --> B[Generate Obligations]
    B --> C[Loop setiap siswa aktif]
    C --> D{Ada kewajiban unpaid?}
    D -->|Ya| E{Sudah di-invoice?}
    D -->|Tidak| F[Skip]
    E -->|Belum| G[Buat Invoice + Items]
    E -->|Sudah| F
    G --> H[AccountingEngine: invoice.created]
```

### Manual Creation:
1. Pilih siswa
2. Sistem menampilkan kewajiban yang belum di-invoice
3. Pilih kewajiban yang ingin di-invoice
4. Set tanggal jatuh tempo
5. Invoice dibuat dengan nomor otomatis

### Status Invoice:
| Status | Keterangan |
|--------|------------|
| `unpaid` | Belum ada pembayaran |
| `partially_paid` | Sudah dibayar sebagian |
| `paid` | Lunas (paid_amount >= total_amount) |
| `cancelled` | Dibatalkan |

### Proteksi:
- Invoice tidak bisa di-hard delete (exception dilempar)
- Cancel invoice yang sudah ada pembayaran akan cascade void settlement terkait
- Membutuhkan permission `invoices.cancel_paid` untuk cancel invoice yang sudah dibayar

## 4. Settlement (Penerimaan Pembayaran)

### Single-Invoice Flow:
```mermaid
flowchart TD
    A[Pilih Siswa] --> B[Tampilkan Invoice Outstanding]
    B --> C[Pilih Invoice]
    C --> D[Input Jumlah & Metode Bayar]
    D --> E{Amount <= Outstanding?}
    E -->|Ya| F[Buat Settlement + Allocation]
    E -->|Tidak| G[Error: melebihi sisa]
    F --> H[Recalculate Invoice]
    H --> I[Mark Obligations as Paid]
    I --> J[AccountingEngine: settlement.applied]
```

### Multi-Invoice Flow:
1. Pilih siswa
2. Tampilkan semua invoice outstanding
3. Input total pembayaran
4. Alokasikan ke masing-masing invoice
5. Validasi: total alokasi <= total pembayaran
6. Validasi: setiap alokasi <= outstanding invoice tersebut
7. Buat Settlement dengan multiple SettlementAllocation

### Concurrency Control:
- `lockForUpdate()` pada invoice saat alokasi untuk mencegah over-allocation
- Validasi outstanding dihitung dari allocation sum, bukan kolom `paid_amount`

### Void Settlement:
```mermaid
flowchart TD
    A[Void Request + Reason] --> B[Update status = void]
    B --> C[Recalculate affected invoices]
    C --> D[Revert obligation payments]
    D --> E[Recalculate allocated_amount]
    E --> F[AccountingEngine: reversal.posted]
```

## 5. Transaction (Penerimaan/Pengeluaran Non-Invoice)

### Cash Separation Rules:
- Pembayaran siswa dengan fee bulanan **WAJIB** melalui Settlement
- Jika siswa punya invoice terbuka, sistem redirect ke Settlement
- Jika siswa punya kewajiban belum di-invoice, sistem tolak dan arahkan ke Invoice dulu
- Transaction hanya untuk penerimaan non-rutin atau pengeluaran

### Income Transaction:
- Untuk penerimaan yang tidak terkait invoice (donasi, dll)
- Otomatis generate receipt PDF

### Expense Transaction:
- Membutuhkan permission `transactions.expense.create`
- Fee type harus bertipe expense (dengan subcategory)

## 6. Receipt (Kuitansi)

### Flow:
```mermaid
flowchart LR
    A[Transaction Created] --> B[Generate PDF]
    B --> C[Verification Code]
    C --> D[QR/URL Verify]
    D --> E[Simpan di Storage]
```

### Controlled Receipt (Settlement):
1. Cetak pertama — otomatis, status ORIGINAL
2. Cetak ulang — wajib alasan, hanya bendahara/admin
3. Setiap cetak dicatat: user, waktu, IP, device, alasan

### Verifikasi:
- Setiap kuitansi punya verification code unik
- Bisa diverifikasi publik via URL tanpa login

## 7. Reporting

### Laporan yang Tersedia:

| Laporan | Fungsi | Export |
|---------|--------|--------|
| Daily | Transaksi harian | Excel |
| Monthly | Rekapitulasi bulanan | Excel |
| Arrears | Daftar tunggakan per siswa | Excel |
| AR Outstanding | Aging analysis piutang | Excel |
| Collection | Tingkat kolektabilitas | Excel |
| Student Statement | Riwayat keuangan siswa | Excel |
| Cash Book | Arus kas masuk/keluar | Excel |
| Budget vs Realization | Anggaran vs realisasi pengeluaran | - |

### Scope:
- Per unit (default) — hanya data unit aktif
- Consolidated (super_admin) — gabungan semua unit

## Diagram Lifecycle Lengkap

```mermaid
stateDiagram-v2
    [*] --> FeeSetup: Awal Tahun Ajaran
    FeeSetup --> ObligationGenerated: ArrearsService
    ObligationGenerated --> InvoiceCreated: InvoiceService
    InvoiceCreated --> PaymentReceived: SettlementService
    PaymentReceived --> InvoicePartial: Bayar Sebagian
    PaymentReceived --> InvoicePaid: Lunas
    InvoicePartial --> PaymentReceived: Bayar Lagi
    InvoicePaid --> ReceiptPrinted: ReceiptService
    ReceiptPrinted --> Reported: ReportController

    InvoiceCreated --> InvoiceCancelled: Cancel
    PaymentReceived --> SettlementVoided: Void
    SettlementVoided --> InvoiceCreated: Invoice kembali unpaid
```
