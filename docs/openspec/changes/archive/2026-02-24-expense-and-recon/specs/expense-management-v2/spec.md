## ADDED Requirements

### Requirement: Structured Expense Entry
Sistem SHALL menyediakan entitas pengeluaran terstruktur yang merekam kategori, subkategori, tanggal, nominal, vendor/uraian, dan status approval, lalu memposting realisasi ke `transactions` bertipe `expense` setelah disetujui.

#### Scenario: Buat pengeluaran terstruktur
- **WHEN** bendahara membuat entri pengeluaran dengan kategori, subkategori, tanggal, dan nominal valid
- **THEN** sistem menyimpan entri sebagai draft dan belum memposting ke transaksi final

#### Scenario: Approve pengeluaran
- **WHEN** approver menyetujui entri pengeluaran draft
- **THEN** sistem membuat transaksi expense completed yang terhubung ke entri tersebut

### Requirement: Budget vs Realization Tracking
Sistem SHALL menyediakan anggaran periodik per unit, periode, kategori, dan subkategori, serta menghitung realisasi dan deviasi secara otomatis dari expense yang disetujui.

#### Scenario: Lihat deviasi budget
- **WHEN** pengguna membuka laporan budget vs realisasi untuk periode tertentu
- **THEN** sistem menampilkan nilai budget, realisasi, dan selisih per kategori/subkategori

#### Scenario: Cegah duplikasi budget periodik
- **WHEN** admin membuat budget untuk kombinasi unit+periode+kategori+subkategori yang sudah ada
- **THEN** sistem menolak dengan validasi unique constraint
