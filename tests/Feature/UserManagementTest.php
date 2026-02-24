<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
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

    public function test_admin_can_search_users_by_name_or_email(): void
    {
        $admin = $this->createAdminUser('owner@example.com');
        User::create([
            'name' => 'Lucia Recepcion',
            'email' => 'lucia@example.com',
            'role' => User::ROLE_ADMIN,
            'doctor_id' => null,
            'active' => true,
            'password' => 'super-segura',
        ]);
        User::create([
            'name' => 'Carlos Medico',
            'email' => 'carlos@example.com',
            'role' => User::ROLE_ADMIN,
            'doctor_id' => null,
            'active' => true,
            'password' => 'super-segura',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index', ['q' => 'lucia']));

        $response->assertStatus(200);
        $response->assertSee('Lucia Recepcion');
        $response->assertDontSee('Carlos Medico');
    }

    public function test_admin_can_reset_user_password(): void
    {
        $admin = $this->createAdminUser('owner@example.com');
        $user = User::create([
            'name' => 'Usuario Reset',
            'email' => 'reset@example.com',
            'role' => User::ROLE_ADMIN,
            'doctor_id' => null,
            'active' => true,
            'password' => 'clave-anterior',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.users.password.update', $user), [
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ]);

        $response->assertRedirect(route('admin.users.edit', $user));
        $this->assertTrue(Hash::check('nueva-clave-segura', (string) $user->fresh()->password));
        $this->assertSame($admin->id, $user->fresh()->updated_by);
    }

    public function test_toggle_active_registers_deactivation_audit(): void
    {
        $admin = $this->createAdminUser('owner@example.com');
        $target = User::create([
            'name' => 'Target',
            'email' => 'target@example.com',
            'role' => User::ROLE_ADMIN,
            'doctor_id' => null,
            'active' => true,
            'password' => 'super-segura',
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.toggle-active', $target));

        $response->assertSessionHasNoErrors();
        $target->refresh();
        $this->assertFalse($target->active);
        $this->assertSame($admin->id, $target->updated_by);
        $this->assertSame($admin->id, $target->deactivated_by);
        $this->assertNotNull($target->deactivated_at);
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
