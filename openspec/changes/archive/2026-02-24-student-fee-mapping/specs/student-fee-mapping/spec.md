## ADDED Requirements

### Requirement: Student Fee Mapping Data Model
Sistem SHALL memiliki tabel `student_fee_mappings` dengan kolom minimal `id`, `unit_id`, `student_id`, `fee_matrix_id`, `effective_from`, `effective_to`, `is_active`, `notes`, `created_by`, `updated_by`, dan timestamps, serta foreign key ke `students` dan `fee_matrix`.

#### Scenario: Simpan mapping fee siswa baru
- **WHEN** admin menyimpan assignment fee matrix untuk siswa dengan periode efektif valid
- **THEN** sistem menyimpan record mapping dengan relasi unit dan metadata audit lengkap

#### Scenario: Tolak mapping tanpa relasi valid
- **WHEN** request berisi `student_id` atau `fee_matrix_id` yang tidak sesuai unit aktif
- **THEN** sistem menolak penyimpanan dengan pesan validasi yang jelas

### Requirement: Effective Period and Overlap Validation
Sistem SHALL menolak mapping yang overlap periode efektif pada siswa yang sama untuk fee matrix yang sama atau kombinasi mapping aktif yang menyebabkan ambiguitas di tanggal yang sama.

#### Scenario: Overlap period ditolak
- **WHEN** admin membuat mapping baru dengan rentang tanggal bertabrakan terhadap mapping aktif yang relevan
- **THEN** sistem mengembalikan validasi gagal dan tidak menyimpan data

#### Scenario: Open-ended period valid
- **WHEN** admin mengisi `effective_to` null untuk mapping berjalan
- **THEN** sistem menerima data selama tidak ada overlap dengan mapping lain yang aktif

### Requirement: Obligation Generation Priority Rule
Sistem SHALL memprioritaskan mapping fee siswa aktif saat generate obligation/invoice; jika mapping siswa tidak tersedia, sistem SHALL fallback ke matrix kelas+kategori existing.

#### Scenario: Mapping siswa tersedia
- **WHEN** job/aksi generate obligation dijalankan untuk siswa dengan mapping aktif pada periode target
- **THEN** sistem membuat obligation berdasarkan mapping siswa tersebut

#### Scenario: Mapping siswa tidak tersedia
- **WHEN** generate obligation dijalankan untuk siswa tanpa mapping aktif
- **THEN** sistem menggunakan aturan fee matrix kelas+kategori seperti perilaku existing

### Requirement: Student Fee Mapping Management UI
Sistem SHALL menyediakan halaman UI untuk daftar, tambah, ubah status aktif, dan hapus (soft cancel) mapping fee per siswa dengan filter siswa dan periode.

#### Scenario: Admin melihat daftar mapping siswa
- **WHEN** admin membuka halaman student fee mapping
- **THEN** sistem menampilkan daftar mapping dengan informasi siswa, fee matrix, periode efektif, dan status

#### Scenario: Admin menonaktifkan mapping
- **WHEN** admin menandai mapping menjadi non-aktif
- **THEN** mapping tidak lagi dipakai untuk generation periode setelah status dinonaktifkan
