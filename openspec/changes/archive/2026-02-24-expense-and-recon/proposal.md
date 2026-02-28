## Why

Sekolah sudah bisa mencatat expense sebagai transaksi, namun belum memiliki manajemen pengeluaran terstruktur dan proses rekonsiliasi bank bulanan. Hal ini menyulitkan kontrol anggaran, pelacakan realisasi, dan audit kas/bank.

## What Changes

- Menambah modul Expense Management v2: CRUD pengeluaran terstruktur berbasis kategori/subkategori, status approval, dan pelacakan budget vs realisasi.
- Menambah modul Bank Reconciliation: pencocokan mutasi bank terhadap transaksi sistem per periode.
- Menambah laporan ringkas selisih rekonsiliasi dan status match/unmatch.
- Menambahkan permission khusus bendahara/auditor untuk kedua modul.

## Capabilities

### New Capabilities
- `expense-management-v2`: Pengeluaran terstruktur dengan budget, realisasi, approval, dan histori.
- `bank-reconciliation`: Rekonsiliasi bank bulanan dengan workflow matching dan penyelesaian selisih.

### Modified Capabilities
- `transactions`: Penyesuaian alur expense agar dapat terhubung ke entitas pengeluaran terstruktur.

## Impact

- Affected code: model/migration expense budget & reconciliation, controller/service baru, view module baru, route + permission.
- Data: tabel budgeting, expense entries, reconciliation sessions, reconciliation lines.
- Risiko: perubahan proses operasional bendahara dan kebutuhan data import mutasi bank.
