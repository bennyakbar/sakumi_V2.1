## Context

Sistem existing mengandalkan `fee_matrix` level kelas+kategori untuk generate kewajiban bulanan. Kebutuhan sekolah menuntut override per siswa dengan periode efektif yang bisa bertumpuk lintas tahun ajaran tanpa mengganggu siswa lain.

## Goals / Non-Goals

**Goals:**
- Menyediakan data model mapping fee individual per siswa.
- Menambahkan antarmuka admin untuk kelola mapping per siswa.
- Menjaga kompatibilitas dengan mekanisme matrix lama sebagai fallback.

**Non-Goals:**
- Tidak mengubah struktur `fee_matrix` inti.
- Tidak membuat engine prorata biaya otomatis pada change ini.

## Decisions

1. Tambah tabel `student_fee_mappings` sebagai override layer.
- Rationale: isolasi rule per siswa tanpa merusak skema matrix global.
- Alternative: menambah kolom di `students`; ditolak karena relasi many-to-many periodik.

2. Resolver fee saat generate obligation: cek mapping aktif siswa dahulu, jika tidak ada baru fallback ke class+category matrix.
- Rationale: perilaku deterministic dan backward compatible.
- Alternative: merge kedua sumber secara paralel; berisiko double charge.

3. Validasi overlap periode pada kombinasi `student_id + fee_matrix_id` dan rule aktif pada tanggal yang sama.
- Rationale: mencegah ambiguitas mapping saat generate obligation.
- Alternative: izinkan overlap dan pilih latest created; sulit diaudit.

## Risks / Trade-offs

- [Risk] Salah konfigurasi mapping menyebabkan charge tidak sesuai -> Mitigation: preview mapping aktif sebelum simpan.
- [Risk] Query active mapping memperlambat generate massal -> Mitigation: index komposit `student_id, effective_from, effective_to, is_active`.
- [Risk] Perubahan rule berdampak ke histori -> Mitigation: mapping bersifat prospektif, tidak mengubah obligation/invoice yang sudah terbentuk.

## Migration Plan

1. Tambah migration + model + relasi Eloquent.
2. Tambah service resolver dan integrasi ke generator obligation/invoice.
3. Tambah UI CRUD pada detail siswa.
4. Tambah tests overlap period, fallback behavior, dan generation output.

## Open Questions

- Apakah perlu opsi prioritas jika ada dua mapping aktif dengan fee type berbeda dalam periode sama?
- Apakah perubahan mapping berlaku segera atau mulai periode berikutnya secara default?
