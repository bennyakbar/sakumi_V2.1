# SOP OPERASIONAL SAKUMI
## Standar Operasional Prosedur — Sistem Keuangan Sekolah (SAKUMI)

**Versi:** 2.0  
**Tanggal:** 26 Februari 2026  
**Berlaku untuk:** Seluruh Unit (MI, RA, DTA)

---

## BAB I – KETENTUAN UMUM

### 1.1 Tujuan
SOP ini menetapkan prosedur standar penggunaan aplikasi SAKUMI agar:
- Pencatatan keuangan akurat dan konsisten
- Cash flow harian terlacak dan dapat direkonsiliasi
- Audit trail terjaga untuk setiap transaksi
- Pembagian tugas antar role jelas dan tidak tumpang tindih

### 1.2 Ruang Lingkup
SOP ini berlaku untuk seluruh pengguna sistem SAKUMI dengan role berikut:

| Kode Role | Nama Role | Singkatan |
|---|---|---|
| `super_admin` | Super Administrator | SA |
| `admin_tu_mi` / `admin_tu_ra` / `admin_tu_dta` | Admin Tata Usaha (per unit) | Admin TU |
| `bendahara` | Bendahara | BDH |
| `kepala_sekolah` | Kepala Sekolah | KS |
| `operator_tu` | Operator Tata Usaha | OP-TU |
| `auditor` | Auditor | AUD |
| `cashier` | Kasir | KSR |

### 1.3 Prinsip Dasar Operasional
1. **Immutabilitas Data**: Semua transaksi keuangan bersifat permanen. Tidak ada edit/hapus — koreksi dilakukan melalui pembatalan (cancel) diikuti transaksi pengganti.
2. **Pemisahan Tugas (SoD)**: Penginput tidak dapat menyetujui sendiri exception yang dibuatnya.
3. **Maker-Checker**: Setiap perubahan kritis memerlukan pembuat (maker) dan pemeriksa (checker) yang berbeda.
4. **Audit Trail Permanen**: Seluruh aktivitas system tercatat dan tidak dapat dihapus.
5. **Invoice adalah Hasil**: Status invoice diperbarui otomatis oleh sistem, tidak diinput manual.

### 1.4 Definisi Operasional
| Istilah | Definisi |
|---|---|
| **Invoice** | Tagihan yang diterbitkan kepada siswa berdasarkan jenis biaya dan periode |
| **Settlement** | Alokasi pembayaran terhadap satu atau lebih invoice |
| **Transaction** | Catatan penerimaan atau pengeluaran kas yang sudah terjadi |
| **Outstanding** | Sisa tagihan = Invoice Total − Already Paid |
| **Overdue** | Invoice yang jatuh tempo melewati tanggal hari ini |
| **Aging** | Usia tunggakan dalam hari sejak due date |
| **Kwitansi ORIGINAL** | Kwitansi yang dicetak pertama kali (print_count = 1) |
| **Kwitansi COPY** | Kwitansi yang dicetak ulang (print_count > 1), wajib alasan |
| **Void** | Pembatalan settlement oleh Bendahara/Admin TU dengan alasan wajib |
| **Fee Matrix** | Tabel tarif biaya berdasarkan jenis biaya, kelas, dan kategori siswa |

---

## BAB II – KETENTUAN AKSES DAN KEAMANAN

### 2.1 Ketentuan Login
1. Setiap pengguna memiliki akun pribadi — dilarang berbagi username/password.
2. Password minimal 8 karakter, mengandung: huruf besar, huruf kecil, angka, dan karakter spesial.
3. Session otomatis logout setelah 2 jam tidak aktif.
4. Gagal login 5 kali → akun dikunci sementara 15 menit.
5. Wajib logout setelah selesai menggunakan sistem.

### 2.2 Pengelolaan Akun
1. Permintaan pembuatan, perubahan, atau penonaktifan akun diajukan kepada Super Admin secara resmi (tertulis).
2. Hanya Super Admin yang dapat menetapkan atau mengubah role pengguna.
3. Pengguna tidak dapat mengubah role dirinya sendiri.
4. Akun pegawai yang mengakhiri tugas wajib dinonaktifkan di hari yang sama.

### 2.3 Ketentuan Reprint Kwitansi
| Kondisi | Ketentuan |
|---|---|
| Cetak pertama (ORIGINAL) | Kasir, Operator TU, Admin TU, Bendahara, Super Admin |
| Reprint (COPY) | Admin TU, Bendahara, Super Admin — wajib alasan |
| Alasan reprint valid | Hilang, Rusak, Permintaan orang tua, Lainnya (isi alasan) |
| Log reprint | Dicatat otomatis: user, waktu, alasan, nomor print |

---

## BAB III – SOP PER ROLE

### 3.1 Super Administrator (`super_admin`)

#### A. Tanggung Jawab Utama
- Tata kelola sistem secara menyeluruh
- Pengelolaan user, role, dan permission
- Monitor kesehatan sistem, audit log, dan backup
- Konfigurasi pengaturan sistem (Settings)

#### B. Prosedur Harian
**Pagi:**
1. Login dan akses `Dashboard`.
2. Cek `Health Check` (`/health`) — pastikan status DB, Storage, Queue, dan Cache: **OK**.
3. Review audit log hari sebelumnya untuk event kritis (perubahan role, pembatalan transaksi massal).
4. Konfirmasi status backup otomatis malam sebelumnya.

**Jam Operasional:**
5. Proses permintaan pembuatan/perubahan akun yang sudah disetujui secara resmi.
6. Tangani perubahan master data yang memerlukan otorisasi Super Admin.
7. Monitor antrian pekerjaan (job queue) — pastikan tidak ada failed_jobs > 10.

**Akhir Hari:**
8. Konfirmasi backup scheduled berjalan atau jadwalkan manual jika diperlukan.
9. Review event kritikal audit log hari ini.
10. Logout.

#### C. Batasan
- Super Admin tidak boleh self-approve perubahan keamanan kritikal tanpa approval pihak kedua.
- Penghapusan permanen data (`permanent-delete`) hanya dilakukan dengan preview terlebih dahulu dan persetujuan tertulis dari pimpinan.

#### D. Checklist Harian Super Admin
- [ ] Cek Health Check: DB, Storage, Queue, Cache OK
- [ ] Konfirmasi status backup
- [ ] Review audit log event kritis
- [ ] Proses permintaan akun yang disetujui
- [ ] Monitor failed jobs
- [ ] Logout

---

### 3.2 Admin Tata Usaha (`admin_tu_mi` / `admin_tu_ra` / `admin_tu_dta`)

#### A. Tanggung Jawab Utama
- Pengelolaan master data (siswa, kelas, kategori, jenis biaya, fee matrix)
- Input transaksi pembayaran dan pengeluaran unit
- Pengelolaan invoice dan settlement
- Pemantauan tunggakan dan laporan harian

#### B. Prosedur Harian

**Pagi:**
1. Login dan cek `Dashboard` — lihat ringkasan pembayaran hari ini dan total tunggakan.
2. Buka `Arrears Report` — catat siswa dengan aging tertinggi untuk ditindaklanjuti.

**Jam Operasional — Saat Menerima Pembayaran:**
3. Buka menu `Invoices` atau `Arrears Report`.
4. Cari siswa berdasarkan nama/NIS.
5. Pilih invoice yang akan dibayar — verifikasi: nama siswa, jenis tagihan, outstanding.
6. Klik **Pay Now** → akan masuk form `Create Settlement`.
7. Isi data settlement:
   - Nominal pembayaran (≤ outstanding)
   - Metode pembayaran (tunai / transfer / QRIS)
   - Tanggal pembayaran
   - Catatan (opsional)
8. Klik **Simpan** — sistem akan memvalidasi nominal dan memperbarui outstanding secara otomatis.
9. Cetak kwitansi ORIGINAL dan serahkan kepada pembayar.
10. Verifikasi: outstanding invoice berkurang sesuai pembayaran yang diterima.

**Penginputan Invoice:**
11. Buka `Invoices → Create` untuk membuat invoice manual.
12. Atau gunakan `Generate Invoice` untuk pembuatan massal berdasarkan fee matrix.
13. Pastikan: siswa aktif, jenis biaya, periode, dan nominal sudah benar sebelum simpan.

**Akhir Hari:**
14. Buka `Daily Report` → pilih tanggal hari ini.
15. Cocokkan total penerimaan dengan bukti kas/bank fisik.
16. Jika ada selisih, identifikasi dan dokumentasikan sebelum tutup hari.
17. Logout.

#### C. Prosedur Mingguan
1. Review data master: nonaktifkan siswa yang sudah keluar/lulus.
2. Rekap invoice overdue tanpa progres pembayaran — eskalasikan ke Bendahara.
3. Koordinasikan siswa dengan aging > 90 hari dengan Bendahara dan Kepala Sekolah.
4. Verifikasi fee matrix — pastikan tarif efektif dan aktif sudah benar.

#### D. Prosedur Pembatalan (Cancel) Transaksi/Settlement
1. Hanya lakukan pembatalan jika ada kesalahan input yang sudah terdokumentasi.
2. Buka transaksi/settlement yang bersangkutan.
3. Klik **Cancel** / **Void**.
4. Isi alasan pembatalan secara jelas dan lengkap.
5. Sistem akan memperbarui status dan outstanding invoice secara otomatis.
6. Informasikan kepada Bendahara untuk verifikasi.

#### E. Checklist Harian Admin TU
- [ ] Cek dashboard dan arrears report
- [ ] Proses semua pembayaran yang diterima hari ini
- [ ] Cetak kwitansi untuk setiap pembayaran
- [ ] Verifikasi outstanding berkurang sesuai pembayaran
- [ ] Daily report cocok dengan kas/bank fisik
- [ ] Tidak ada settlement gagal/duplikat
- [ ] Logout

---

### 3.3 Bendahara (`bendahara`)

#### A. Tanggung Jawab Utama
- Kontrol keuangan dan rekonsiliasi harian
- Persetujuan exception keuangan (pembatalan, penyesuaian)
- Reprint kwitansi berdasarkan alasan yang valid
- Pengelolaan pengeluaran (expenses) dan rekonsiliasi bank
- Pelaporan keuangan bulanan

#### B. Prosedur Harian

**Pagi:**
1. Login dan akses `Dashboard` — review KPI keuangan.
2. Buka `Daily Report` → verifikasi semua transaksi kemarin sudah terekap.
3. Review settlement bernilai besar atau tidak lazim.

**Jam Operasional:**
4. Proses permintaan void/cancel settlement dari Admin TU/Kasir — verifikasi alasan dan dokumen pendukung.
5. Eksekusi reprint kwitansi jika disetujui — pastikan alasan tercatat.
6. Verifikasi expense yang diajukan — approve jika valid, tolak jika tidak sesuai.
7. Lakukan rekonsiliasi bank: buka `Bank Reconciliation`, import mutasi bank, match dengan transaksi sistem.

**Akhir Hari:**
8. Rekonsiliasi final antara total settlement dan mutasi kas/bank.
9. Generate dan arsipkan `Daily Report`.
10. Catat selisih jika ada dan dokumentasikan.
11. Logout.

#### C. Prosedur Mingguan
1. Review `Arrears Report` — analisis aging per bucket (0-30, 31-60, 61-90, >90 hari).
2. Pastikan tidak ada overpayment (outstanding < 0 tidak boleh terjadi).
3. Validasi seluruh pembatalan settlement beserta alasan dan pelakunya.
4. Rekonsiliasi mutasi bank mingguan.

#### D. Prosedur Bulanan
1. Generate `Monthly Report` — verifikasi total penerimaan vs target.
2. Konfirmasi saldo piutang outstanding akhir bulan.
3. Arsipkan laporan bulanan (PDF + Excel) dengan nama file: `UNITKODE_LaporanBulanan_YYYYMM_BDH.xlsx`.
4. Siapkan laporan untuk Kepala Sekolah dan Yayasan.
5. Review dan update fee matrix jika ada perubahan tarif.

#### E. Prosedur Rekonsiliasi Bank
1. Buka `Bank Reconciliation → New Session`.
2. Set periode rekonsiliasi (tanggal mulai – tanggal akhir).
3. Import file mutasi bank (format CSV/Excel).
4. Sistem menampilkan daftar baris mutasi bank di sisi kiri dan transaksi sistem di sisi kanan.
5. Cocokkan (match) setiap baris mutasi bank dengan transaksi:
   - Klik baris mutasi → klik transaksi yang sesuai → klik **Match**.
   - Jika ada transaksi di bank tidak ada di sistem: tandai sebagai "unmatched" dan investigasi.
6. Setelah seluruh baris dicocokkan → klik **Close Session**.
7. Cetak laporan rekonsiliasi sebagai arsip.

#### F. Checklist Harian Bendahara
- [ ] Verifikasi daily report kemarin
- [ ] Review settlement besar/tidak lazim
- [ ] Proses permintaan void/cancel dengan alasan valid
- [ ] Rekonsiliasi bank vs sistem
- [ ] Verifikasi expense yang diajukan
- [ ] Arsipkan laporan harian
- [ ] Logout

---

### 3.4 Kepala Sekolah (`kepala_sekolah`)

#### A. Tanggung Jawab Utama
- Pengawasan eksekutif kinerja keuangan unit
- Review laporan dan tunggakan
- Persetujuan kebijakan tindak lanjut penagihan

#### B. Prosedur Harian
1. Login dan akses `Dashboard` — lihat ringkasan KPI: total penerimaan hari ini, outstanding, jumlah siswa dengan tunggakan.
2. Cek apakah ada anomali (penerimaan sangat rendah dibanding baseline, pembatalan massal).
3. Jika ada anomali: minta klarifikasi kepada Bendahara/Admin TU secara langsung.
4. Logout.

#### C. Prosedur Mingguan
1. Buka `Arrears Report` — filter per kelas, lihat siapa saja dengan tunggakan signifikan.
2. Identifikasi pola: apakah ada kelas/periode tertentu yang tunggakannya selalu tinggi?
3. Keluarkan instruksi tindak lanjut kepada Admin TU/Bendahara.
4. Review `Student Statement` untuk siswa dengan outstanding besar.

#### D. Prosedur Bulanan
1. Buka `Monthly Report` — review performa pendapatan bulan ini vs bulan lalu.
2. Review `Collection Report` — lihat tingkat kolektibilitas (% invoice terbayar).
3. Review `AR Outstanding Report` — piutang yang belum terbayar per tanggal akhir bulan.
4. Setujui rencana tindak lanjut penagihan untuk bulan berikutnya.
5. Arsipkan laporan dengan nama file: `UNITKODE_RingkasanKepsek_YYYYMM.pdf`.

#### E. Mode Akses Kepala Sekolah
**Akses yang dimiliki:** View-only untuk seluruh data keuangan.  
**Tidak dapat:** Membuat transaksi, settlement, invoice, atau mengubah data apapun.

#### F. Checklist Bulanan Kepala Sekolah
- [ ] Review monthly report — pendapatan tervalidasi
- [ ] Review arrears aging — pola tunggakan teridentifikasi
- [ ] Review collection rate
- [ ] Beri instruksi tindak lanjut ke tim operasional
- [ ] Arsipkan laporan eksekutif

---

### 3.5 Operator Tata Usaha (`operator_tu`)

#### A. Tanggung Jawab Utama
- Pengelolaan data master (siswa, kelas, kategori)
- Input transaksi pembayaran (tanpa cancel)
- Pembuatan invoice dan settlement
- Cetak kwitansi ORIGINAL

#### B. Prosedur Harian
1. Login dan akses `Dashboard`.
2. Saat terdapat siswa baru: buka `Master → Students → Create`, isi data lengkap, simpan.
3. Saat menerima pembayaran: ikuti prosedur yang sama dengan Admin TU (poin 3–10 di SOP Admin TU).
4. **Penting:** Operator TU TIDAK dapat membatalkan transaksi — jika ada kesalahan, eskalasikan ke Admin TU atau Bendahara.
5. Akhir hari: cek `Daily Report` untuk verifikasi input hari ini.
6. Logout.

#### C. Import Data Siswa (Massal)
1. Buka `Master → Students → Import`.
2. Unduh template CSV dari tombol **Download Template**.
3. Isi data siswa di template sesuai format (NIS, Nama, Kelas, Kategori, dll).
4. Upload file `.csv` yang sudah diisi.
5. Sistem akan validasi dan preview hasil import.
6. Jika ada error: perbaiki di file dan upload ulang.
7. Konfirmasi import jika semua data valid.

#### D. Checklist Harian Operator TU
- [ ] Input semua siswa baru (jika ada)
- [ ] Proses semua pembayaran hari ini
- [ ] Cetak kwitansi untuk setiap pembayaran
- [ ] Daily report diverifikasi
- [ ] Logout

---

### 3.6 Auditor (`auditor`)

#### A. Tanggung Jawab Utama
- Pemeriksaan dan verifikasi data keuangan
- Review audit log
- Pelaporan hasil audit kepada manajemen

#### B. Prosedur Kerja
1. Login dengan akun auditor (view-only access).
2. Akses modul yang diperlukan untuk audit:
   - `Transactions` → review transaksi periode audit
   - `Invoices` → verifikasi kesesuaian invoice vs pembayaran
   - `Settlements` → verifikasi alokasi pembayaran
   - `Reports` → Daily, Monthly, Arrears, AR Outstanding, Collection, Cash Book
   - `Audit Log` → review aktivitas pengguna
3. Export laporan yang dibutuhkan dalam format Excel/PDF.
4. Nama file ekspor: `UNITKODE_Audit_YYYYMMDD_AUD.xlsx`.
5. Auditor TIDAK dapat membuat, mengubah, atau menghapus data apapun.

#### C. Ketentuan Auditor
- Akses auditor bersifat read-only penuh.
- Seluruh akses auditor tercatat dalam audit log sistem.
- Hasil audit dilaporkan di luar sistem (dokumen fisik/email resmi) kepada pimpinan.

---

### 3.7 Kasir (`cashier`)

#### A. Tanggung Jawab Utama
- Input pembayaran siswa di loket
- Cetak kwitansi ORIGINAL
- Rekonsiliasi kas harian

#### B. Prosedur Harian

**Pagi:**
1. Login dan verifikasi tanggal sesi sudah benar.
2. Siapkan saldo awal kas.

**Jam Operasional:**
3. Saat siswa membayar:
   - Buka `Transactions → Create`.
   - Cari siswa berdasarkan NIS/nama — pastikan benar.
   - Pilih item pembayaran.
   - Input nominal dan metode pembayaran.
   - Verifikasi ulang: nama siswa + nominal sebelum simpan.
   - Klik **Simpan**.
   - Cetak kwitansi ORIGINAL dan serahkan kepada siswa/wali.
4. Jika ada kesalahan setelah simpan: JANGAN coba ubah data — laporkan segera kepada Admin TU/Bendahara untuk proses void/cancel.
5. Kasir TIDAK dapat melakukan reprint kwitansi — eskalasikan ke Admin TU/Bendahara dengan alasan tertulis.

**Akhir Hari:**
6. Hitung total kas diterima hari ini.
7. Cocokkan dengan total transaksi di sistem untuk hari ini.
8. Catat selisih jika ada (dengan keterangan).
9. Serahkan kas kepada Bendahara.
10. Logout.

#### C. Ketentuan Kasir
- Kasir hanya memiliki akses ke menu: Dashboard, Transactions, Receipts.
- Tidak dapat membuat/melihat Invoice atau Settlement.
- Tidak dapat melihat laporan keuangan detail (Daily/Monthly/Arrears)
- Reprint kwitansi oleh kasir dilarang — wajib eskalasi.

#### D. Checklist Harian Kasir
- [ ] Verifikasi tanggal sesi dan saldo awal
- [ ] Setiap pembayaran langsung diinput dan dicetak kwitansi
- [ ] Rekonsiliasi kas vs sistem di akhir shift
- [ ] Laporkan selisih kepada Bendahara
- [ ] Logout

---

## BAB IV – ALUR ANTAR ROLE

### 4.1 Alur Pembayaran Siswa
```
Siswa/Wali Bayar
       ↓
Kasir / Operator TU / Admin TU
  → Input Transaksi / Settlement
  → Sistem perbarui invoice outstanding otomatis
  → Cetak Kwitansi ORIGINAL
       ↓
Bendahara
  → Rekonsiliasi Harian
  → Verifikasi Daily Report
       ↓
Kepala Sekolah
  → Review Summary Dashboard
  → Approval arahan kebijakan (jika diperlukan)
```

### 4.2 Alur Pembatalan / Void
```
Kasir/Operator TU → menemukan kesalahan input
       ↓
Eskalasi ke Admin TU / Bendahara (dengan penjelasan tertulis)
       ↓
Admin TU / Bendahara → Verifikasi bukti kesalahan
       ↓
Admin TU / Bendahara → Eksekusi Cancel/Void di sistem (wajib isi alasan)
       ↓
Sistem → perbarui outstanding invoice
       ↓
(Opsional) Input ulang transaksi yang benar
       ↓
Kepala Sekolah → notifikasi jika nilai signifikan
```

### 4.3 Alur Reprint Kwitansi
```
Kasir/Operator TU/Siswa → meminta reprint
       ↓
Admin TU / Bendahara → verifikasi alasan (hilang/rusak/permintaan ortu)
       ↓
Admin TU / Bendahara → eksekusi reprint di sistem
  (wajib pilih alasan dari dropdown)
       ↓
Sistem → cetak kwitansi COPY dengan status "Reprint #N"
  + catat: user, waktu, alasan, nomor cetak di audit log
```

### 4.4 Matrix Persetujuan

| Aktivitas | Maker | Checker/Approver |
|---|---|---|
| Input transaksi pembayaran | Kasir / Operator TU / Admin TU | Bendahara (rekonsiliasi) |
| Pembatalan transaksi | Admin TU | Bendahara |
| Void settlement | Admin TU / Bendahara | Bendahara / Super Admin |
| Reprint kwitansi | Admin TU / Bendahara | Bendahara |
| Pembuatan akun baru | Pemohon (tertulis) | Super Admin |
| Perubahan fee matrix | Admin TU / Bendahara | Kepala Sekolah (kebijakan) |
| Approve expense | Admin TU / Bendahara | Bendahara |
| Rekonsiliasi bank | Bendahara | Bendahara (self-verify) |

---

## BAB V – SLA OPERASIONAL

| Kegiatan | Batas Waktu |
|---|---|
| Input pembayaran setelah diterima | Hari yang sama, sebelum tutup operasional |
| Rekonsiliasi harian | Sebelum jam operasional berakhir |
| Tindak lanjut tunggakan >90 hari | Dibahas dalam rapat mingguan |
| Void/cancel diproses | Hari yang sama dengan eskalasi |
| Reprint kwitansi diproses | Hari yang sama dengan permintaan resmi |
| Laporan bulanan disiapkan | Paling lambat tanggal 5 bulan berikutnya |

---

## BAB VI – PENANGANAN SITUASI DARURAT

### 6.1 Kesalahan Input Transaksi
1. Jangan panik dan jangan coba "memperbaiki" dengan input transaksi tambahan.
2. Catat detail kesalahan (nomor transaksi, nominal, nama siswa).
3. Laporkan kepada Admin TU / Bendahara segera.
4. Admin TU / Bendahara melakukan void/cancel dengan alasan resmi.
5. Buat transaksi baru yang benar.

### 6.2 Sistem Tidak Dapat Diakses
1. Gunakan formulir manual sementara untuk mencatat pembayaran.
2. Laporkan kepada Super Admin.
3. Investigasi melalui Health Check (`/health/live`) jika memungkinkan.
4. Setelah sistem kembali normal, input seluruh catatan manual.

### 6.3 Dugaan Pelanggaran Keamanan
1. Logout segera dari semua sesi.
2. Laporkan kepada Super Admin dan Kepala Sekolah.
3. Super Admin segera nonaktifkan akun yang diduga dikompromikan.
4. Review audit log untuk identifikasi aktivitas tidak normal.
5. Ganti password seluruh pengguna jika perlu.

---

## BAB VII – PENUTUP

SOP ini bersifat mengikat untuk seluruh pengguna SAKUMI sesuai role masing-masing. Setiap perubahan proses bisnis yang berdampak pada alur sistem harus:
1. Diajukan secara tertulis kepada Super Admin dan Kepala Sekolah.
2. Dikaji dampaknya terhadap integritas keuangan.
3. Disosialisasikan kepada seluruh pengguna terdampak sebelum berlaku.

**Dokumen ini dievaluasi dan diperbarui setiap 6 bulan atau bila ada perubahan signifikan pada sistem.**
