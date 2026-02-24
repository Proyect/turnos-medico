<?php

namespace Tests\Feature;

use App\Mail\UserDirectMessageMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_notification_screen(): void
    {
        $regularUser = User::create([
            'name' => 'Regular',
            'email' => 'regular@example.com',
            'role' => User::ROLE_DOCTOR,
            'doctor_id' => null,
            'active' => true,
            'password' => 'super-segura',
        ]);
        $target = $this->createAdmin('target@example.com');

        $response = $this->actingAs($regularUser)->get(route('admin.users.notify.create', $target));

        $response->assertRedirect('/login/admin');
    }

    public function test_admin_can_send_email_notification_to_user(): void
    {
        Mail::fake();

        $admin = $this->createAdmin('admin@example.com');
        $target = $this->createAdmin('target@example.com');

        $response = $this->actingAs($admin)->post(route('admin.users.notify.store', $target), [
            'channels' => ['email'],
            'subject' => 'Recordatorio',
            'message' => 'Tu turno fue reprogramado.',
        ]);

        $response->assertRedirect(route('admin.users.notify.create', $target));
        Mail::assertSent(UserDirectMessageMail::class);
        $this->assertDatabaseHas('user_notification_logs', [
            'user_id' => $target->id,
            'channel' => 'email',
            'status' => 'sent',
            'sent_by' => $admin->id,
        ]);
    }

    public function test_admin_can_send_whatsapp_notification_with_authenticated_provider(): void
    {
        Http::fake([
            'https://api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        config()->set('services.twilio_whatsapp.enabled', true);
        config()->set('services.twilio_whatsapp.account_sid', 'AC123');
        config()->set('services.twilio_whatsapp.auth_token', 'token123');
        config()->set('services.twilio_whatsapp.from', 'whatsapp:+14155238886');
        config()->set('services.twilio_whatsapp.base_url', 'https://api.twilio.com/2010-04-01');

        $admin = $this->createAdmin('admin@example.com');
        $target = $this->createAdmin('target@example.com', '+5491122334455');

        $response = $this->actingAs($admin)->post(route('admin.users.notify.store', $target), [
            'channels' => ['whatsapp'],
            'message' => 'Recordatorio por WhatsApp.',
        ]);

        $response->assertRedirect(route('admin.users.notify.create', $target));
        $this->assertDatabaseHas('user_notification_logs', [
            'user_id' => $target->id,
            'channel' => 'whatsapp',
            'status' => 'sent',
            'provider_message_id' => 'SM123',
            'sent_by' => $admin->id,
        ]);
    }

    public function test_notification_requires_user_contact_for_selected_channel(): void
    {
        $admin = $this->createAdmin('admin@example.com');
        $target = $this->createAdmin('target@example.com');

        $response = $this->actingAs($admin)->post(route('admin.users.notify.store', $target), [
            'channels' => ['whatsapp'],
            'message' => 'Mensaje sin teléfono de destino.',
        ]);

        $response->assertSessionHasErrors('channels');
    }

    private function createAdmin(string $email, ?string $phone = null): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => $email,
            'phone' => $phone,
            'role' => User::ROLE_ADMIN,
            'doctor_id' => null,
            'active' => true,
            'password' => 'admin-secret',
        ]);
    }
}
