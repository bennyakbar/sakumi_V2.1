## **Asumsi & Aturan Bisnis (Business Rules)**

### BR-01: Student dapat memiliki lebih dari satu fee mapping aktif (contoh: monthly \+ annual). BR-02: Invoice selalu dibuat dalam status Issued (tidak ada Draft). BR-03: Definisi periode:

* ### Monthly: YYYY-MM (contoh 2026-02)

* ### Annual: AYYYYY (contoh AY2026)

* ### Weekly & Every X Days: harus menggunakan identifier konsisten (lihat BR-04).   BR-04: Untuk konsistensi implementasi, sistem memakai:

* ### Weekly: YYYY-Www (contoh 2026-W06)

* ### Every X Days: YYYY-MM-DD..YYYY-MM-DD (contoh 2026-02-01..2026-02-14)   BR-05: Jika fee mapping berubah di tengah periode, perubahan berlaku mulai periode berikutnya (no prorate).   BR-06: Overpayment tidak diperbolehkan:

* ### Allocation ke invoice tidak boleh melebihi outstanding invoice

* ### Total allocation tidak boleh melebihi settlement amount   BR-07: Satu settlement hanya dapat dialokasikan ke invoice milik student yang sama.   BR-08: Duplikasi invoice dicegah dengan kunci unik:

* ### student\_id \+ fee\_matrix\_id \+ period\_identifier

## **Functional Requirements**

### **A1. Master Data & Configuration**

#### **A1.1 Student**

* Sistem menyimpan student dengan minimal:  
  * Student ID, Nama  
  * Kelas/Level  
  * Category (untuk penentuan fee)  
  * Status enrollment (Active/Inactive)  
* Sistem menyediakan halaman detail student termasuk ringkasan billing:  
  * Total outstanding, total paid, invoice overdue (jika ada)

#### **A1.2 Fee Matrix (Billing Rules)**

* Admin dapat membuat dan mengelola Fee Matrix dengan atribut minimal:  
  * Fee name (mis. SPP Bulanan, Uang Pangkal Tahunan, dll)  
  * Period type: Every X Days / Weekly / Monthly / Annual  
  * Period parameter:  
    * X Days: nilai X  
    * Weekly: hari eksekusi atau definisi minggu (mis. week-of-year atau setiap 7 hari)  
    * Monthly: bulan-tahun target saat generate (mengikuti scheduler)  
    * Annual: AY (mis. AY2026)  
  * Category (mis. Reguler, Beasiswa, dll)  
  * Level/Class applicability (jika dipakai)  
  * Amount/value  
  * Effective start period (kapan mulai berlaku)  
  * Active flag  
* Validasi:  
  * Fee Matrix yang inactive tidak boleh ikut tergenerate  
  * Kombinasi yang “tabrakan” (mis. fee yang sama untuk category+level+periode yang sama) harus dicegah/diwarning (sesuai kebijakan)

#### **A1.3 Student Fee Mapping (Assign Fee ke Student)**

* Admin dapat assign beberapa Fee Matrix ke 1 student  
* Mapping harus punya informasi:  
  * Fee matrix reference  
  * Effective start period  
  * Effective end period (optional)  
  * Status active/inactive  
* Aturan perubahan mid-period:  
  * Jika mapping diubah di tengah periode, sistem **tidak** mengubah invoice periode berjalan  
  * Perubahan baru berpengaruh di periode berikutnya (next cycle)

---

### **A2. Invoice Generation (Job / Scheduler)**

#### **A2.1 Period Setup / Billing Calendar**

* Sistem menyediakan setup untuk menentukan periode generate untuk tiap period type:  
  * Every X days: kapan start dan interval X hari  
  * Weekly: hari apa generate (mis. setiap Senin)  
  * Monthly: tanggal generate (mis. tanggal 1 setiap bulan)  
  * Annual: tanggal generate untuk AY tertentu (mis. awal AY)  
* Sistem menyimpan “Billing Period Identifier” yang konsisten:  
  * Monthly: YYYY-MM (contoh 2026-02)  
  * Annual: AY2026  
  * Weekly: YYYY-WW atau rentang tanggal (harus ditentukan konsisten)  
  * X days: rentang tanggal cycle atau sequence ID (harus ditentukan konsisten)

#### **A2.2 Invoice Generation Job (Batch)**

* Sistem menjalankan job untuk membuat invoice per student berdasarkan Fee Matrix yang aktif dan mapping student.  
* Job bisa dijalankan:  
  * Otomatis (schedule)  
  * Manual (button “Run Now”) oleh user berwenang  
* Input job (minimal):  
  * Period type (X days/weekly/monthly/annual)  
  * Target period identifier (mis. 2026-02, AY2026)  
  * Filter optional: level/class, category, student status  
* Output job:  
  * Jumlah student diproses, invoice dibuat, invoice dilewati (already exists), error  
  * Daftar error per student (contoh: tidak ada mapping aktif)

#### **A2.3 Invoice Creation Rules**

* Sistem membuat invoice “Issued” langsung (tanpa Draft)  
* Satu student dapat menerima lebih dari satu invoice pada periode yang sama jika:  
  * Ada lebih dari satu fee matrix aktif untuk period tersebut (mis. Bulanan \+ Tahunan)  
* Duplikasi:  
  * Sistem **tidak boleh** membuat invoice duplikat untuk kombinasi:  
    * student \+ fee matrix \+ target period identifier  
* Konten invoice minimal:  
  * Invoice number (unik)  
  * Student reference  
  * Period type \+ period identifier (mis. Monthly 2026-02 / Annual AY2026)  
  * Due date (berdasarkan konfigurasi)  
  * Line item:  
    * Fee name  
    * Amount  
  * Total amount  
  * Status: Unpaid/Partially Paid/Paid (initially Unpaid)

---

### **A3. Invoice Management**

#### **A3.1 List & Filter Invoice**

* Sistem menampilkan daftar invoice dengan filter:  
  * Student name / Student ID  
  * Status: Unpaid / Partially Paid / Paid  
  * Period type (monthly/annual/weekly/x-days)  
  * Period identifier (mis. 2026-02, AY2026)  
  * Date range (issue date / due date)  
  * Level/class, category (optional)  
* Sorting:  
  * by due date, status, student name, invoice number  
* Aksi dari list:  
  * View invoice detail  
  * Add settlement (jika belum paid)

#### **A3.2 Invoice Detail**

* Menampilkan:  
  * Header invoice \+ student info  
  * Period info  
  * Line items \+ total  
  * Paid amount, outstanding amount  
  * Settlement/allocations history  
* Aturan:  
  * Invoice “Paid” jika outstanding \= 0  
  * Invoice “Partially Paid” jika outstanding \> 0 dan ada pembayaran  
  * Invoice “Unpaid” jika belum ada pembayaran

---

### **A4. Settlement (Pembayaran) dengan Allocation ke Multiple Invoice**

#### **A4.1 Create Settlement (per Student)**

* User membuat settlement untuk 1 student (bukan langsung per-invoice), dengan data:  
  * Student  
  * Payment date  
  * Payment method (cash/transfer/etc.)  
  * Total paid amount  
  * Reference no (opsional)  
  * Notes (opsional)  
* Setelah settlement dibuat, user melakukan allocation ke satu atau lebih invoice student tsb.

#### **A4.2 Allocation Rules**

* Allocation hanya boleh untuk invoice milik student yang sama  
* Allocation amount per invoice harus:  
  * 0  
  * ≤ outstanding invoice  
* Total allocation:  
  * Tidak boleh melebihi settlement total  
* Overpayment tidak diperbolehkan:  
  * Sistem menolak jika allocation menyebabkan invoice menjadi negatif outstanding  
* Sistem update status invoice otomatis setelah allocation

#### **A4.3 Settlement Detail & History**

* Settlement detail menampilkan:  
  * Total paid  
  * Sisa unallocated (jika belum dialokasikan semua)  
  * Daftar invoice yang dibayar \+ amount per invoice  
* Sistem menyediakan reversal/void settlement (jika diperlukan) dengan dampak:  
  * Mengembalikan outstanding invoice seperti semula  
  * Audit log tersimpan

---

### **A5. Student Billing History**

* Halaman student menampilkan:  
  * List invoice student itu  
  * Filter status \+ period  
  * Ringkasan outstanding \+ paid  
* Quick action:  
  * Create settlement untuk student itu  
  * Allocation ke invoice yang dipilih

---

### **A6. Reports**

Minimal report menu:

* AR / Outstanding report:  
  * per period, per class/level, per category, per student  
* Collection report:  
  * by date range, payment method, user/cashier  
* Invoice issued report:  
  * invoice count & amount by period/type  
* Student statement:  
  * per student: invoice \+ settlement allocations dalam range tanggal

---

### **A7. Audit & Permission (Functional)**

* Role/permission:  
  * Run invoice generation job  
  * Create/modify fee matrix  
  * Assign fee mapping  
  * Create settlement & allocate  
  * View/export reports  
* Audit log untuk:  
  * perubahan fee mapping  
  * job run  
  * settlement \+ allocation \+ reversal

---

## **B. UI Requirements (Halaman & Komponen)**

### **B1. Fee Matrix Setup Page**

* Table fee matrix \+ tombol Add/Edit/Deactivate  
* Form field:  
  * Fee name  
  * Period type \+ parameter (X, weekly rule, etc.)  
  * Category, Level/Class  
  * Amount  
  * Effective start/end  
  * Active toggle  
* Validasi form & pesan error jelas

### **B2. Student Fee Mapping Page (per Student)**

* Panel student info  
* List fee matrix yang di-assign  
* Action:  
  * Add mapping (pilih fee matrix, start period)  
  * End mapping (set end period)  
  * Disable mapping

### **B3. Invoice Generation Job Page**

* Form:  
  * Period type  
  * Target period identifier (dropdown/datepicker sesuai tipe)  
  * Filter (class/level/category/student status)  
* Button:  
  * Preview (opsional tapi bagus): tampilkan berapa invoice akan dibuat  
  * Run Job  
* Result area:  
  * summary \+ error list (download log opsional)

### **B4. Invoice List Page**

* Filter bar (student, status, period type, period id, date range)  
* Table invoice \+ status badge \+ outstanding  
* Row action: View, Add Settlement (shortcut)

### **B5. Invoice Detail Page**

* Header \+ totals  
* Tab:  
  * Line items  
  * Settlements/Allocations  
* Button:  
  * Add Settlement / Allocate Payment (tergantung desain)

### **B6. Settlement Create \+ Allocation Page**

* Stepper atau 2-panel UI:  
  * Input settlement (tanggal, metode, amount)  
  * Allocation grid:  
    * Tampilkan invoice student yang outstanding  
    * Kolom: invoice no, period, total, paid, outstanding, allocate amount (input)  
    * Auto-fill option (opsional): “Allocate oldest first”  
* Validasi realtime:  
  * total allocate ≤ settlement total  
  * allocate ≤ outstanding per invoice

### **B7. Student Detail Page**

* Tab “Invoices”:  
  * list \+ filter  
* Summary cards:  
  * Outstanding, Paid, Overdue count

### **B8. Reports Page**

* Pilih report type  
* Filter sesuai report  
* Tabel hasil \+ export

---

## **C. User Flow (End-to-End)**

### **Flow 1: Setup Fee dan Mapping Student**

1. Admin buat Fee Matrix (Monthly SPP, Annual Registration, dll)  
2. Admin assign Fee Matrix ke student (bisa lebih dari satu)  
3. Sistem menyimpan effective start period

### **Flow 2: Generate Invoice Bulanan**

1. Finance buka “Invoice Generation”  
2. Pilih period type \= Monthly, period \= 2026-02  
3. Klik Run  
4. Sistem:  
   * ambil semua student aktif  
   * cek mapping fee monthly yang aktif untuk 2026-02  
   * buat invoice Issued untuk tiap fee eligible  
   * skip yang sudah ada  
5. Finance melihat hasil summary & error

### **Flow 3: Generate Invoice Tahunan (AY)**

1. Pilih period type \= Annual, period \= AY2026  
2. Run job  
3. Sistem buat invoice annual untuk student yang punya fee annual aktif

### **Flow 4: Terima Pembayaran (1 settlement untuk beberapa invoice)**

1. Finance cari student → klik “Create Settlement”  
2. Input settlement (metode, tanggal, amount)  
3. Sistem tampilkan daftar invoice outstanding student  
4. Finance isi allocate amount ke beberapa invoice  
5. Submit  
6. Sistem validasi:  
   * tidak ada overpayment  
   * total allocate ≤ settlement  
7. Sistem update status invoice (Unpaid/Partial/Paid) \+ simpan settlement

### **Flow 5: Monitoring & Reports**

1. Finance buka Invoice List → filter “Unpaid/Overdue” untuk follow-up  
2. Buka Reports:  
   * Outstanding by class untuk analisa  
   * Collections by date untuk rekonsiliasi

## **Kebutuhan UI (UI Requirements)**

### **5.1 Halaman & Komponen**

#### **UI-01 Fee Matrix Page**

* Table \+ search/filter (period type, category, active)  
* Form Add/Edit:  
  * fee\_name, period\_type, period\_param (dynamic), category, level/class, amount  
  * effective start/end, active  
* Inline validation

#### **UI-02 Student Detail Page**

* Header: student info \+ billing summary cards (Outstanding, Paid)  
* Tab:  
  * Fee Mapping  
  * Invoices (history \+ filters)

#### **UI-03 Fee Mapping Tab**

* List mapping aktif/inaktif  
* Add mapping modal (pilih fee matrix \+ effective start)  
* End mapping (set effective end)

#### **UI-04 Invoice Generation Page**

* Form:  
  * period\_type dropdown  
  * target\_period\_identifier picker (dynamic)  
  * optional filters: level/class, category  
* Button: Run  
* Output: summary \+ error list (download CSV optional)

#### **UI-05 Invoice List Page**

* Filter bar: student, status, period\_type, period\_identifier, date range  
* Table: invoice no, student, period, total, paid, outstanding, status, due date  
* Actions: view detail, create settlement (shortcut)

#### **UI-06 Invoice Detail Page**

* Header \+ totals \+ status badge  
* Section: line items  
* Section: allocations history (settlement id, date, method, amount allocated)

#### **UI-07 Settlement Create & Allocation Page**

Disarankan 2 step dalam 1 halaman:

1. Input settlement data \+ amount  
2. Allocation grid:  
   * list invoice outstanding  
   * input allocate amount per invoice  
   * computed totals: allocated sum & remaining  
* Validasi real-time

#### **UI-08 Reports Page**

* Select report type  
* Filter panel  
* Results table \+ export (CSV/Excel)