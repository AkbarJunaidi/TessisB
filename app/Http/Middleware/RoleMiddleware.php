<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Membatasi akses berdasarkan role pengguna.
     *
     * @param array<string> $roles
     */
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {

        // Pengguna harus sudah login
        if (Auth::guest()) {
            abort(401);
        }

        // Memastikan role dasar pengguna (role kustom ikut role dasarnya) termasuk dalam daftar yang diizinkan
        if (!in_array(Auth::user()?->baseRole(), $roles, true)) {
            abort(403, 'Anda tidak memiliki hak akses.');
        }

        return $next($request);
    }
}

