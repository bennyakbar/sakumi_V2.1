## Context

Struktur kategori expense sudah tersedia, namun operasional masih mengandalkan transaksi expense langsung tanpa lapisan perencanaan anggaran dan rekonsiliasi bank. Modul baru harus tetap kompatibel dengan prinsip immutability transaksi.

## Goals / Non-Goals

**Goals:**
- Menyediakan siklus pengeluaran: rencana budget -> realisasi expense -> monitoring deviasi.
- Menyediakan sesi rekonsiliasi bank bulanan dengan status matching yang dapat diaudit.
- Menjaga integrasi dengan transaksi existing tanpa merusak histori.

**Non-Goals:**
- Tidak mengimplementasikan integrasi API bank otomatis pada change ini.
- Tidak membangun laporan akuntansi formal (neraca/laba-rugi).

## Decisions

1. Pisahkan entitas budget dan entitas realisasi expense.
- Rationale: kontrol anggaran membutuhkan lifecycle berbeda dari transaksi harian.
- Alternative: menaruh budget sebagai metadata transaksi; sulit rekap dan audit.

2. Rekonsiliasi berbasis `reconciliation_session` per akun bank-periode dan `reconciliation_line` per mutasi.
- Rationale: workflow jelas untuk draft -> in_review -> closed.
- Alternative: rekonsiliasi tanpa session; sulit rollback dan audit trail.

3. Matching semi-manual: sistem memberi kandidat match berdasarkan amount/date/reference, user finalisasi match.
- Rationale: data lapangan sering tidak 100% konsisten, perlu kontrol manusia.
- Alternative: auto-match penuh; risiko false positive tinggi.

4. Expense terstruktur tetap menghasilkan record `transactions` type expense sebagai sumber jurnal/kas.
- Rationale: menjaga konsistensi buku kas dan accounting engine existing.
- Alternative: ledger terpisah; berisiko duplikasi sumber kebenaran.

## Risks / Trade-offs

- [Risk] Beban input awal budget per kategori/subkategori -> Mitigation: template budget periodik + copy from previous period.
- [Risk] Data mutasi bank impor tidak konsisten formatnya -> Mitigation: standardisasi import CSV dan validasi kolom wajib.
- [Risk] Rekonsiliasi tidak ditutup tepat waktu -> Mitigation: reminder period closing dan dashboard status outstanding.

## Migration Plan

1. Tambah migration/model budget dan reconciliation.
2. Implement service expense structured + posting ke transaction expense.
3. Implement service rekonsiliasi + UI matching.
4. Tambah laporan ringkas deviasi budget dan selisih rekonsiliasi.
5. Tambah tests, seed sample, dan rollout ke staging.

## Open Questions

- Apakah approval expense perlu multi-level atau cukup single approver pada fase awal?
- Apakah impor mutasi bank cukup CSV manual pada wave ini?
