<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SsoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(private SsoService $sso) {}

    public function redirect(): RedirectResponse
    {
        if (app()->environment('local') && config('sso.dev_login_enabled')) {
            return redirect()->route('dev-login.index');
        }

        return redirect()->away($this->sso->getPortalUrl());
    }

    public function logout(Request $request): RedirectResponse
    {
        $devLogin = app()->environment('local') && config('sso.dev_login_enabled');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $devLogin ? redirect()->route('login') : redirect()->away($this->sso->getLogoutUrl());
    }
}
