<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || !Auth::user()?->isAdmin()) {
            return redirect()->to('/login/admin')->withErrors(['auth' => 'Debes iniciar sesión como Administrador.']);
        }

        return $next($request);
    }
}
