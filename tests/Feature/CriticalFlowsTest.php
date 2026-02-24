<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CriticalFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_create_appointment_with_doctor_from_other_specialty(): void
    {
        $cardiology = Specialty::create(['name' => 'Cardiologia']);
        $pediatrics = Specialty::create(['name' => 'Pediatria']);
        $doctor = Doctor::create([
            'name' => 'Dra. Valeria Test',
            'specialty_id' => $cardiology->id,
            'active' => true,
        ]);

        $response = $this->post(route('appointments.store'), [
            'patient_first_name' => 'Ana',
            'patient_last_name' => 'Lopez',
            'phone' => '11111111',
            'dni' => '12345678',
            'specialty_id' => $pediatrics->id,
            'doctor_id' => $doctor->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'time' => '09:00',
        ]);

        $response->assertSessionHasErrors('doctor_id');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_cannot_create_appointment_in_the_past(): void
    {
        $specialty = Specialty::create(['name' => 'Clinica']);
        $doctor = Doctor::create([
            'name' => 'Dr. Martin Test',
            'specialty_id' => $specialty->id,
            'active' => true,
        ]);

        $response = $this->post(route('appointments.store'), [
            'patient_first_name' => 'Juan',
            'patient_last_name' => 'Perez',
            'phone' => '22222222',
            'dni' => '87654321',
            'specialty_id' => $specialty->id,
            'doctor_id' => $doctor->id,
            'date' => now()->subDay()->format('Y-m-d'),
            'time' => '10:00',
        ]);

        $response->assertSessionHasErrors('date');
        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_cannot_create_duplicate_slot_for_same_doctor_and_datetime(): void
    {
        $specialty = Specialty::create(['name' => 'Dermatologia']);
        $doctor = Doctor::create([
            'name' => 'Dra. Laura Test',
            'specialty_id' => $specialty->id,
            'active' => true,
        ]);
        $slot = now()->addDays(2)->setTime(11, 0, 0);

        Appointment::create([
            'patient_first_name' => 'Paciente',
            'patient_last_name' => 'Existente',
            'phone' => '33333333',
            'dni' => '11111111',
            'specialty_id' => $specialty->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => $slot,
            'status' => 'requested',
        ]);

        $response = $this->post(route('appointments.store'), [
            'patient_first_name' => 'Nuevo',
            'patient_last_name' => 'Paciente',
            'phone' => '44444444',
            'dni' => '22222222',
            'specialty_id' => $specialty->id,
            'doctor_id' => $doctor->id,
            'date' => $slot->format('Y-m-d'),
            'time' => $slot->format('H:i'),
        ]);

        $response->assertSessionHasErrors('time');
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_can_create_valid_appointment_request(): void
    {
        $specialty = Specialty::create(['name' => 'Odontologia']);
        $doctor = Doctor::create([
            'name' => 'Dra. Elena Test',
            'specialty_id' => $specialty->id,
            'active' => true,
        ]);

        $response = $this->post(route('appointments.store'), [
            'patient_first_name' => 'Mario',
            'patient_last_name' => 'Rossi',
            'phone' => '44442222',
            'dni' => '33333333',
            'specialty_id' => $specialty->id,
            'doctor_id' => $doctor->id,
            'date' => now()->addDays(3)->format('Y-m-d'),
            'time' => '15:30',
        ]);

        $response->assertRedirect(route('appointments.create'));
        $this->assertDatabaseHas('appointments', [
            'patient_first_name' => 'Mario',
            'doctor_id' => $doctor->id,
            'specialty_id' => $specialty->id,
            'status' => 'requested',
        ]);
    }

    public function test_reception_cannot_mark_arrived_if_status_is_not_requested(): void
    {
        $appointment = $this->createAppointmentWithStatus('paid');

        $response = $this->withSession(['role' => 'admin'])
            ->post(route('reception.arrived', $appointment));

        $response->assertSessionHasErrors('status');
        $this->assertSame('paid', $appointment->fresh()->status);
    }

    public function test_reception_cannot_mark_paid_when_status_is_completed(): void
    {
        $appointment = $this->createAppointmentWithStatus('completed');

        $response = $this->withSession(['role' => 'admin'])
            ->post(route('reception.paid', $appointment));

        $response->assertSessionHasErrors('status');
        $this->assertSame('completed', $appointment->fresh()->status);
    }

    public function test_login_medico_requires_active_doctor(): void
    {
        RateLimiter::clear('role-login:medico:127.0.0.1');

        $specialty = Specialty::create(['name' => 'Neurologia']);
        $inactiveDoctor = Doctor::create([
            'name' => 'Dr. Inactivo',
            'specialty_id' => $specialty->id,
            'active' => false,
        ]);

        config()->set('auth.role_passwords.doctor', 'secret');

        $response = $this->post(route('login.perform', 'medico'), [
            'doctor_id' => $inactiveDoctor->id,
            'password' => 'secret',
        ]);

        $response->assertSessionHasErrors('doctor_id');
    }

    public function test_login_admin_fails_when_password_is_not_configured(): void
    {
        RateLimiter::clear('role-login:admin:127.0.0.1');

        config()->set('auth.role_passwords.admin', '');

        $response = $this->post(route('login.perform', 'admin'), [
            'password' => 'secret',
        ]);

        $response->assertSessionHasErrors('auth');
    }

    private function createAppointmentWithStatus(string $status): Appointment
    {
        $specialty = Specialty::create(['name' => 'Traumatologia']);
        $doctor = Doctor::create([
            'name' => 'Dr. Test Estado',
            'specialty_id' => $specialty->id,
            'active' => true,
        ]);

        return Appointment::create([
            'patient_first_name' => 'Estado',
            'patient_last_name' => 'Prueba',
            'phone' => '55555555',
            'dni' => '99999999',
            'specialty_id' => $specialty->id,
            'doctor_id' => $doctor->id,
            'scheduled_at' => now()->addDay()->setTime(12, 0, 0),
            'status' => $status,
        ]);
    }
}
