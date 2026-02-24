<?php

namespace App\Http\Controllers;

use App\Mail\UserDirectMessageMail;
use App\Models\User;
use App\Models\UserNotificationLog;
use App\Services\WhatsApp\TwilioWhatsAppClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminUserNotificationController extends Controller
{
    public function create(User $user): View
    {
        $logs = $user->notificationLogs()
            ->with('sender:id,name')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.users.notify', compact('user', 'logs'));
    }

    public function store(Request $request, User $user, TwilioWhatsAppClient $whatsAppClient): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['required', 'in:email,whatsapp'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($request, $user): void {
            $channels = (array) $request->input('channels', []);

            if (in_array('email', $channels, true)) {
                if (blank($user->email)) {
                    $validator->errors()->add('channels', 'El usuario no tiene email para notificación.');
                }

                if (blank(trim((string) $request->input('subject', '')))) {
                    $validator->errors()->add('subject', 'El asunto es obligatorio para notificaciones por email.');
                }
            }

            if (in_array('whatsapp', $channels, true) && blank($user->phone)) {
                $validator->errors()->add('channels', 'El usuario no tiene teléfono para notificación por WhatsApp.');
            }
        });

        $data = $validator->validate();

        $channels = array_values(array_unique((array) $data['channels']));
        $subject = trim((string) ($data['subject'] ?? 'Notificación del sistema'));
        $message = trim((string) $data['message']);
        $sender = $request->user();

        $results = [];

        if (in_array('email', $channels, true)) {
            $results['email'] = $this->sendEmail($user, $subject, $message, $sender->name, $sender->id);
        }

        if (in_array('whatsapp', $channels, true)) {
            $results['whatsapp'] = $this->sendWhatsApp($user, $message, $sender->id, $whatsAppClient);
        }

        $successful = collect($results)->where('ok', true)->count();
        $failed = collect($results)->where('ok', false)->count();

        if ($failed === 0) {
            return redirect()
                ->route('admin.users.notify.create', $user)
                ->with('success', "Notificación enviada correctamente por {$successful} canal(es).");
        }

        $errors = collect($results)
            ->filter(fn ($item) => !$item['ok'])
            ->map(fn ($item, $channel) => strtoupper($channel).': '.$item['error'])
            ->implode(' | ');

        return redirect()
            ->route('admin.users.notify.create', $user)
            ->withErrors(['notify' => "Se enviaron {$successful} canal(es) y fallaron {$failed}. {$errors}"])
            ->withInput();
    }

    /**
     * @return array{ok: bool, error: ?string}
     */
    private function sendEmail(User $user, string $subject, string $message, string $senderName, int $senderId): array
    {
        try {
            Mail::to((string) $user->email)->send(
                new UserDirectMessageMail($user, $subject, $message, $senderName)
            );

            UserNotificationLog::create([
                'user_id' => $user->id,
                'sent_by' => $senderId,
                'channel' => 'email',
                'recipient' => $user->email,
                'subject' => $subject,
                'message' => $message,
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            report($e);

            UserNotificationLog::create([
                'user_id' => $user->id,
                'sent_by' => $senderId,
                'channel' => 'email',
                'recipient' => $user->email,
                'subject' => $subject,
                'message' => $message,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => 'No se pudo enviar email'];
        }
    }

    /**
     * @return array{ok: bool, error: ?string}
     */
    private function sendWhatsApp(
        User $user,
        string $message,
        int $senderId,
        TwilioWhatsAppClient $whatsAppClient
    ): array {
        $result = $whatsAppClient->send((string) $user->phone, $message);

        UserNotificationLog::create([
            'user_id' => $user->id,
            'sent_by' => $senderId,
            'channel' => 'whatsapp',
            'recipient' => $user->phone,
            'subject' => null,
            'message' => $message,
            'status' => $result['ok'] ? 'sent' : 'failed',
            'provider_message_id' => $result['message_id'],
            'provider_response' => $result['response'],
            'error_message' => $result['error'],
            'sent_at' => $result['ok'] ? now() : null,
        ]);

        return [
            'ok' => $result['ok'],
            'error' => $result['error'],
        ];
    }
}
