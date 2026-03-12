# SAKUMI - System Overview

## Tujuan Sistem

SAKUMI (Sistem Akuntansi Keuangan Sekolah) adalah aplikasi manajemen keuangan sekolah berbasis web yang dibangun menggunakan Laravel. Sistem ini dirancang untuk mengelola seluruh siklus keuangan sekolah mulai dari penagihan (invoicing), penerimaan pembayaran (settlement), pencetakan kuitansi (receipt), hingga pelaporan keuangan (reporting).

## Unit Sekolah yang Didukung

- **MI** - Madrasah Ibtidaiyah
- **RA** - Raudhatul Athfal
- **DTA** - Diniyah Takmiliyah Awaliyah

Setiap unit berjalan secara terpisah (multi-tenant per unit) dengan data keuangan yang terisolasi melalui mekanisme `BelongsToUnit` scope.

## Core Modules

### 1. Student Management
Pengelolaan data siswa termasuk NIS, NISN, kelas, kategori, wali murid, dan enrollment per tahun ajaran.

### 2. Fee Management (Master Tarif)
- **Fee Types** — Jenis biaya (SPP, uang gedung, dll) dengan flag `is_monthly` dan `is_active`
- **Fee Matrix** — Tarif per kelas/kategori dengan masa berlaku (`effective_from`/`effective_to`)
- **Student Fee Mapping** — Override tarif individual per siswa

### 3. Obligation Generation
ArrearsService secara otomatis membuat kewajiban bulanan (`StudentObligation`) berdasarkan Fee Matrix dan Student Fee Mapping, dengan prioritas: mapping individual > matrix kelas/kategori.

### 4. Invoice Management
- **Batch Generation** — Generate invoice massal per periode (bulanan/tahunan) per kelas/kategori
- **Manual Creation** — Buat invoice individual dengan memilih kewajiban tertentu
- **Invoice Items** — Setiap item terhubung ke StudentObligation
- Status: `unpaid` → `partially_paid` → `paid` | `cancelled`

### 5. Settlement Processing
- **Single-Invoice Settlement** — Bayar satu invoice
- **Multi-Invoice Settlement** — Alokasi satu pembayaran ke beberapa invoice sekaligus
- Metode pembayaran: `cash`, `transfer`, `qris`
- Settlement Allocation menghubungkan pembayaran ke invoice
- Status: `completed` | `cancelled` | `void`

### 6. Transaction Recording
- **Income Transaction** — Penerimaan non-invoice (biaya non-rutin)
- **Expense Transaction** — Pengeluaran operasional
- Sistem secara otomatis mengarahkan pembayaran siswa dengan invoice terbuka ke Settlement

### 7. Receipt Printing
- PDF receipt dengan verifikasi code dan watermark
- Controlled receipt system: tracking cetak ulang (reprint) dengan alasan wajib
- Terbilang otomatis dalam Bahasa Indonesia

### 8. Financial Reporting
- **Laporan Harian** — Transaksi per hari dengan total
- **Laporan Bulanan** — Rekapitulasi per bulan
- **Laporan Tunggakan (Arrears)** — Kewajiban belum terbayar
- **AR Outstanding** — Aging analysis (0-30, 31-60, 61-90, 90+ hari)
- **Laporan Koleksi (Collection)** — Tingkat kolektabilitas pembayaran
- **Laporan Siswa (Student Statement)** — Riwayat keuangan per siswa
- **Buku Kas (Cash Book)** — Arus kas masuk dan keluar
- Semua laporan mendukung export Excel

### 9. Expense Management
- Pencatatan pengeluaran dengan kategori/subkategori
- Approval workflow
- Budget vs Realization report

### 10. Bank Reconciliation
- Import data bank
- Matching otomatis dan manual settlement ↔ bank statement
- Session-based reconciliation workflow

### 11. Admission (PSB)
- Periode penerimaan siswa baru
- Workflow: pendaftaran → review → accept/reject → enroll

## Arsitektur Sistem

```
┌──────────────────────────────────────────────────┐
│                   Laravel App                     │
├──────────────┬──────────────┬────────────────────┤
│  Controllers │   Services   │     Models         │
│              │              │                    │
│  Invoice     │ InvoiceServ  │ Student            │
│  Settlement  │ SettlementS  │ Invoice/Items      │
│  Transaction │ TransactionS │ Settlement/Alloc   │
│  Report      │ ArrearsServ  │ Transaction/Items  │
│  Receipt     │ ReceiptServ  │ StudentObligation  │
│  Expense     │ AccountingEn │ FeeMatrix/FeeType  │
│  Admission   │ BankReconSrv │ Receipt            │
│  Reconcile   │              │ DocumentSequence   │
└──────┬───────┴──────────────┴────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────────┐
│              PostgreSQL Database                  │
│  + Unit Scoping (multi-tenant)                   │
│  + Spatie Activity Log (audit trail)             │
│  + Soft Deletes (students)                       │
│  + Hard Delete Protection (invoices, settlements)│
└──────────────────────────────────────────────────┘
```

## Financial Workflow

```
Fee Matrix / Student Fee Mapping
        │
        ▼
StudentObligation (kewajiban bulanan)
        │
        ▼
Invoice (penagihan ke siswa/wali)
        │
        ▼
Settlement (penerimaan pembayaran)
  ├── SettlementAllocation (alokasi ke invoice)
  └── Obligation marked as paid
        │
        ▼
Receipt (kuitansi pembayaran)
        │
        ▼
Reports (laporan keuangan)
```

## Accounting Engine

Setiap operasi keuangan dicatat melalui `AccountingEngine::fromEvent()` dengan idempotency key untuk mencegah duplikasi jurnal. Event types:
- `invoice.created`
- `settlement.applied`
- `payment.posted` / `payment.direct.posted`
- `expense.posted`
- `reversal.posted` (void/cancel)

## Document Numbering

Nomor dokumen dihasilkan secara otomatis menggunakan `DocumentSequence` model:
- Invoice: `INV-{UNIT}-{YEAR}-{SEQ}` (6 digit)
- Settlement: `STL-{YEAR}-{SEQ}` (6 digit)
- Transaction Income: `NF-{YEAR}-{SEQ}` (6 digit)
- Transaction Expense: `NK-{YEAR}-{SEQ}` (6 digit)
