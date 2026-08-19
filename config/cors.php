<?php

/*
|--------------------------------------------------------------------------
| Cross-origin access
|--------------------------------------------------------------------------
|
| The frontend is deployed separately from the API, so its origin has to be
| allowed explicitly. FRONTEND_URL accepts a comma-separated list, which covers
| the common case of a production domain plus a preview deployment.
|
| Credentials stay off: authentication is a bearer token, not a cookie, so the
| browser never needs to send credentials cross-origin — and leaving it off
| means a misconfigured origin can't be used to ride a session.
|
*/

$origins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('FRONTEND_URL', 'http://localhost:5173'))
)));

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $origins !== [] ? $origins : ['http://localhost:5173'],

    'allowed_origins_patterns' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FRONTEND_URL_PATTERNS', ''))
    ))),

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Language'],

    'max_age' => 3600,

    'supports_credentials' => false,

];
