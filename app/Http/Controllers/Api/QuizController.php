<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BadgeResource;
use App\Http\Resources\CertificateResource;
use App\Http\Resources\QuestionResource;
use App\Models\BookSection;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(private readonly ProgressService $progress)
    {
    }

    /**
     * The questions for a section, without the answer key.
     */
    public function show(Request $request, BookSection $section): JsonResponse
    {
        abort_unless($section->book->is_published, 404);

        $questions = $section->questions()->with('options')->get();

        $lastAttempt = $request->user()
            ?->quizAttempts()
            ->where('book_section_id', $section->id)
            ->latest()
            ->first();

        return response()->json([
            'data' => QuestionResource::collection($questions),
            'meta' => [
                'section' => [
                    'id' => $section->id,
                    'title' => $section->t('title'),
                    'position' => $section->position,
                ],
                'book' => [
                    'id' => $section->book->id,
                    'title' => $section->book->t('title'),
                ],
                'pass_ratio' => (float) config('himam.quiz_pass_ratio'),
                'last_attempt' => $lastAttempt ? [
                    'score' => $lastAttempt->score,
                    'total' => $lastAttempt->total,
                    'passed' => $lastAttempt->passed,
                    'created_at' => $lastAttempt->created_at->toIso8601String(),
                ] : null,
            ],
        ]);
    }

    /**
     * Grades an attempt, credits points, and reports anything newly unlocked.
     */
    public function submit(Request $request, BookSection $section): JsonResponse
    {
        abort_unless($section->book->is_published, 404);

        $validated = $request->validate([
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['required', 'integer'],
        ]);

        // Keys arrive as strings over JSON; the service compares them as ints.
        $answers = collect($validated['answers'])
            ->mapWithKeys(fn ($optionId, $questionId) => [(int) $questionId => (int) $optionId])
            ->all();

        $result = $this->progress->submitQuiz($request->user(), $section, $answers);
        $attempt = $result['attempt'];

        return response()->json([
            'score' => $attempt->score,
            'total' => $attempt->total,
            'passed' => $attempt->passed,
            'points_awarded' => $attempt->points_awarded,
            'correct_options' => $result['correct'],
            'new_badges' => BadgeResource::collection($result['new_badges']),
            'new_certificates' => CertificateResource::collection($result['new_certificates']),
            'stats' => $this->progress->stats($request->user()->refresh()),
        ]);
    }
}
