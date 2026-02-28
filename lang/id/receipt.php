<?php

return [

    // Judul dokumen
    'title' => [
        'payment_receipt'    => 'RECEIPT PEMBAYARAN',
        'expense_receipt'    => 'BUKTI PENGELUARAN',
        'invoice'            => 'TAGIHAN PEMBAYARAN',
        'settlement_receipt' => 'BUKTI PELUNASAN',
    ],

    // Label meta
    'label' => [
        'receipt_no'       => 'No. Kwitansi',
        'voucher_no'       => 'No. Bukti',
        'transaction_no'   => 'No. Transaksi',
        'pay_date'         => 'Tanggal Bayar',
        'date'             => 'Tanggal',
        'method'           => 'Metode',
        'payment_method'   => 'Metode Bayar',
        'student_name'     => 'Nama Siswa',
        'class'            => 'Kelas',
        'officer'          => 'Petugas',
        'transaction_type' => 'Jenis Transaksi',
        'notes'            => 'Catatan',
        'phone'            => 'Telp',
        'issued_at'        => 'Diterbitkan pada',
        'printed_at'       => 'Dicetak pada',
        'print_status'     => 'Status Cetak',
        'school_treasurer'   => 'Bendahara Sekolah',
        'settlement_no'      => 'No. Pelunasan',
        'reference_number'   => 'No. Referensi',
    ],

    // Header tabel
    'table' => [
        'no'               => 'No',
        'description'      => 'Uraian Transaksi',
        'expense_desc'     => 'Uraian Pengeluaran',
        'invoice_item'     => 'Item Tagihan',
        'detail'           => 'Keterangan',
        'nominal'          => 'Nominal',
        'period'           => 'Periode',
        'invoice_no'       => 'No. Invoice',
        'invoice_total'    => 'Total Tagihan',
        'allocated'        => 'Jumlah Dialokasi',
    ],

    // Total
    'total' => [
        'payment'          => 'Total Pembayaran',
        'expense'          => 'Total Pengeluaran',
        'invoice'          => 'Total Tagihan',
        'paid'             => 'Sudah Dibayar',
        'outstanding'      => 'Sisa Tagihan',
        'settlement'       => 'Total Pelunasan',
    ],

    // Footer
    'footer' => [
        'official_receipt' => 'Dokumen ini merupakan bukti pembayaran resmi sekolah.',
        'official_expense' => 'Dokumen ini merupakan bukti pengeluaran resmi sekolah.',
        'items_condensed'  => ':count item tambahan diringkas agar tetap 1 lembar',
        'digitally_signed' => 'Ditandatangani secara digital',
        'verification'       => 'Kode Verifikasi',
        'official_settlement' => 'Dokumen ini merupakan bukti pelunasan resmi sekolah.',
    ],

    // Halaman verifikasi
    'verify' => [
        'title'            => 'Verifikasi Kwitansi',
        'valid'            => 'DOKUMEN VALID',
        'invalid'          => 'DOKUMEN TIDAK VALID / KODE TIDAK SESUAI',
        'doc_no'           => 'No. Dokumen',
        'type'             => 'Jenis',
        'date'             => 'Tanggal',
        'total'            => 'Total',
        'status'           => 'Status',
        'code_sent'        => 'Kode Dikirim',
        'code_valid'       => 'Kode Valid',
    ],

    // Label khusus invoice
    'label_invoice_no'     => 'No. Invoice',
    'label_due_date'       => 'Jatuh Tempo',
    'label_period'         => 'Periode',
    'label_status'         => 'Status',
    'label_nis'            => 'NIS',
    'label_digital_sig'    => 'Digital Signature',
    'label_admin_tu'       => 'Admin TU',
    'logo_fallback'        => 'Logo',

    // Lain-lain
    'empty'                => 'Tidak ada item transaksi.',
    'no_invoice_items'     => 'Tidak ada item tagihan.',
    'expense_type'         => 'PENGELUARAN',
    'income_type'          => 'PENDAPATAN',
    'address_not_set'      => 'Alamat sekolah belum diatur',
    'status_disbursed'     => 'DICAIRKAN',

];
