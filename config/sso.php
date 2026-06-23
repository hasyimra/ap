<?php

return [
    'url' => env('SSO_URL', 'https://dkmapps.com'),
    'app_code' => env('SSO_APP_CODE', 'AP'),
    'client_id' => env('SSO_CLIENT_ID'),
    'client_secret' => env('SSO_CLIENT_SECRET'),
    'redirect_uri' => env('SSO_REDIRECT_URI'),
    'verify_ssl' => env('SSO_VERIFY_SSL', true),
    'dev_login_enabled' => env('AP_DEV_LOGIN_ENABLED', false),
];
