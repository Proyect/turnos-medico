<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class TwilioWhatsAppClient
{
    public function isConfigured(): bool
    {
        return (bool) config('services.twilio_whatsapp.enabled')
            && filled(config('services.twilio_whatsapp.account_sid'))
            && filled(config('services.twilio_whatsapp.auth_token'))
            && filled(config('services.twilio_whatsapp.from'));
    }

    /**
     * @return array{ok: bool, message_id: ?string, response: ?string, error: ?string}
     */
    public function send(string $to, string $body): array
    {
        if (!$this->isConfigured()) {
            return [
                'ok' => false,
                'message_id' => null,
                'response' => null,
                'error' => 'Canal WhatsApp no configurado en .env',
            ];
        }

        $sid = (string) config('services.twilio_whatsapp.account_sid');
        $token = (string) config('services.twilio_whatsapp.auth_token');
        $from = (string) config('services.twilio_whatsapp.from');
        $baseUrl = rtrim((string) config('services.twilio_whatsapp.base_url'), '/');

        $endpoint = "{$baseUrl}/Accounts/{$sid}/Messages.json";

        try {
            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->timeout(10)
                ->post($endpoint, [
                    'From' => $this->normalizeAddress($from),
                    'To' => $this->normalizeAddress($to),
                    'Body' => $body,
                ]);
        } catch (ConnectionException $e) {
            return [
                'ok' => false,
                'message_id' => null,
                'response' => null,
                'error' => 'No se pudo conectar con el proveedor de WhatsApp',
            ];
        }

        if ($response->successful()) {
            return [
                'ok' => true,
                'message_id' => $response->json('sid'),
                'response' => $response->body(),
                'error' => null,
            ];
        }

        return [
            'ok' => false,
            'message_id' => null,
            'response' => $response->body(),
            'error' => $response->json('message') ?? 'Error al enviar WhatsApp',
        ];
    }

    private function normalizeAddress(string $value): string
    {
        return str_starts_with($value, 'whatsapp:') ? $value : 'whatsapp:'.$value;
    }
}
