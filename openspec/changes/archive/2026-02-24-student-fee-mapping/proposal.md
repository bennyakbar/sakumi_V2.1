## Why

Penentuan fee saat ini berbasis matriks kelas+kategori, sehingga kasus khusus per siswa (diskon, program khusus, transisi) belum bisa ditangani secara tepat. Sekolah membutuhkan pemetaan fee individual dengan periode efektif.

## What Changes

- Menambah model pemetaan fee per siswa dengan effective start/end period.
- Menambah UI CRUD untuk assign beberapa fee matrix ke satu siswa.
- Menyesuaikan generator obligation/invoice agar prioritas membaca mapping siswa sebelum fallback ke matrix umum kelas+kategori.
- Menambahkan validasi overlap periode agar tidak terjadi konflik mapping aktif.

## Capabilities

### New Capabilities
- `student-fee-mapping`: Pemetaan fee matrix individual per siswa dengan periode efektif dan prioritas terhadap matrix global.

### Modified Capabilities
- `obligations-arrears`: Perubahan sumber aturan fee saat generate obligation agar mendukung mapping siswa.

## Impact

- Affected code: model/migration baru, service generation obligation/invoice, UI manajemen siswa.
- Data: tabel baru `student_fee_mappings` dengan relasi ke `students` dan `fee_matrix`.
- Risiko: konflik periode aktif dan backward compatibility ke data lama.
