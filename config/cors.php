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

$configured = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('FRONTEND_URL', ''))
)));

/*
 * Fallbacks for when FRONTEND_URL isn't set. These are this project's known,
 * fixed frontends — the local dev server and the published GitHub Pages site —
 * and an allowed origin is public information anyway (any caller can read it
 * from a preflight). Defaulting to them means a deployment that forgets the
 * variable degrades to "works" rather than "every request silently blocked",
 * which is otherwise an invisible and very confusing failure.
 *
 * Setting FRONTEND_URL still overrides this list entirely.
 */
$defaults = [
    'http://localhost:5173',
    'http://127.0.0.1:5173',
    'https://mohamedshaaban.github.io',
];

$origins = $configured !== [] ? $configured : $defaults;

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FRONTEND_URL_PATTERNS', ''))
    ))),

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Language'],

    'max_age' => 3600,

    'supports_credentials' => false,

];
