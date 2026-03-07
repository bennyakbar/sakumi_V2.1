## ADDED Requirements

### Requirement: Reconciliation Session Lifecycle
Sistem SHALL menyediakan sesi rekonsiliasi bank per akun bank dan periode dengan status `draft`, `in_review`, dan `closed`.

#### Scenario: Buat sesi rekonsiliasi bulanan
- **WHEN** bendahara memilih akun bank dan periode yang belum direkonsiliasi
- **THEN** sistem membuat sesi baru berstatus draft

#### Scenario: Tutup sesi rekonsiliasi
- **WHEN** pengguna menutup sesi dengan semua item terselesaikan
- **THEN** sistem mengubah status sesi menjadi closed dan mengunci perubahan item

### Requirement: Match System Transactions with Bank Mutations
Sistem SHALL mendukung pencocokan mutasi bank dengan transaksi sistem menggunakan parameter amount, tanggal, dan referensi, dengan status baris `matched`, `unmatched`, atau `adjusted`.

#### Scenario: Match mutasi dengan transaksi
- **WHEN** pengguna memilih mutasi bank dan transaksi kandidat yang sesuai
- **THEN** sistem menyimpan relasi match dan menandai kedua sisi sebagai matched

#### Scenario: Terdapat selisih rekonsiliasi
- **WHEN** terdapat mutasi atau transaksi yang belum cocok pada periode berjalan
- **THEN** sistem menampilkan daftar unmatched beserta total selisih rekonsiliasi

### Requirement: Reconciliation Audit Trail
Sistem SHALL mencatat audit trail untuk setiap aksi rekonsiliasi (create session, import mutation, match/unmatch, close session) termasuk actor dan timestamp.

#### Scenario: Lihat histori aksi rekonsiliasi
- **WHEN** auditor membuka detail sesi rekonsiliasi
- **THEN** sistem menampilkan urutan log aksi dengan pengguna dan waktu kejadian
