<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->with('error', 'Akun Anda tidak aktif.');
        }

        if ($user && $user->isSsoAdmin() && ! $request->routeIs('users.*') && ! $request->routeIs('logout')) {
            return redirect()->route('users.index');
        }

        return $next($request);
    }
}
