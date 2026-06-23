<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DevLoginController extends Controller
{
    public function index(): View
    {
        if (! app()->environment('local') || ! config('sso.dev_login_enabled')) {
            abort(404);
        }

        $users = User::orderBy('role')->orderBy('name')->get();

        return view('auth.dev-login', compact('users'));
    }

    public function login(User $user): RedirectResponse
    {
        if (! app()->environment('local') || ! config('sso.dev_login_enabled')) {
            abort(404);
        }

        Auth::login($user);
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('dashboard');
    }
}
