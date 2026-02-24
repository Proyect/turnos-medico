<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = strtolower((string) env('ADMIN_EMAIL', 'admin@centromedico.local'));
        $adminPassword = (string) env('ADMIN_PASSWORD', 'admin12345');
        $doctorPassword = (string) env('DOCTOR_DEFAULT_PASSWORD', 'doctor12345');

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'Administrador',
                'role' => User::ROLE_ADMIN,
                'doctor_id' => null,
                'password' => Hash::make($adminPassword),
            ]
        );

        Doctor::query()
            ->where('active', true)
            ->orderBy('id')
            ->get()
            ->each(function (Doctor $doctor) use ($doctorPassword): void {
                $email = $this->doctorEmail($doctor);
                $existing = User::query()->where('doctor_id', $doctor->id)->first();

                if ($existing) {
                    $existing->forceFill([
                        'name' => $doctor->name,
                        'email' => $email,
                        'role' => User::ROLE_DOCTOR,
                    ])->save();

                    return;
                }

                User::create([
                    'name' => $doctor->name,
                    'email' => $email,
                    'role' => User::ROLE_DOCTOR,
                    'doctor_id' => $doctor->id,
                    'password' => Hash::make($doctorPassword),
                ]);
            });
    }

    private function doctorEmail(Doctor $doctor): string
    {
        $base = Str::slug($doctor->name, '.');
        if ($base === '') {
            $base = 'doctor';
        }

        return "{$base}.{$doctor->id}@medicos.local";
    }
}
