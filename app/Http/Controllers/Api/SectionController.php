<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectionResource;
use App\Models\BookSection;
use App\Services\ProgressService;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function __construct(private readonly ProgressService $progress)
    {
    }

    /**
     * The reading screen: the section text plus where it sits in the book.
     */
    public function show(Request $request, BookSection $section): SectionResource
    {
        abort_unless($section->book->is_published, 404);

        $section->load('book')->loadCount('questions');

        // Opening a section counts as having read it; passing still requires
        // the quiz.
        if ($user = $request->user()) {
            $this->progress->markRead($user, $section);

            $section->readerRead = true;
            $section->readerPassed = in_array($section->id, $user->passedSectionIds(), true);
        }

        return (new SectionResource($section))->additional([
            'meta' => [
                'book' => [
                    'id' => $section->book->id,
                    'title' => $section->book->t('title'),
                ],
                'position' => $section->position,
                'sections_total' => $section->book->sections()->count(),
            ],
        ]);
    }
}
