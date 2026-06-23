<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SsoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SsoCallbackController extends Controller
{
    public function __construct(private SsoService $sso) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate(['sso_token' => 'required|string']);

        $ssoUser = $this->sso->validateOneTimeToken($request->string('sso_token'));

        $user = User::where('sso_id', $ssoUser['id'])->first()
            ?? User::where('email', $ssoUser['email'])->first();

        $isNewUser = $user === null;
        $user ??= new User();

        $user->fill([
            'name' => $ssoUser['name'],
            'email' => $ssoUser['email'],
            'avatar' => $ssoUser['avatar'] ?? null,
            'phone' => $ssoUser['phone'] ?? null,
            'is_active' => $ssoUser['is_active'] ?? true,
        ]);
        $user->sso_id ??= $ssoUser['id'];

        if ($isNewUser) {
            $user->role = ($ssoUser['is_admin'] ?? false) ? 'sso_admin' : 'viewer';
        } elseif ($ssoUser['is_admin'] ?? false) {
            $user->role = 'sso_admin';
        }

        $user->save();

        if (! $user->is_active) {
            abort(403, 'Akun Anda tidak aktif.');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('dashboard');
    }
}
