<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the response language for every API request.
 *
 * Precedence: an explicit ?lang= override, then the Accept-Language header the
 * frontend sends, then the signed-in reader's saved preference, then the app
 * default. Anything not in config('himam.locales') is ignored rather than
 * trusted, so a malformed header can't knock the API into an unknown locale.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = array_keys(config('himam.locales'));

        $candidates = [
            $request->query('lang'),
            $request->header('X-Locale'),
            $this->fromAcceptLanguage($request, $supported),
            $request->user()?->locale,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && in_array($candidate, $supported, true)) {
                app()->setLocale($candidate);
                break;
            }
        }

        $response = $next($request);
        $response->headers->set('Content-Language', app()->getLocale());

        return $response;
    }

    /**
     * Picks the highest-weighted Accept-Language entry we actually support.
     */
    private function fromAcceptLanguage(Request $request, array $supported): ?string
    {
        foreach ($request->getLanguages() as $language) {
            $base = strtolower(substr($language, 0, 2));

            if (in_array($base, $supported, true)) {
                return $base;
            }
        }

        return null;
    }
}
