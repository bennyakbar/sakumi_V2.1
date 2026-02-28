<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // School profile
            ['key' => 'school_name', 'value' => 'Madrasah Ibtidaiyah', 'type' => 'string', 'group' => 'school', 'description' => 'Nama sekolah'],
            ['key' => 'school_address', 'value' => '', 'type' => 'string', 'group' => 'school', 'description' => 'Alamat sekolah'],
            ['key' => 'school_phone', 'value' => '', 'type' => 'string', 'group' => 'school', 'description' => 'Nomor telepon sekolah'],
            ['key' => 'school_logo', 'value' => '', 'type' => 'string', 'group' => 'school', 'description' => 'Path ke file logo'],
            ['key' => 'school_email', 'value' => '', 'type' => 'string', 'group' => 'school', 'description' => 'Email sekolah'],

            // Receipt
            ['key' => 'receipt_footer_text', 'value' => 'Terima kasih atas pembayarannya', 'type' => 'string', 'group' => 'receipt', 'description' => 'Teks footer kwitansi'],
            ['key' => 'receipt_show_logo', 'value' => 'true', 'type' => 'boolean', 'group' => 'receipt', 'description' => 'Tampilkan logo di kwitansi'],

            // Notification
            ['key' => 'whatsapp_gateway_url', 'value' => '', 'type' => 'string', 'group' => 'notification', 'description' => 'URL gateway WhatsApp API'],
            ['key' => 'whatsapp_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'notification', 'description' => 'Aktifkan notifikasi WhatsApp'],
            ['key' => 'notification_payment_template', 'value' => 'Yth. Orang Tua/Wali {student_name}, pembayaran {fee_type} sebesar Rp {amount} telah diterima. Terima kasih.', 'type' => 'string', 'group' => 'notification', 'description' => 'Template notifikasi pembayaran'],
            ['key' => 'notification_arrears_template', 'value' => 'Yth. Orang Tua/Wali {student_name}, mengingatkan bahwa terdapat tunggakan {fee_type} sebesar Rp {amount}. Mohon segera melakukan pembayaran.', 'type' => 'string', 'group' => 'notification', 'description' => 'Template notifikasi tunggakan'],

            // Arrears
            ['key' => 'arrears_threshold_months', 'value' => '1', 'type' => 'number', 'group' => 'arrears', 'description' => 'Batas bulan tunggakan sebelum reminder dikirim'],

            // System
            ['key' => 'academic_year_current', 'value' => '2025/2026', 'type' => 'string', 'group' => 'system', 'description' => 'Tahun akademik aktif'],
            ['key' => 'dangerous_permanent_delete_enabled', 'value' => 'false', 'type' => 'boolean', 'group' => 'system', 'description' => 'Izinkan permanent delete superadmin'],
            ['key' => 'inactivity_timeout', 'value' => '7200', 'type' => 'number', 'group' => 'system', 'description' => 'Timeout inaktivitas sesi (detik)'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
