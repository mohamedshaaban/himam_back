<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web routes
|--------------------------------------------------------------------------
|
| This service is an API; the reader app is a separate static deployment. The
| Laravel starter's welcome view is deliberately not used — it renders through
| @vite, which needs a frontend build manifest that this image has no reason to
| contain, so it would only ever return a 500 here.
|
| A small JSON index makes the root useful for a health poke instead.
|
*/

Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'status' => 'ok',
    'api' => url('/api'),
    'health' => url('/up'),
    'locales' => array_keys(config('himam.locales')),
]));
