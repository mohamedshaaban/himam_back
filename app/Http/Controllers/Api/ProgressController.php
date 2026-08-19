<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function __construct(private readonly ProgressService $progress)
    {
    }

    /**
     * Everything the reader's progress screen renders: overall counters, a
     * level-by-level and book-by-book breakdown down to individual sections,
     * the badges they're closest to, recent attempts, and points by month.
     */
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json($this->progress->detail($request->user()));
    }
}
