<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_another_admin_user(): void
    {
        $admin = $this->createAdminUser('owner@example.com');

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Coordinador',
            'email' => 'coord@example.com',
            'role' => User::ROLE_ADMIN,
            'active' => '1',
            'password' => 'super-segura',
            'password_confirmation' => 'super-segura',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'coord@example.com',
            'role' => User::ROLE_ADMIN,
            'active' => true,
        ]);
    }

    public function test_admin_can_create_doctor_user_linked_to_doctor(): void
    {
        $admin = $this->createAdminUser('owner@example.com');
        $specialty = Specialty::create(['name' => 'Clinica']);
        $doctor = Doctor::create([
            'name' => 'Dr. Vinculado',
            'specialty_id' => $specialty->id,
            'active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => $doctor->name,
            'email' => 'doctor.user@example.com',
            'role' => User::ROLE_DOCTOR,
            'doctor_id' => $doctor->id,
            'active' => '1',
            'password' => 'super-segura',
            'password_confirmation' => 'super-segura',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'doctor.user@example.com',
            'role' => User::ROLE_DOCTOR,
            'doctor_id' => $doctor->id,
            'active' => true,
        ]);
    }

    public function test_cannot_assign_same_doctor_to_two_users(): void
    {
        $admin = $this->createAdminUser('owner@example.com');
        $specialty = Specialty::create(['name' => 'Traumatologia']);
        $doctor = Doctor::create([
            'name' => 'Dr. Unico',
            'specialty_id' => $specialty->id,
            'active' => true,
        ]);

        User::create([
            'name' => 'Usuario Medico',
            'email' => 'medico.existing@example.com',
            'role' => User::ROLE_DOCTOR,
            'doctor_id' => $doctor->id,
            'active' => true,
            'password' => 'super-segura',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Otro Usuario Medico',
            'email' => 'medico.new@example.com',
            'role' => User::ROLE_DOCTOR,
            'doctor_id' => $doctor->id,
            'active' => '1',
            'password' => 'super-segura',
            'password_confirmation' => 'super-segura',
        ]);

        $response->assertSessionHasErrors('doctor_id');
    }

    public function test_admin_cannot_deactivate_own_user(): void
    {
        $admin = $this->createAdminUser('owner@example.com');

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $admin));

        $response->assertSessionHasErrors('auth');
        $this->assertTrue((bool) $admin->fresh()->active);
    }

    private function createAdminUser(string $email): User
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
