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
