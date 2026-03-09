<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pesan Flash / Error Backend
    |--------------------------------------------------------------------------
    */

    // Transaksi
    'transaction_created'          => 'Transaksi berhasil dibuat. Nomor: :number',
    'transaction_create_failed'    => 'Gagal membuat transaksi: :error',
    'transaction_cancelled'        => 'Transaksi berhasil dibatalkan.',
    'transaction_no_edit'          => 'Transaksi tidak dapat diedit.',
    'transaction_already_cancelled' => 'Transaksi sudah dibatalkan.',
    'invalid_transaction_type'     => 'Jenis transaksi tidak valid.',
    'expense_not_authorized'       => 'Anda tidak memiliki izin untuk membuat transaksi pengeluaran.',
    'transaction_redirect_to_settlement' => 'Siswa memiliki invoice aktif (:invoice). Gunakan menu Settlement untuk pelunasan agar status invoice terupdate.',
    'student_has_unpaid_obligations_use_invoice' => 'Siswa masih memiliki kewajiban belum dibayar. Buat/gunakan invoice lalu lakukan pembayaran lewat Settlement.',
    'student_required_for_monthly_fee' => 'Transaksi dengan fee bulanan wajib memilih siswa agar tidak melewati alur invoice-settlement.',
    'monthly_fee_must_use_invoice' => 'Fee bulanan wajib diproses lewat alur Invoice/Settlement, bukan transaksi walk-in.',
    'student_payment_must_use_settlement' => 'Pembayaran siswa untuk SPP/fee bulanan wajib dicatat melalui modul Settlement.',
    'cancelled_by_admin'           => 'Dibatalkan oleh administrator',

    // Pembayaran (Settlement)
    'settlement_created'           => 'Pembayaran berhasil dibuat: :number',
    'settlement_create_failed'     => 'Gagal membuat pembayaran: :error',
    'settlement_cancelled'         => 'Pembayaran berhasil dibatalkan.',
    'settlement_already_cancelled' => 'Pembayaran sudah dibatalkan.',
    'settlement_min_allocation'    => 'Pembayaran harus memiliki setidaknya satu alokasi dengan jumlah > 0',
    'allocation_exceeds_settlement' => 'Total alokasi (Rp :allocated) melebihi jumlah pembayaran (Rp :total).',
    'invoice_not_found'            => 'Invoice #:id tidak ditemukan, sudah lunas, atau milik siswa lain.',
    'allocation_exceeds_outstanding' => 'Alokasi untuk invoice :number (Rp :allocated) melebihi tunggakan (Rp :outstanding).',
    'payment_exceeds_outstanding'  => 'Jumlah pembayaran melebihi sisa tunggakan.',
    'invoice_no_balance'           => 'Invoice yang dipilih tidak memiliki sisa tunggakan.',
    'settlement_voided'            => 'Pembayaran berhasil di-void.',
    'settlement_void_failed'       => 'Gagal melakukan void pembayaran: :error',
    'settlement_already_void'      => 'Pembayaran sudah di-void.',
    'settlement_not_active'        => 'Pembayaran tidak dapat di-void (status saat ini: :status).',

    // Tagihan (Invoice)
    'invoice_created'              => 'Tagihan berhasil dibuat: :number',
    'invoice_create_failed'        => 'Gagal membuat tagihan: :error',
    'invoice_cancelled'            => 'Tagihan berhasil dibatalkan.',
    'invoice_generation_complete'  => 'Generasi tagihan selesai: :created dibuat, :skipped dilewati.',
    'invoice_generation_errors'    => 'Error: :count',
    'invoice_generation_failed'    => 'Generasi gagal: :error',
    'unsupported_period_type'      => 'Jenis periode tidak didukung: :type',
    'no_valid_obligations'         => 'Tidak ditemukan kewajiban belum dibayar yang valid.',
    'obligations_already_invoiced' => 'Beberapa kewajiban sudah dibayar atau sudah ditagih.',
    'cannot_cancel_paid_invoice'   => 'Tidak dapat membatalkan tagihan yang sudah lunas.',
    'cannot_cancel_invoice_payments' => 'Tidak dapat membatalkan tagihan yang sudah ada pembayarannya. Batalkan pembayaran terlebih dahulu.',
    'invoice_void_requires_single_allocation_settlement' => 'Tidak dapat membatalkan: settlement :number mencakup beberapa invoice. Silakan void settlement tersebut terlebih dahulu (Settlement > :number > Void), lalu coba batalkan invoice ini kembali.',
    'cancel_paid_invoice_not_authorized' => 'Anda tidak memiliki izin untuk membatalkan invoice yang sudah dibayar. Hubungi bendahara atau super admin.',
    'cancel_paid_invoice_requires_void_permission' => 'Pembatalan invoice yang sudah dibayar memerlukan izin void settlement. Hubungi bendahara atau super admin.',
    'hard_delete_not_allowed'      => 'Penghapusan langsung tidak diizinkan. Gunakan alur batal/void.',
    'settings_updated'            => 'Pengaturan berhasil diperbarui.',
    'academic_year_must_be_consecutive' => 'Tahun ajaran harus berurutan, contoh: 2025/2026.',
    'permanent_delete_not_allowed' => 'Permanent delete hanya boleh untuk superadmin dan harus diaktifkan di Settings.',
    'permanent_delete_confirmation_invalid' => 'Konfirmasi permanent delete tidak valid. Ketik tepat: HAPUS PERMANEN.',
    'permanent_delete_blocked_dependencies' => 'Permanent delete diblokir karena data ini sudah dipakai: :details.',
    'permanent_delete_failed_fk' => 'Permanent delete gagal karena masih ada relasi data yang terkunci oleh database.',
    'user_permanently_deleted' => 'User berhasil dihapus permanen.',
    'student_permanently_deleted' => 'Siswa berhasil dihapus permanen.',
    'class_permanently_deleted' => 'Kelas berhasil dihapus permanen.',
    'category_permanently_deleted' => 'Kategori berhasil dihapus permanen.',
    'fee_type_permanently_deleted' => 'Jenis biaya berhasil dihapus permanen.',
    'fee_matrix_permanently_deleted' => 'Matriks biaya berhasil dihapus permanen.',

    // Master: Jenis Biaya
    'fee_type_created'             => 'Jenis Biaya berhasil dibuat.',
    'fee_type_updated'             => 'Jenis Biaya berhasil diperbarui.',
    'fee_type_deleted'             => 'Jenis Biaya berhasil dihapus.',
    'fee_type_in_use'              => 'Tidak dapat menghapus jenis biaya karena digunakan dalam matriks biaya.',

    // Master: Matriks Biaya
    'fee_matrix_created'           => 'Matriks Biaya berhasil dibuat.',
    'fee_matrix_updated'           => 'Matriks Biaya berhasil diperbarui.',
    'fee_matrix_deleted'           => 'Matriks Biaya berhasil dihapus.',
    'fee_matrix_exists'            => 'Matriks Biaya untuk kombinasi ini sudah ada.',

    // Master: Siswa
    'student_created'              => 'Siswa berhasil ditambahkan.',
    'student_updated'              => 'Siswa berhasil diperbarui.',
    'student_deleted'              => 'Siswa berhasil dihapus.',
    'student_import_success'       => 'Impor siswa berhasil diselesaikan.',
    'student_fee_mapping_created'  => 'Mapping biaya siswa berhasil ditambahkan.',
    'student_fee_mapping_updated'  => 'Mapping biaya siswa berhasil diperbarui.',
    'student_fee_mapping_deactivated' => 'Mapping biaya siswa berhasil dinonaktifkan.',
    'student_fee_mapping_overlap'  => 'Periode yang dipilih bertabrakan dengan mapping aktif lain untuk jenis biaya yang sama.',

    // Master: Kelas
    'class_created'                => 'Kelas berhasil dibuat.',
    'class_updated'                => 'Kelas berhasil diperbarui.',
    'class_deleted'                => 'Kelas berhasil dihapus.',
    'class_has_students'           => 'Tidak dapat menghapus kelas yang masih memiliki siswa.',

    // Master: Kategori
    'category_created'             => 'Kategori Siswa berhasil dibuat.',
    'category_updated'             => 'Kategori Siswa berhasil diperbarui.',
    'category_deleted'             => 'Kategori Siswa berhasil dihapus.',
    'category_has_students'        => 'Tidak dapat menghapus kategori karena masih memiliki siswa.',

    // Manajemen User
    'user_created'                 => 'User berhasil dibuat.',
    'user_updated'                 => 'User berhasil diperbarui.',
    'user_deleted'                 => 'User berhasil dinonaktifkan.',
    'user_password_reset'          => 'Password sementara berhasil dibuat.',
    'users_bulk_updated'           => ':count user berhasil diperbarui.',
    'cannot_deactivate_self'       => 'Anda tidak dapat menonaktifkan akun sendiri.',

    // Middleware / Auth
    'no_unit_assigned'             => 'Akun Anda belum ditetapkan ke unit manapun. Hubungi administrator.',
    'unit_inactive'                => 'Unit tidak aktif.',
    'no_switch_permission'         => 'Anda tidak memiliki izin untuk berpindah unit.',
    'session_expired'              => 'Sesi Anda telah berakhir karena tidak aktif.',
    'unauthorized'                 => 'Aksi tidak diizinkan.',
    'super_admin_only'             => 'Hanya Super Admin yang dapat mengelola peran.',
    'cannot_modify_own_role'       => 'Anda tidak dapat mengubah peran sendiri.',

    // Laporan
    'source_settlement'            => 'Settlement',
    'source_direct_transaction'    => 'Transaksi Langsung',
    'uncategorized'                => 'Tidak Berkategori',
    'general'                      => 'Umum',
    'watermark_original'           => 'ASLI',

    // Penerimaan (PSB)
    'admission_period_created'     => 'Periode penerimaan berhasil dibuat.',
    'admission_period_updated'     => 'Periode penerimaan berhasil diperbarui.',
    'admission_period_deleted'     => 'Periode penerimaan berhasil dihapus.',
    'admission_period_has_applicants' => 'Tidak dapat menghapus periode yang masih memiliki calon siswa.',
    'applicant_created'            => 'Calon siswa berhasil didaftarkan.',
    'applicant_updated'            => 'Data calon siswa berhasil diperbarui.',
    'applicant_deleted'            => 'Data calon siswa berhasil dihapus.',
    'applicant_reviewed'           => 'Calon siswa berhasil masuk tahap review.',
    'applicant_accepted'           => 'Calon siswa berhasil diterima.',
    'applicant_rejected'           => 'Calon siswa berhasil ditolak.',
    'applicant_enrolled'           => 'Calon siswa berhasil didaftarkan sebagai siswa.',
    'applicant_cannot_edit_enrolled' => 'Tidak dapat mengedit calon siswa yang sudah terdaftar.',
    'applicant_cannot_delete_enrolled' => 'Tidak dapat menghapus calon siswa yang sudah terdaftar.',
    'applicants_bulk_updated'      => ':count calon siswa berhasil diperbarui.',
    'admission_invalid_transition' => 'Transisi status tidak valid.',
    'admission_quota_exceeded'     => 'Kuota kelas sudah penuh.',
    'admission_invalid_status'     => 'Status tidak valid untuk operasi bulk.',

    // Label kelompok aging
    'aging_0_30'                   => '0-30 hari',
    'aging_31_60'                  => '31-60 hari',
    'aging_61_90'                  => '61-90 hari',
    'aging_90_plus'                => '>90 hari',

];
