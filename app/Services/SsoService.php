<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SsoService
{
    public function getLoginUrl(): string
    {
        return rtrim(config('sso.url'), '/').'/sso/redirect?'.http_build_query([
            'app' => config('sso.app_code'),
            'return_to' => config('sso.redirect_uri'),
        ]);
    }

    /**
     * Portal-first entry point: user selalu lihat & klik icon di Portal SSO secara sadar,
     * bukan auto-bounce langsung ke app (pola yang sama dipakai di seluruh suite ini).
     */
    public function getPortalUrl(): string
    {
        return rtrim(config('sso.url'), '/').'/portal';
    }

    public function getLogoutUrl(): string
    {
        return rtrim(config('sso.url'), '/').'/logout';
    }

    public function validateOneTimeToken(string $token): array
    {
        $response = Http::withOptions(['verify' => config('sso.verify_ssl')])
            ->post(rtrim(config('sso.url'), '/').'/api/sso/ot-token', [
                'one_time_token' => $token,
                'client_id' => config('sso.client_id'),
                'client_secret' => config('sso.client_secret'),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal memvalidasi token SSO: '.$response->status());
        }

        return $response->json('user');
    }
}
