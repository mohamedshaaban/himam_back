<?php

namespace App\Http\Middleware;

use App\Services\LocaleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the response language for every API request.
 *
 * Precedence: an explicit ?lang= override, then the X-Locale header, then the
 * Accept-Language header the browser sends, then the signed-in reader's saved
 * preference, and finally the language marked default in the dashboard.
 * Anything the platform doesn't currently support is ignored rather
 * than trusted, so a malformed header — or a language an administrator has
 * since disabled — can't knock the API into an unknown locale.
 *
 * The supported set comes from the database via LocaleRegistry, so adding a
 * language takes effect here without a deploy.
 */
class SetLocale
{
    public function __construct(private readonly LocaleRegistry $locales)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $candidates = [
            $request->query('lang'),
            $request->header('X-Locale'),
            $this->fromAcceptLanguage($request),
            $request->user()?->locale,
        ];

        // The registry's default comes last rather than app.locale: the language
        // marked default in the dashboard is the one the programme means, and
        // leaving it to config would make the answer depend on an env var that
        // happens to be set on one host and not another.
        $candidates[] = $this->locales->default();

        foreach ($candidates as $candidate) {
            if ($this->locales->supports($candidate)) {
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
    private function fromAcceptLanguage(Request $request): ?string
    {
        foreach ($request->getLanguages() as $language) {
            $base = strtolower(substr($language, 0, 2));

            if ($this->locales->supports($base)) {
                return $base;
            }
        }

        return null;
    }
}
