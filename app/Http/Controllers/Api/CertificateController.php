<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CertificateController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return CertificateResource::collection(
            $request->user()->certificates()->with(['level', 'book'])->latest('issued_at')->get()
        );
    }

    /**
     * Every certificate the programme can issue, earned or not.
     *
     * This backs the "all" tab next to "earned": a reader should be able to see
     * what is still ahead of them, with how much of it is done, not just what
     * they already hold.
     */
    public function available(Request $request): JsonResponse
    {
        $user = $request->user();
        $passed = $user->passedSectionIds();
        $held = $user->certificates()->whereNotNull('level_id')->get()->keyBy('level_id');

        $levels = \App\Models\Level::query()
            ->where('is_active', true)
            ->with(['books' => fn ($q) => $q->where('is_published', true), 'books.sections:id,book_id'])
            ->orderBy('position')
            ->get();

        $data = $levels->map(function ($level) use ($passed, $held) {
            $sectionIds = $level->books->flatMap->sections->pluck('id');
            $done = $sectionIds->intersect($passed)->count();
            $total = $sectionIds->count();
            $certificate = $held->get($level->id);

            return [
                'level' => ['id' => $level->id, 'name' => $level->t('name')],
                'title' => $level->t('name'),
                'earned' => (bool) $certificate,
                'serial' => $certificate?->serial,
                'issued_at' => $certificate?->issued_at?->toDateString(),
                'verification_url' => $certificate
                    ? url("/api/certificates/verify/{$certificate->verification_code}")
                    : null,
                'sections_passed' => $done,
                'sections_total' => $total,
                'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function show(Request $request, Certificate $certificate): CertificateResource
    {
        abort_unless($certificate->user_id === $request->user()->id, 403);

        return new CertificateResource($certificate->load(['level', 'book', 'user']));
    }

    /**
     * Public endpoint behind the QR code. Returns only what a verifier needs to
     * confirm the certificate is genuine — no contact details.
     */
    public function verify(string $code): JsonResponse
    {
        $certificate = Certificate::with(['level', 'book', 'user'])
            ->where('verification_code', $code)
            ->first();

        if (! $certificate) {
            return response()->json([
                'valid' => false,
                'message' => __('No certificate matches this code.'),
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'serial' => $certificate->serial,
            'holder' => $certificate->user->name,
            'title' => $certificate->t('title'),
            'level' => $certificate->level?->t('name'),
            'issued_at' => $certificate->issued_at?->toDateString(),
        ]);
    }
}
