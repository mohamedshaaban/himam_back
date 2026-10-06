<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LevelResource;
use App\Http\Resources\SlideResource;
use App\Models\Level;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Small public lookups the frontend needs before a reader signs in.
 */
class ReferenceController extends Controller
{
    public function locales(): JsonResponse
    {
        $registry = app(\App\Services\LocaleRegistry::class);

        $locales = collect($registry->all())
            ->map(fn (array $meta, string $code) => ['code' => $code] + $meta)
            ->values();

        return response()->json([
            'data' => $locales,
            'meta' => [
                'default' => $registry->default(),
                'fallback' => $registry->default(),
                'current' => app()->getLocale(),
            ],
        ]);
    }

    /**
     * Countries for the registration form, named and ordered in the request's
     * language. Public, because the form is reachable before signing in.
     */
    public function countries(): JsonResponse
    {
        $locale = app()->getLocale();

        return response()->json([
            'data' => app(\App\Services\CountryRegistry::class)->all($locale),
            'meta' => ['locale' => $locale],
        ]);
    }

    public function levels(): AnonymousResourceCollection
    {
        return LevelResource::collection(
            Level::where('is_active', true)->withCount('books')->orderBy('position')->get()
        );
    }

    public function slides(Request $request, string $screen): AnonymousResourceCollection
    {
        return SlideResource::collection(
            Slide::active()->where('screen', $screen)->orderBy('position')->get()
        );
    }
}
