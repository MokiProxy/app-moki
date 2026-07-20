<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    protected Client $client;
    protected ?string $token;

    public function __construct()
    {
        $this->client = new Client(['verify' => false, 'timeout' => 10]);
        $this->token = \fonnte_api();
    }

    /**
     * Kirim pesan WhatsApp via Fonnte API.
     *
     * @param string $target Nomor HP tujuan (format 08xxx atau 62xxx)
     * @param string $message Isi pesan (mendukung *bold*, _italic_ WhatsApp formatting)
     * @return array ['success' => bool, 'message' => string]
     */
    public function send(string $target, string $message): array
    {
        if (!$this->token) {
            Log::error('WA Gagal: Token Fonnte tidak ditemukan.');
            return ['success' => false, 'message' => 'Token Fonnte tidak ditemukan.'];
        }

        $target = $this->formatPhoneNumber($target);

        try {
            $this->client->post(
                env('FONNTE_API_URL', 'https://api.fonnte.com/send'),
                [
                    'headers' => [
                        'Authorization' => $this->token,
                    ],
                    'form_params' => [
                        'target'  => $target,
                        'message' => $message,
                    ],
                ]
            );

            Log::info('WA Terkirim ke ' . $target);
            return ['success' => true, 'message' => 'Pesan berhasil dikirim ke ' . $target];

        } catch (\Exception $e) {
            Log::error('WA Gagal kirim ke ' . $target . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal mengirim pesan: ' . $e->getMessage()];
        }
    }

    /**
     * Format nomor HP Indonesia ke format internasional.
     * 08xxx -> 62xxx
     */
    protected function formatPhoneNumber(string $phone): string
    {
        $phone = trim($phone);

        if (substr($phone, 0, 1) === '0') {
            return '62' . substr($phone, 1);
        }

        return $phone;
    }
}
