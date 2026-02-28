## 1. Expense Management v2 Foundation

- [x] 1.1 Buat migration/model untuk expense entries dan budget periodik per kategori/subkategori.
- [x] 1.2 Tambahkan controller, service, route, dan permission untuk alur draft -> approve -> posted.

## 2. Integration with Transactions

- [x] 2.1 Hubungkan approval expense dengan pembuatan `transactions` type expense yang immutable.
- [x] 2.2 Tambahkan laporan budget vs realisasi pada modul pengeluaran.

## 3. Bank Reconciliation Module

- [x] 3.1 Buat migration/model sesi rekonsiliasi dan baris mutasi/matching.
- [x] 3.2 Implementasikan UI sesi rekonsiliasi (buat sesi, import mutasi CSV, matching, close).
- [x] 3.3 Implementasikan kalkulasi selisih dan status matched/unmatched/adjusted.

## 4. Testing and Rollout

- [x] 4.1 Tambahkan feature test untuk approval expense, deviasi budget, dan lifecycle rekonsiliasi.
- [x] 4.2 Siapkan data seed/staging script untuk uji rekonsiliasi bulanan.
