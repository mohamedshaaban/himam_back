<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\EnforcesProgramAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\BadgeResource;
use App\Http\Resources\CertificateResource;
use App\Http\Resources\QuestionResource;
use App\Models\Book;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The quiz for a whole book.
 *
 * A book is read either in sections, each with its own quiz, or as one document
 * with one quiz. Never both: the points for finishing the material are awarded
 * once, and two places to earn them would mean two answers to "has this reader
 * finished the book".
 */
class BookQuizController extends Controller
{
    use EnforcesProgramAccess;

    public function __construct(private readonly ProgressService $progress)
    {
    }

    public function show(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        $this->assertCanRead($request, $book);

        // 404 rather than an empty quiz: a book read in sections has no
        // book-level quiz, and saying "here is nothing" invites a client to
        // render an empty exam.
        abort_unless($book->hasOwnQuiz(), 404, __('This book is examined section by section, so it has no exam of its own.'));

        $questions = $book->questions()->with('options')->get();

        $attempts = $request->user()
            ?->quizAttempts()
            ->where('book_id', $book->id)
            ->latest()
            ->get() ?? collect();

        $lastAttempt = $attempts->first();

        return response()->json([
            'data' => QuestionResource::collection($questions)->resolve(),
            'meta' => [
                'book' => ['id' => $book->id, 'title' => $book->t('title')],
                'questions_total' => $questions->count(),
                'pass_ratio' => (float) config('himam.quiz_pass_ratio'),
                'quiz_button' => $book->quizButton(),

                // Same shape the section quiz reports, so a client does not
                // need two readings of "has this been taken".
                'taken' => $attempts->isNotEmpty(),
                'attempts' => $attempts->count(),
                'passed' => $attempts->contains('passed', true),
                'best_score' => $attempts->max('score'),

                'last_attempt' => $lastAttempt ? [
                    'score' => $lastAttempt->score,
                    'total' => $lastAttempt->total,
                    'passed' => $lastAttempt->passed,
                    'created_at' => $lastAttempt->created_at->toIso8601String(),
                ] : null,
            ],
        ]);
    }

    public function submit(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        $this->assertCanRead($request, $book);
        abort_unless($book->hasOwnQuiz(), 404, __('This book is examined section by section, so it has no exam of its own.'));

        $data = $request->validate([
            'answers' => ['present', 'array'],
            'answers.*' => ['nullable', 'integer'],
        ]);

        $result = $this->progress->submitBookQuiz($request->user(), $book, $data['answers']);

        $attempt = $result['attempt'];

        // Deliberately the same flat shape the section quiz returns: one quiz
        // screen submits to either endpoint, and an envelope here but not there
        // would make it read the result two different ways for no reason.
        return response()->json([
            'score' => $attempt->score,
            'total' => $attempt->total,
            'passed' => $attempt->passed,
            'points_awarded' => $attempt->points_awarded,

            // The key is revealed only to a reader who passed. A resit is free
            // and unlimited, so handing back the answers after a failure would
            // turn the first attempt into a way of being told them — and the
            // second into a formality. A failed attempt gets the questions it
            // got wrong instead, which is what a reader needs to know to go
            // back and read again.
            'correct_options' => $attempt->passed ? $result['correct'] : null,
            'incorrect_questions' => $attempt->passed ? [] : $result['incorrect'],
            'new_badges' => BadgeResource::collection($result['new_badges']),
            'new_certificates' => CertificateResource::collection($result['new_certificates']),
            'stats' => $this->progress->stats($request->user()->refresh()),
        ]);
    }

    /**
     * Records that the reader opened a book that has no sections.
     */
    public function markRead(Request $request, Book $book): JsonResponse
    {
        abort_unless($book->is_published, 404);

        $this->assertCanRead($request, $book);

        $this->progress->markBookRead($request->user(), $book);

        return response()->json([
            'data' => ['id' => $book->id, 'read' => true],
        ]);
    }
}
