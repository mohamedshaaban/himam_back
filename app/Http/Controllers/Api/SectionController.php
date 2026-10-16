<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesProgramAccess;
use App\Http\Resources\SectionResource;
use App\Models\BookSection;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    use EnforcesProgramAccess;

    public function __construct(private readonly ProgressService $progress)
    {
    }

    /**
     * Records that this reader has read the section.
     *
     * Separate from fetching it, so the record reflects a decision the app made
     * rather than a request it happened to repeat. Passing the quiz is still
     * what earns the points; this only marks it seen.
     */
    public function markRead(Request $request, BookSection $section): JsonResponse
    {
        abort_unless($section->book->isAvailable(), 404);

        $this->assertCanRead($request, $section->book);

        $user = $request->user();

        $this->progress->markRead($user, $section);

        return response()->json([
            'data' => [
                'id' => $section->id,
                'read' => true,
                'passed' => in_array($section->id, $user->passedSectionIds(), true),
            ],
        ]);
    }

    /**
     * The reading screen: the section text plus where it sits in the book.
     */
    public function show(Request $request, BookSection $section): SectionResource
    {
        abort_unless($section->book->isAvailable(), 404);

        $this->assertCanRead($request, $section->book);

        $section->load('book')->loadCount('questions');

        $section->bookHasOwnQuiz = $section->book->hasOwnQuiz();

        // Reading is recorded by POST /sections/{section}/read, not here. A GET
        // is the request a client is most free to repeat — a prefetch, a retry,
        // a cache warm — and none of those mean a reader has read anything.
        if ($user = $request->user()) {
            $section->readerRead = $user->progress()
                ->where('book_section_id', $section->id)
                ->whereNotNull('read_at')
                ->exists();

            $section->readerPassed = in_array($section->id, $user->passedSectionIds(), true);
        }

        return (new SectionResource($section))->additional([
            'meta' => [
                'book' => [
                    'id' => $section->book->id,
                    'title' => $section->book->t('title'),

                    // Where the exam lives, so the screen sends the reader to
                    // the right one instead of guessing from the section.
                    'has_own_quiz' => $section->book->hasOwnQuiz(),
                    'quiz_button' => $section->book->quizButton(),
                ],
                'position' => $section->position,
                'sections_total' => $section->book->sections()->count(),
            ],
        ]);
    }
}
