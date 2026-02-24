## Context

Modul report saat ini sudah menyediakan `daily`, `monthly`, dan `arrears`, namun belum mencakup kebutuhan AR outstanding per dimensi, collection report per cashier/metode bayar, student statement, dan cash book dengan saldo awal-akhir. Implementasi harus mempertahankan dukungan unit scope vs consolidated scope untuk super admin.

## Goals / Non-Goals

**Goals:**
- Menambahkan empat laporan operasional dengan filter yang dibutuhkan sekolah.
- Menjaga konsistensi pola controller/view/export yang sudah dipakai modul report.
- Menyediakan performa query memadai dengan agregasi SQL, index, dan pagination.

**Non-Goals:**
- Tidak membangun modul akuntansi full (neraca/laba-rugi) pada change ini.
- Tidak mengubah aturan bisnis settlement/invoice existing.

## Decisions

1. Tambah endpoint dalam `ReportController` untuk menjaga cohesion modul report.
- Rationale: fitur masih berada pada domain reporting; menghindari fragmentasi controller.
- Alternative: controller terpisah per report; ditunda sampai jumlah laporan bertambah signifikan.

2. Gunakan query SQL agregasi (join/subquery) + pagination server-side.
- Rationale: dataset transaksi sekolah dapat meningkat; server-side lebih stabil dibanding kalkulasi penuh di Blade.
- Alternative: olah data penuh di PHP collection; ditolak karena memory footprint.

3. Cash Book dihitung dari opening balance harian + mutasi income/expense hari berjalan.
- Rationale: sesuai pola buku kas bendahara (saldo awal -> penerimaan -> pengeluaran -> saldo akhir).
- Alternative: hanya tampil transaksi harian tanpa running balance; tidak memenuhi kebutuhan operasional.

4. Export mengikuti paket `maatwebsite/excel` yang sudah dipakai arrears export.
- Rationale: reuse dependency dan pola yang sudah stabil.
- Alternative: export manual CSV; kurang konsisten untuk format kompleks.

## Risks / Trade-offs

- [Risk] Query berat pada rentang tanggal panjang -> Mitigation: index pada kolom tanggal/status, batasi default range, pagination.
- [Risk] Perbedaan definisi kas vs bank pada transaksi transfer -> Mitigation: parameter filter sumber kas dan dokumentasi definisi laporan.
- [Risk] Kebingungan pengguna antar laporan baru -> Mitigation: label/filter konsisten dan deskripsi singkat di UI.

## Migration Plan

1. Tambah permission reports baru dan assign ke role terkait.
2. Tambah route GET + export endpoint.
3. Implement query/service + view blade.
4. Tambah test feature untuk filter dan total per laporan.
5. Rollout bertahap ke staging, validasi dengan sampel data bulan berjalan.

## Open Questions

- Apakah collection report perlu memisahkan settlement invoice vs income direct non-siswa secara default?
- Untuk cash book, apakah transaksi transfer dianggap mutasi kas atau harus dipisah kas/bank ledger?
