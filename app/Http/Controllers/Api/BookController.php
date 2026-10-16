<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\EnforcesProgramAccess;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\BookSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    use EnforcesProgramAccess;

    public function index(Request $request): AnonymousResourceCollection
    {
        $books = Book::published()
            ->with('level')
            // sections_with_quiz_count keeps examSummary() to one query for the
            // whole index rather than one per book.
            ->withCount([
                'sections',
                'questions',
                'sections as sections_with_quiz_count' => fn ($q) => $q->whereHas('questions'),
            ])
            ->when($request->filled('level_id'), fn ($q) => $q->where('level_id', $request->integer('level_id')))
            ->orderBy('position')
            ->get();

        $this->attachProgress($books, $request);

        return BookResource::collection($books);
    }

    public function show(Request $request, Book $book): BookResource
    {
        abort_unless($book->is_published, 404);

        $this->assertCanRead($request, $book);

        $book->load(['level', 'sections' => fn ($q) => $q->withCount('questions')])
            ->loadCount('questions');

        $passed = $request->user()?->passedSectionIds() ?? [];

        $read = $request->user()?->readSectionIds() ?? [];

        foreach ($book->sections as $section) {
            $section->readerPassed = in_array($section->id, $passed, true);
            $section->readerRead = in_array($section->id, $read, true);
        }

        $this->attachProgress(collect([$book]), $request);

        return new BookResource($book);
    }

    /**
     * Decorates each book with this reader's completion counts. Anonymous
     * visitors get no progress block at all rather than zeroes.
     */
    private function attachProgress(\Illuminate\Support\Collection $books, Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            return;
        }

        $passed = $user->passedSectionIds();
        $passedBooks = $user->passedBookIds();
        $readSections = $user->readSectionIds();

        // One query for every book's section ids, rather than one per book.
        // On a database in another region each of those round trips costs more
        // than the work it does, and the catalogue index was paying fourteen of
        // them to answer a question one query can.
        $sectionIdsByBook = $books->contains(fn ($book) => ! $book->relationLoaded('sections'))
            ? BookSection::whereIn('book_id', $books->pluck('id'))
                ->get(['id', 'book_id'])
                ->groupBy('book_id')
                ->map(fn ($rows) => $rows->pluck('id'))
            : collect();

        $sectionsFor = function (Book $book) use ($books, $sectionIdsByBook) {
            if ($book->relationLoaded('sections')) {
                return $book->sections->pluck('id');
            }

            return $sectionIdsByBook->get($book->id, collect())->values();
        };

        foreach ($books as $book) {
            $sectionIds = $sectionsFor($book);

            $total = $sectionIds->count();
            $done = $sectionIds->intersect($passed)->count();
            $read = $sectionIds->intersect($readSections)->count();

            // Reading and passing are reported side by side rather than folded
            // into one number. When the exam is the book's own there are no
            // section passes to count, and a reader who has read every section
            // would otherwise sit at 0% with nothing to show for it.
            $book->readerProgress = [
                'sections_total' => $total,
                'sections_read' => $read,
                'sections_passed' => $done,
                'read_percent' => $total > 0 ? (int) round($read / $total * 100) : 0,
                'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
                'completed' => $book->completedBy($passed, $passedBooks, $sectionIds),
            ];
        }
    }
}
