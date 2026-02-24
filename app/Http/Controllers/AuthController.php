<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request, string $role): View|RedirectResponse
    {
        abort_unless(in_array($role, ['admin', 'medico']), 404);

        if (Auth::check()) {
            $user = Auth::user();

            if ($user?->isAdmin() && $user->active) {
                return redirect()->route('reception.index');
            }

            if ($user?->isDoctor() && $user->active && $user->doctor?->active) {
                return redirect()->route('doctor.index');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $doctors = collect();
        if ($role === 'medico') {
            $doctors = Doctor::query()
                ->where('active', true)
                ->whereHas('user', function ($query): void {
                    $query->where('role', User::ROLE_DOCTOR)->where('active', true);
                })
                ->orderBy('name')
                ->get(['id', 'name']);
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
            $credentials = $request->validate([
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string'],
            ]);

            $user = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->where('active', true)
                ->whereRaw('LOWER(email) = ?', [strtolower($credentials['email'])])
                ->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                RateLimiter::hit($throttleKey, $decaySeconds);

                return back()
                    ->withErrors(['auth' => 'Credenciales inválidas.'])
                    ->withInput($request->except('password'));
            }

            Auth::login($user);
            $request->session()->regenerate();
            RateLimiter::clear($throttleKey);

            return redirect()->route('reception.index')->with('success', 'Sesión iniciada como Administrador.');
        }

        $credentials = $request->validate([
            'password' => ['required','string'],
            'doctor_id' => [
                'required',
                Rule::exists('doctors', 'id')->where(fn ($query) => $query->where('active', true)),
            ],
        ]);

        $user = User::query()
            ->where('role', User::ROLE_DOCTOR)
            ->where('doctor_id', (int) $credentials['doctor_id'])
            ->with('doctor:id,name,active')
            ->first();

        if (
            !$user
            || !$user->active
            || !$user->doctor
            || !$user->doctor->active
            || !Hash::check($credentials['password'], $user->password)
        ) {
            RateLimiter::hit($throttleKey, $decaySeconds);

            return back()
                ->withErrors(['auth' => 'Credenciales inválidas.'])
                ->withInput($request->except('password'));
        }

        Auth::login($user);
        $request->session()->regenerate();
        RateLimiter::clear($throttleKey);

        return redirect()->route('doctor.index')->with('success', 'Sesión iniciada como Médico.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/paciente')->with('success', 'Sesión cerrada.');
    }

    private function throttleKey(Request $request, string $role): string
    {
        $identifier = $role === 'admin'
            ? strtolower((string) $request->input('email', ''))
            : (string) $request->input('doctor_id', '');

        return sprintf('role-login:%s:%s:%s', $role, $identifier, $request->ip());
    }
}
