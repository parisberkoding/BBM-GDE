<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Middleware ini bisa handle pemeriksaan role dengan berbagai cara
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            abort(403, 'Anda harus login terlebih dahulu.');
        }

        $user = Auth::user();

        // Jika tidak memiliki role yang dibutuhkan
        if (!$this->hasRole($user, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }

    /**
     * Cek apakah user memiliki role yang diizinkan
     */
    protected function hasRole($user, array $roles): bool
    {
        return in_array($user->role, $roles);
    }
}
