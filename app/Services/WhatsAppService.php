<?php

namespace App\Services;

use App\Models\Notification as NotificationModel;
use App\Models\Student;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function sendPaymentConfirmation(Student $student, string $feeType, float $amount): ?NotificationModel
    {
        $template = getSetting('notification_payment_template', '');
        $message = str_replace(
            ['{student_name}', '{fee_type}', '{amount}'],
            [$student->name, $feeType, formatRupiah($amount, 0)],
            $template
        );

        return $this->send($student, 'payment_success', $message);
    }

    public function sendArrearsReminder(Student $student, string $feeType, float $amount): ?NotificationModel
    {
        $template = getSetting('notification_arrears_template', '');
        $message = str_replace(
            ['{student_name}', '{fee_type}', '{amount}'],
            [$student->name, $feeType, formatRupiah($amount, 0)],
            $template
        );

        return $this->send($student, 'arrears_reminder', $message);
    }

    public function send(Student $student, string $type, string $message): ?NotificationModel
    {
        if (!getSetting('whatsapp_enabled', false)) {
            return null;
        }

        $phone = $student->parent_whatsapp;
        if (!$phone || !$this->isValidPhone($phone)) {
            return null;
        }

        $notification = NotificationModel::create([
            'student_id' => $student->id,
            'type' => $type,
            'message' => $message,
            'recipient_phone' => $phone,
            'whatsapp_status' => 'pending',
        ]);

        try {
            $gatewayUrl = getSetting('whatsapp_gateway_url', '');
            $apiKey = config('services.whatsapp.api_key', env('WHATSAPP_API_KEY'));

            if (!$gatewayUrl || !$apiKey) {
                throw new \RuntimeException('WhatsApp gateway not configured.');
            }

            $parsed = parse_url($gatewayUrl);
            if (
                !$parsed
                || !in_array($parsed['scheme'] ?? '', ['https'], true)
                || empty($parsed['host'])
                || in_array($parsed['host'], ['localhost', '127.0.0.1', '0.0.0.0', '[::1]'], true)
                || filter_var($parsed['host'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
                    && !filter_var(gethostbyname($parsed['host']), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
            ) {
                throw new \RuntimeException('WhatsApp gateway URL is not allowed (must be HTTPS, public host).');
            }

            $response = Http::timeout(15)->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->post($gatewayUrl, [
                'phone' => $phone,
                'message' => $message,
            ]);

            $notification->update([
                'whatsapp_status' => $response->successful() ? 'sent' : 'failed',
                'whatsapp_sent_at' => $response->successful() ? now() : null,
                'whatsapp_response' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error("WhatsApp send failed: {$e->getMessage()}", ['notification_id' => $notification->id]);
            $notification->update([
                'whatsapp_status' => 'failed',
                'whatsapp_response' => $e->getMessage(),
            ]);
        }

        return $notification;
    }

    public function retry(NotificationModel $notification): NotificationModel
    {
        return $this->send(
            $notification->student,
            $notification->type,
            $notification->message
        );
    }

    public function isValidPhone(string $phone): bool
    {
        return (bool) preg_match('/^628[0-9]{8,11}$/', $phone);
    }
}
