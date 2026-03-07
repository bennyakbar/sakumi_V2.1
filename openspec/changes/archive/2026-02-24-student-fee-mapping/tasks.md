## 1. Data Structure and Model

- [x] 1.1 Buat migration `student_fee_mappings` lengkap dengan FK, index komposit, dan constraint tanggal efektif.
- [x] 1.2 Buat model `StudentFeeMapping` beserta relasi ke `Student` dan `FeeMatrix`.

## 2. Service Integration

- [x] 2.1 Tambahkan resolver mapping aktif per siswa-periode di service generation.
- [x] 2.2 Integrasikan prioritas mapping siswa ke alur generate obligation/invoice dengan fallback matrix global.

## 3. UI and Access Control

- [x] 3.1 Tambahkan controller, route, policy/permission untuk CRUD mapping fee siswa.
- [x] 3.2 Buat Blade views untuk daftar/form mapping pada modul siswa.

## 4. Validation and Tests

- [x] 4.1 Implementasikan validasi overlap periode dan validasi scope unit.
- [x] 4.2 Tambahkan feature/integration test untuk priority rule dan fallback behavior.
