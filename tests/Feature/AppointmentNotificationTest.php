<?php

namespace Tests\Feature;

use App\Mail\AppointmentNotificationMail;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use App\Services\Notifications\AppointmentNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointment_request_triggers_email_and_whatsapp_notifications_when_configured(): void
    {
        Mail::fake();
        Http::fake([
            'https://api.twilio.com/*' => Http::response(['sid' => 'SM-REQUEST-001'], 201),
        ]);

        $this->enableAppointmentNotifications();

        $specialty = Specialty::create(['name' => 'Clinica']);
        $doctor = Doctor::create([
            'name' => 'Dr. Notificacion',
            'specialty_id' => $specialty->id,
            'active' => true,
        ]);

        $response = $this->post(route('appointments.store'), [
            'patient_first_name' => 'Ana',
            'patient_last_name' => 'Notifica',
            'phone' => '+5491122334455',
            'patient_email' => 'paciente@example.com',
            'dni' => '12345678',
            'specialty_id' => $specialty->id,
            'doctor_id' => $doctor->id,
            'date' => now()->addDays(2)->format('Y-m-d'),
            'time' => '10:00',
        ]);

        $response->assertRedirect(route('appointments.create'));
        Mail::assertSent(AppointmentNotificationMail::class);
        $this->assertDatabaseHas('appointment_notification_logs', [
            'event' => AppointmentNotificationService::EVENT_REQUESTED,
            'channel' => 'email',
            'recipient' => 'paciente@example.com',
            'status' => 'sent',
        ]);
        $this->assertDatabaseHas('appointment_notification_logs', [
            'event' => AppointmentNotificationService::EVENT_REQUESTED,
            'channel' => 'whatsapp',
            'recipient' => '+5491122334455',
            'status' => 'sent',
            'provider_message_id' => 'SM-REQUEST-001',
        ]);
    }

    public function test_mark_arrived_triggers_notifications_and_tracks_actor(): void
    {
        Mail::fake();
        Http::fake([
            'https://api.twilio.com/*' => Http::response(['sid' => 'SM-ARRIVED-001'], 201),
        ]);

        $this->enableAppointmentNotifications();

        $appointment = $this->createAppointmentWithContact(Appointment::STATUS_REQUESTED);
        $admin = $this->createAdmin('admin-arrived@example.com');

        $response = $this->actingAs($admin)->post(route('reception.arrived', $appointment));

        $response->assertSessionHas('success');
        $this->assertSame(Appointment::STATUS_ARRIVED, $appointment->fresh()->status);
        $this->assertDatabaseHas('appointment_notification_logs', [
            'appointment_id' => $appointment->id,
            'event' => AppointmentNotificationService::EVENT_ARRIVED,
            'channel' => 'email',
            'status' => 'sent',
            'sent_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('appointment_notification_logs', [
            'appointment_id' => $appointment->id,
            'event' => AppointmentNotificationService::EVENT_ARRIVED,
            'channel' => 'whatsapp',
            'status' => 'sent',
            'provider_message_id' => 'SM-ARRIVED-001',
            'sent_by' => $admin->id,
        ]);
    }

    public function test_mark_paid_keeps_flow_when_whatsapp_number_is_invalid(): void
    {
        Mail::fake();
        Http::fake();

        $this->enableAppointmentNotifications();

        $appointment = $this->createAppointmentWithContact(Appointment::STATUS_REQUESTED, 'abc', 'patient@test.com');
        $admin = $this->createAdmin('admin-paid@example.com');

        $response = $this->actingAs($admin)->post(route('reception.paid', $appointment));

        $response->assertSessionHas('success');
        $this->assertSame(Appointment::STATUS_PAID, $appointment->fresh()->status);
        $this->assertDatabaseHas('appointment_notification_logs', [
            'appointment_id' => $appointment->id,
            'event' => AppointmentNotificationService::EVENT_PAID,
            'channel' => 'whatsapp',
            'status' => 'failed',
        ]);
    }

    private function enableAppointmentNotifications(): void
    {
        config()->set('notifications.appointment.enabled', true);
        config()->set('notifications.appointment.email_enabled', true);
        config()->set('notifications.appointment.whatsapp_enabled', true);
        config()->set('services.twilio_whatsapp.enabled', true);
        config()->set('services.twilio_whatsapp.account_sid', 'AC123');
        config()->set('services.twilio_whatsapp.auth_token', 'token123');
        config()->set('services.twilio_whatsapp.from', 'whatsapp:+14155238886');
        config()->set('services.twilio_whatsapp.base_url', 'https://api.twilio.com/2010-04-01');
    }

    private function createAppointmentWithContact(
        string $status,
        string $phone = '+5491122334455',
        string $email = 'patient@example.com'
    ): Appointment {
        $specialty = Specialty::create(['name' => 'Traumatologia']);
        $doctor = Doctor::create([
            'name' => 'Dr. Estado Notificacion',
            'specialty_id' => $specialty->id,
            'active' => true,
        ]);

        return Appointment::create([
            'patient_first_name' => 'Paciente',
            'patient_last_name' => 'Prueba',
            'phone' => $phone,
            'patient_email' => $email,
            'dni' => '99999999',
            'specialty_id' => $specialty->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addDay()->setTime(11, 0, 0),
            'status' => $status,
        ]);
    }

    private function createAdmin(string $email): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => $email,
            'role' => User::ROLE_ADMIN,
            'doctor_id' => null,
            'active' => true,
            'password' => 'admin-secret',
        ]);
    }
}
