<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Models\Book;
use App\Models\Certificate;
use App\Models\Level;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(private readonly ProgressService $progress)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $certificates = Certificate::with(['user:id,name,email', 'level', 'book'])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->latest('issued_at')
            ->paginate($request->integer('per_page', 25) ?: 25);

        return response()->json(
            CertificateResource::collection($certificates)->response()->getData(true)
        );
    }

    /**
     * Issues a certificate by hand, for cases the automatic rule doesn't cover.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'level_id' => ['nullable', 'exists:levels,id'],
            'book_id' => ['nullable', 'exists:books,id'],
        ]);

        $certificate = $this->progress->issueCertificate(
            User::findOrFail($data['user_id']),
            isset($data['level_id']) ? Level::find($data['level_id']) : null,
            isset($data['book_id']) ? Book::find($data['book_id']) : null,
        );

        return response()->json([
            'data' => new CertificateResource($certificate->load(['user', 'level', 'book'])),
        ], 201);
    }

    public function destroy(Certificate $certificate): JsonResponse
    {
        $certificate->delete();

        return response()->json(['message' => __('Certificate revoked.')]);
    }
}
