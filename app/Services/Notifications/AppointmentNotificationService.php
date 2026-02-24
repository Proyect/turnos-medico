<?php

namespace App\Services\Notifications;

use App\Mail\AppointmentNotificationMail;
use App\Models\Appointment;
use App\Models\AppointmentNotificationLog;
use App\Services\WhatsApp\TwilioWhatsAppClient;
use Illuminate\Support\Facades\Mail;

class AppointmentNotificationService
{
    public const EVENT_REQUESTED = 'appointment_requested';
    public const EVENT_ARRIVED = 'appointment_arrived';
    public const EVENT_PAID = 'appointment_paid';

    public function __construct(private TwilioWhatsAppClient $whatsAppClient) {}

    public function notifyRequested(Appointment $appointment, ?int $sentBy = null): void
    {
        $this->dispatch($appointment, self::EVENT_REQUESTED, $sentBy);
    }

    public function notifyArrived(Appointment $appointment, ?int $sentBy = null): void
    {
        $this->dispatch($appointment, self::EVENT_ARRIVED, $sentBy);
    }

    public function notifyPaid(Appointment $appointment, ?int $sentBy = null): void
    {
        $this->dispatch($appointment, self::EVENT_PAID, $sentBy);
    }

    private function dispatch(Appointment $appointment, string $event, ?int $sentBy): void
    {
        if (!(bool) config('notifications.appointment.enabled', true)) {
            return;
        }

        $appointment->loadMissing(['doctor.specialty']);
        [$subject, $message] = $this->buildMessage($appointment, $event);

        if ((bool) config('notifications.appointment.email_enabled', true) && filled($appointment->patient_email)) {
            $this->sendEmail($appointment, $event, $subject, $message, $sentBy);
        }

        if (
            (bool) config('notifications.appointment.whatsapp_enabled', true)
            && $this->whatsAppClient->isConfigured()
            && filled($appointment->phone)
        ) {
            $this->sendWhatsApp($appointment, $event, $subject, $message, $sentBy);
        }
    }

    private function sendEmail(
        Appointment $appointment,
        string $event,
        string $subject,
        string $message,
        ?int $sentBy
    ): void {
        try {
            Mail::to((string) $appointment->patient_email)
                ->send(new AppointmentNotificationMail($appointment, $subject, $message));

            $this->log([
                'appointment_id' => $appointment->id,
                'sent_by' => $sentBy,
                'event' => $event,
                'channel' => 'email',
                'recipient' => $appointment->patient_email,
                'subject' => $subject,
                'message' => $message,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            $this->log([
                'appointment_id' => $appointment->id,
                'sent_by' => $sentBy,
                'event' => $event,
                'channel' => 'email',
                'recipient' => $appointment->patient_email,
                'subject' => $subject,
                'message' => $message,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function sendWhatsApp(
        Appointment $appointment,
        string $event,
        string $subject,
        string $message,
        ?int $sentBy
    ): void {
        $recipientPhone = $this->normalizePhoneForWhatsApp((string) $appointment->phone);
        if ($recipientPhone === null) {
            $this->log([
                'appointment_id' => $appointment->id,
                'sent_by' => $sentBy,
                'event' => $event,
                'channel' => 'whatsapp',
                'recipient' => $appointment->phone,
                'subject' => $subject,
                'message' => $message,
                'status' => 'failed',
                'error_message' => 'Telefono no valido para WhatsApp. Usa formato internacional (ejemplo: +5491122334455).',
            ]);

            return;
        }

        $result = $this->whatsAppClient->send($recipientPhone, $message);

        $this->log([
            'appointment_id' => $appointment->id,
            'sent_by' => $sentBy,
            'event' => $event,
            'channel' => 'whatsapp',
            'recipient' => $recipientPhone,
            'subject' => $subject,
            'message' => $message,
            'status' => $result['ok'] ? 'sent' : 'failed',
            'provider_message_id' => $result['message_id'],
            'provider_response' => $result['response'],
            'error_message' => $result['error'],
            'sent_at' => $result['ok'] ? now() : null,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function buildMessage(Appointment $appointment, string $event): array
    {
        $schedule = optional($appointment->scheduled_at)->format('d/m/Y H:i') ?? '-';
        $doctor = $appointment->doctor?->name ?? '-';
        $specialty = $appointment->specialty?->name ?? '-';
        $status = $appointment->status_label;

        if ($event === self::EVENT_ARRIVED) {
            $subject = 'Asistencia confirmada en recepcion';
            $intro = 'Registramos tu llegada en recepcion.';
        } elseif ($event === self::EVENT_PAID) {
            $subject = 'Pago confirmado';
            $intro = 'Confirmamos el pago de tu turno.';
        } else {
            $subject = 'Turno solicitado correctamente';
            $intro = 'Tu solicitud de turno fue registrada.';
        }

        $body = "{$intro}\n\n"
            ."Detalle del turno:\n"
            ."- Fecha y hora: {$schedule}\n"
            ."- Especialidad: {$specialty}\n"
            ."- Medico: {$doctor}\n"
            ."- Estado actual: {$status}\n\n"
            ."Si detectas algun error, comunicate con recepcion.";

        return [$subject, $body];
    }

    private function normalizePhoneForWhatsApp(string $phone): ?string
    {
        $normalized = preg_replace('/[\s\-\(\)]/', '', trim($phone));
        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (preg_match('/^\+[1-9]\d{6,14}$/', $normalized) === 1) {
            return $normalized;
        }

        if (preg_match('/^[1-9]\d{6,14}$/', $normalized) === 1) {
            return '+'.$normalized;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function log(array $payload): void
    {
        AppointmentNotificationLog::create($payload);
    }
}
