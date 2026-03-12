# SAKUMI - Internal Control & Audit

## 1. Pencegahan Duplikasi Pembayaran

### 1.1 Concurrency Lock pada Settlement
Saat membuat settlement, sistem menggunakan `lockForUpdate()` pada baris invoice untuk mencegah dua pembayaran bersamaan meng-over-allocate satu invoice.

```
SettlementService::createSettlement()
  → DB::transaction()
    → Invoice::lockForUpdate()       ← Database-level lock
    → Hitung outstanding dari SUM(allocations)  ← Live calculation
    → Validasi amount <= outstanding
    → Create Settlement + Allocation
```

### 1.2 Validasi Outstanding Real-time
Outstanding invoice dihitung dari **allocation sum** (bukan kolom `paid_amount`) untuk menghindari drift antara kolom denormalized dan data aktual:

```php
$settledAmount = $invoice->allocations()
    ->whereHas('settlement', fn($q) => $q->where('status', 'completed'))
    ->sum('amount');
$outstanding = $invoice->total_amount - $settledAmount;
```

### 1.3 Duplicate Obligation Prevention
`ArrearsService::generateMonthlyObligations()` bersifat idempoten — memeriksa existing obligation sebelum membuat baru berdasarkan kombinasi `student_id + fee_type_id + month + year`.

### 1.4 Duplicate Invoice Prevention
Invoice generation memeriksa apakah kewajiban sudah ada di invoice non-cancelled sebelum membuat invoice baru:
```php
->whereDoesntHave('invoiceItems', function ($q) {
    $q->whereHas('invoice', fn($iq) => $iq->where('status', '!=', 'cancelled'));
});
```

### 1.5 Idempotency Key (Accounting Engine)
Setiap jurnal akuntansi memiliki `idempotency_key` unik yang mencegah duplikasi pencatatan:
- `invoice.created:{id}`
- `settlement.applied:{id}`
- `settlement.void.reversal:{id}`
- `transaction.cancel.reversal:{id}`

---

## 2. Invoice Locking

### 2.1 Hard Delete Protection
Invoice dan Settlement tidak bisa di-hard delete. Model `booted()` method melempar `RuntimeException`:

```php
static::deleting(function (Invoice $invoice) {
    throw new \RuntimeException(__('message.hard_delete_not_allowed'));
});
```

Hal yang sama berlaku untuk Settlement.

### 2.2 Obligation Amount Freezing
Tarif kewajiban (StudentObligation) hanya bisa diubah jika:
- Belum terbayar (`is_paid = false`)
- Belum pernah di-invoice sama sekali (termasuk invoice cancelled)
- Belum terhubung ke transaction item

Setelah di-invoice, jumlah kewajiban "frozen" untuk menjaga integritas audit trail.

### 2.3 Transaction Immutability
Transaction tidak bisa di-edit setelah dibuat. Jika ada kesalahan, harus di-cancel dan buat baru.

---

## 3. Audit Trail

### 3.1 Spatie Activity Log

Setiap model keuangan menggunakan trait `LogsActivity` dari Spatie:

**Invoice - field yang di-log:**
- status, paid_amount, total_amount
- student_id, invoice_date, due_date, notes

**Settlement - field yang di-log:**
- status, total_amount, allocated_amount, payment_method
- student_id, payment_date, cancellation_reason, void_reason

**Student:**
- Semua field yang berubah (`logOnlyDirty`)

### 3.2 Tracking Creator/Updater

| Model | Created By | Updated By | Cancelled By | Voided By |
|-------|:----------:|:----------:|:------------:|:---------:|
| Invoice | ✅ | ✅ (auto) | - | - |
| Settlement | ✅ | ✅ (auto) | ✅ + at + reason | ✅ + at + reason |
| Transaction | ✅ | - | ✅ + at + reason | - |

- `updated_by` pada Invoice dan Settlement otomatis diisi via model `updating` event

### 3.3 Receipt Print Tracking

Controlled Receipt System mencatat setiap cetak kuitansi:
- **User** yang mencetak
- **Waktu** cetak (issued_at, printed_at)
- **Print count** — berapa kali dicetak
- **Alasan** cetak ulang (wajib diisi)
- **IP Address** dan **Device** (user agent)

### 3.4 Accounting Journal

AccountingEngine mencatat setiap event keuangan:
- `invoice.created` — Invoice baru
- `settlement.applied` — Pembayaran diterima (dengan detail allocations)
- `payment.posted` / `payment.direct.posted` — Penerimaan kas
- `expense.posted` — Pengeluaran
- `reversal.posted` — Pembatalan (void/cancel)

---

## 4. Safeguards (Pengamanan)

### 4.1 Business Rules

| Kode | Rule | Implementasi |
|------|------|-------------|
| BR-06 | Total alokasi tidak boleh melebihi total pembayaran | `SettlementService::createSettlement()` |
| BR-06 | Alokasi per invoice tidak boleh melebihi outstanding | `SettlementService::createSettlement()` |
| BR-07 | Alokasi hanya ke invoice milik siswa yang sama | `where('student_id', $data['student_id'])` |

### 4.2 Cash Separation Enforcement

Transaction controller memastikan pemisahan kas yang benar:

| Kondisi | Aksi |
|---------|------|
| Student + fee bulanan | Tolak → wajib pakai Settlement |
| Student + invoice terbuka | Redirect ke Settlement |
| Student + kewajiban belum diinvoice | Tolak → buat invoice dulu |
| Non-student income | Boleh via Transaction |
| Expense | Boleh via Transaction (permission khusus) |

### 4.3 Role-Based Access Control

- Semua route dilindungi middleware `role:` dan `can:` (permission)
- Rate limiting pada dashboard (`throttle:dashboard-read`) dan reports (`throttle:reports-read`)
- Void settlement memerlukan permission `settlements.void`
- Cancel paid invoice memerlukan permission `invoices.cancel_paid`
- Reprint receipt hanya untuk bendahara/admin

### 4.4 Data Integrity

- Soft delete pada Student (data tidak hilang)
- Hard delete protection pada Invoice dan Settlement
- Database transaction pada semua operasi keuangan
- Foreign key constraints pada semua relasi
- Unit scoping mencegah cross-unit data access

### 4.5 Over-Settlement Detection

Sistem secara aktif mendeteksi dan log warning jika terjadi over-settlement:
```php
if ($outstanding < 0) {
    Log::warning('Over-settled invoice detected', [
        'invoice_id' => $invoice->id,
        'total_amount' => $invoice->total_amount,
        'settled_amount' => $settledAmount,
    ]);
    $outstanding = 0; // Clamp to prevent negative allocation
}
```

---

## 5. Reconciliation Controls

### Bank Reconciliation
- Session-based: setiap periode rekonsiliasi adalah sesi terpisah
- Import data bank dari file
- Matching manual/otomatis antara settlement dan bank statement
- Close session untuk finalisasi
- Permission terpisah untuk manage dan close

### Invoice Recalculation
- `recalculateFromAllocations()` pada Invoice memastikan `paid_amount` selalu sinkron dengan allocation data aktual
- `recalculateAllocatedAmount()` pada Settlement memastikan `allocated_amount` sinkron
- Dipanggil setelah setiap operasi yang mempengaruhi allocation (void, cancel)

---

## 6. Checklist Audit Periodik

### Harian
- [ ] Review laporan harian — jumlah transaksi dan total sesuai kas fisik
- [ ] Periksa settlement baru — status completed, alokasi benar
- [ ] Periksa apakah ada void/cancel — baca alasan

### Bulanan
- [ ] Rekonsiliasi bank — cocokkan settlement dengan mutasi bank
- [ ] Review AR outstanding — aging analysis
- [ ] Review collection rate — tingkat kolektabilitas
- [ ] Generate laporan bulanan — verifikasi total
- [ ] Review buku kas — arus kas masuk/keluar balance

### Tahunan / Semesteran
- [ ] Review activity log — perubahan tidak wajar
- [ ] Review user accounts — role dan permission masih sesuai
- [ ] Review fee matrix — tarif sesuai keputusan yayasan
- [ ] Review student fee mapping — override tarif masih berlaku
- [ ] Review receipt print log — ada reprint yang mencurigakan
