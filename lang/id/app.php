<?php

return [

    /*
    |--------------------------------------------------------------------------
    | String UI Global
    |--------------------------------------------------------------------------
    */

    // Tombol & Aksi
    'button' => [
        'save'           => 'Simpan',
        'cancel'         => 'Batal',
        'filter'         => 'Filter',
        'reset'          => 'Reset',
        'back'           => 'Kembali',
        'edit'           => 'Edit',
        'delete'         => 'Hapus',
        'search'         => 'Cari',
        'detail'         => 'Detail',
        'print'          => 'Cetak',
        'close'          => 'Tutup',
        'confirm'        => 'Konfirmasi',
        'confirm_cancel' => 'Konfirmasi Batal',
        'export'         => 'Ekspor',
        'export_xlsx'    => 'Ekspor XLSX',
        'export_csv'     => 'Ekspor CSV',
        'import'         => 'Impor',
        'create'         => 'Buat',
        'view'           => 'Lihat',
        'remove'         => 'Hapus',
        'restore'        => 'Pulihkan',
        'pay'            => 'Bayar',
        'pay_now'        => 'Bayar Sekarang',
        'view_all'       => 'Lihat Semua',
    ],

    // Label status
    'status' => [
        'completed'      => 'Selesai',
        'cancelled'      => 'Dibatalkan',
        'paid'           => 'Lunas',
        'unpaid'         => 'Belum Lunas',
        'partial'        => 'Bayar Sebagian',
        'active'         => 'Aktif',
        'inactive'       => 'Tidak Aktif',
        'monthly'        => 'Bulanan',
        'one_time'       => 'Sekali Bayar',
        'annual'         => 'Tahunan',
        'registered'     => 'Terdaftar',
        'under_review'   => 'Sedang Direview',
        'accepted'       => 'Diterima',
        'rejected'       => 'Ditolak',
        'enrolled'       => 'Terdaftar Siswa',
        'draft'          => 'Draft',
        'open'           => 'Dibuka',
        'closed'         => 'Ditutup',
    ],

    // Label umum / header kolom
    'label' => [
        'date'            => 'Tanggal',
        'date_from'       => 'Dari Tanggal',
        'date_to'         => 'Sampai Tanggal',
        'time'            => 'Waktu',
        'amount'          => 'Jumlah',
        'total'           => 'Total',
        'total_amount'    => 'Total',
        'actions'         => 'Aksi',
        'class'           => 'Kelas',
        'student'         => 'Siswa',
        'status'          => 'Status',
        'code'            => 'Kode',
        'name'            => 'Nama',
        'notes'           => 'Catatan',
        'no'              => 'No',
        'all'             => 'Semua',
        'period'          => 'Periode',
        'method'          => 'Metode',
        'description'     => 'Deskripsi',
        'nis'             => 'NIS',
        'nisn'            => 'NISN',
        'nis_nisn'        => 'NIS / NISN',
        'category'        => 'Kategori',
        'discount'        => 'Diskon',
        'type'            => 'Jenis',
        'level'           => 'Tingkat',
        'academic_year'   => 'Tahun Ajaran',
        'gender'          => 'Jenis Kelamin',
        'enrollment_date' => 'Tanggal Masuk',
        'due_date'        => 'Jatuh Tempo',
        'outstanding'     => 'Tunggakan',
        'source'          => 'Sumber',
        'items'           => 'Item',
        'allocated'       => 'Dialokasikan',
        'unallocated'     => 'Tidak Dialokasikan',
        'fee_type'        => 'Jenis Biaya',
        'amount_rp'       => 'Jumlah (Rp)',
        'reference'       => 'Referensi',
        'created_by'      => 'Dibuat Oleh',
        'month'           => 'Bulan',
        'year'            => 'Tahun',
    ],

    // Lingkup unit
    'unit' => [
        'current'   => 'Unit Ini',
        'all'       => 'Semua Unit',
        'unit'      => 'Unit',
        'breakdown' => 'Rincian Per-Unit',
    ],

    // Metode pembayaran
    'payment' => [
        'cash'     => 'Tunai',
        'transfer' => 'Transfer',
        'qris'     => 'QRIS',
        'income'   => 'Pemasukan',
        'expense'  => 'Pengeluaran',
    ],

    // Opsi filter
    'filter' => [
        'all_status'  => 'Semua Status',
        'all_periods' => 'Semua Periode',
        'all_classes' => '-- Semua Kelas --',
    ],

    // Navigasi
    'nav' => [
        'dashboard'      => 'Dasbor',
        'transactions'   => 'Transaksi',
        'invoices'       => 'Tagihan',
        'settlements'    => 'Pembayaran',
        'students'       => 'Siswa',
        'classes'        => 'Kelas',
        'categories'     => 'Kategori',
        'fee_types'      => 'Jenis Biaya',
        'fee_matrix'     => 'Matriks Biaya',
        'daily_report'   => 'Laporan Harian',
        'monthly_report' => 'Laporan Bulanan',
        'arrears_report' => 'Laporan Tunggakan',
        'profile'        => 'Profil',
        'log_out'        => 'Keluar',
        'master_data'    => 'Data Master',
        'reports'        => 'Laporan',
        'users'          => 'User',
        'language'       => 'Bahasa',
        'admission'      => 'Penerimaan',
        'admission_periods' => 'Periode Penerimaan',
        'applicants'     => 'Calon Siswa',
    ],

    // Pesan kosong
    'empty' => [
        'transactions'  => 'Tidak ada transaksi ditemukan.',
        'invoices'      => 'Tidak ada tagihan ditemukan.',
        'settlements'   => 'Tidak ada pembayaran ditemukan.',
        'students'      => 'Tidak ada siswa ditemukan.',
        'classes'       => 'Tidak ada kelas ditemukan.',
        'fee_types'     => 'Tidak ada jenis biaya ditemukan.',
        'fee_matrices'  => 'Tidak ada matriks biaya ditemukan.',
        'categories'    => 'Tidak ada kategori ditemukan.',
        'users'         => 'Tidak ada user ditemukan.',
        'entries_date'  => 'Tidak ada entri untuk tanggal ini.',
        'entries'       => 'Tidak ada entri ditemukan.',
        'transactions_short' => 'Tidak ada transaksi.',
        'allocations'   => 'Tidak ada alokasi.',
        'arrears'       => 'Tidak ada invoice jatuh tempo dengan tunggakan.',
        'no_invoices_student' => 'Tidak ada tagihan tertunggak untuk siswa ini.',
        'no_transactions_yet' => 'Belum ada transaksi.',
        'admission_periods' => 'Tidak ada periode penerimaan ditemukan.',
        'applicants'     => 'Tidak ada calon siswa ditemukan.',
    ],

    // Pesan error/flash
    'error' => [
        'unit_inactive'    => 'Unit tidak aktif.',
        'no_switch_perm'   => 'Anda tidak memiliki izin untuk berpindah unit.',
        'no_unit_assigned' => 'Akun Anda belum ditetapkan ke unit manapun. Hubungi administrator.',
        'session_expired'  => 'Sesi Anda telah berakhir karena tidak aktif.',
    ],

    // Placeholder pencarian
    'placeholder' => [
        'search_transaction'  => 'Kode / Siswa / Keterangan',
        'search_invoice'      => 'Cari tagihan atau siswa...',
        'cancellation_reason' => 'Masukkan alasan pembatalan',
        'select_student'      => '-- Pilih Siswa --',
        'select_fee_type'     => '-- Pilih Jenis Biaya --',
        'transfer_ref'        => 'Referensi transfer, dsb.',
        'all_categories'      => 'Semua Kategori',
    ],

    // String form / halaman buat
    'form' => [
        'payment_applied'       => 'Pembayaran akan diterapkan ke tagihan yang dipilih.',
        'select_obligations'    => 'Pilih kewajiban yang akan dimasukkan ke tagihan ini:',
        'selected_total'        => 'Total Terpilih:',
        'min_max'               => 'Min: 1 | Maks: Rp :max',
        'no_obligations'        => 'Tidak ada kewajiban belum tertagih untuk siswa ini.',
        'batch_generate_desc'   => 'Generate batch tagihan untuk semua siswa aktif dengan kewajiban belum dibayar pada periode yang dipilih.',
        'generation_errors'     => 'Error Generasi:',
        'confirm_generate'      => 'Ini akan membuat tagihan untuk semua siswa yang cocok. Lanjutkan?',
        'process_income'        => 'Proses Pemasukan',
        'process_expense'       => 'Proses Pengeluaran',
    ],

];
