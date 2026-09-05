<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the bearer token count on routes that don't require one.
 *
 * Several endpoints are deliberately public so the landing screens can preview
 * the catalogue — books, badges — but they still personalise their answer when
 * a reader is signed in: which sections they have passed, which badges they
 * hold. Without an auth guard on the route, Laravel never inspects the token,
 * so $request->user() was null even for a perfectly valid one, and every reader
 * looked like a brand new visitor with no progress at all.
 *
 * Resolving the user here rather than route by route keeps the two facts apart:
 * whether a route *requires* a reader (auth:sanctum, still enforced separately)
 * and whether it *recognises* one. An absent or invalid token stays anonymous.
 */
class ResolveOptionalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken()) {
            $user = $request->user('sanctum');

            if ($user) {
                $request->setUserResolver(fn () => $user);
            }
        }

        return $next($request);
    }
}
