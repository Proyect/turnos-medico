<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDoctor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user || !$user->isDoctor()) {
            return redirect()->to('/login/medico')->withErrors(['auth' => 'Debes iniciar sesión como Médico.']);
        }

        if (!$user->doctor || !$user->doctor->active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to('/login/medico')->withErrors([
                'auth' => 'Tu usuario médico no se encuentra habilitado.',
            ]);
        }

        return $next($request);
    }
}
