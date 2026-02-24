<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(string $role): View
    {
        abort_unless(in_array($role, ['admin', 'medico']), 404);
        $doctors = collect();
        if ($role === 'medico') {
            $doctors = Doctor::where('active', true)->orderBy('name')->get(['id','name']);
        }
        return view('auth.login', compact('role', 'doctors'));
    }

    public function login(Request $request, string $role): RedirectResponse
    {
        abort_unless(in_array($role, ['admin', 'medico']), 404);
        $throttleKey = $this->throttleKey($request, $role);
        $maxAttempts = (int) config('auth.rate_limits.role_login_attempts', 5);
        $decaySeconds = (int) config('auth.rate_limits.role_login_decay_seconds', 60);

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()
                ->withErrors(['auth' => "Demasiados intentos fallidos. Reintenta en {$seconds} segundos."])
                ->withInput($request->except('password'));
        }

        if ($role === 'admin') {
            $request->validate(['password' => ['required','string']]);
            $pass = $request->input('password');
            $expected = (string) config('auth.role_passwords.admin', '');

            if ($expected === '') {
                return back()->withErrors([
                    'auth' => 'El acceso de Administrador no está configurado. Define ADMIN_PASS en .env.',
                ]);
            }

            if (!hash_equals($expected, (string) $pass)) {
                RateLimiter::hit($throttleKey, $decaySeconds);

                return back()
                    ->withErrors(['password' => 'Clave incorrecta'])
                    ->withInput($request->except('password'));
            }

            $this->startRoleSession($request, ['role' => 'admin']);
            RateLimiter::clear($throttleKey);

            return redirect()->route('reception.index')->with('success', 'Sesión iniciada como Administrador.');
        }

        // Médico
        $request->validate([
            'password' => ['required','string'],
            'doctor_id' => [
                'required',
                Rule::exists('doctors', 'id')->where(fn ($query) => $query->where('active', true)),
            ],
        ]);

        $pass = $request->input('password');
        $expected = (string) config('auth.role_passwords.doctor', '');

        if ($expected === '') {
            return back()->withErrors([
                'auth' => 'El acceso de Médico no está configurado. Define DOCTOR_PASS en .env.',
            ]);
        }

        if (!hash_equals($expected, (string) $pass)) {
            RateLimiter::hit($throttleKey, $decaySeconds);

            return back()
                ->withErrors(['password' => 'Clave incorrecta'])
                ->withInput($request->except('password'));
        }

        $doctor = Doctor::query()
            ->whereKey((int) $request->doctor_id)
            ->where('active', true)
            ->first();

        if (!$doctor) {
            return back()
                ->withErrors(['doctor_id' => 'El médico seleccionado ya no está disponible.'])
                ->withInput($request->except('password'));
        }

        $this->startRoleSession($request, [
            'role' => 'doctor',
            'doctor_id' => $doctor->id,
            'doctor_name' => $doctor->name,
        ]);
        RateLimiter::clear($throttleKey);

        return redirect()->route('doctor.index')->with('success', 'Sesión iniciada como Médico.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/paciente')->with('success', 'Sesión cerrada.');
    }

    private function startRoleSession(Request $request, array $sessionData): void
    {
        $request->session()->regenerate();
        $request->session()->forget(['role', 'doctor_id', 'doctor_name']);
        $request->session()->put($sessionData);
    }

    private function throttleKey(Request $request, string $role): string
    {
        return sprintf('role-login:%s:%s', $role, $request->ip());
    }
}
