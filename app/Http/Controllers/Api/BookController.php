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
            ->withCount(['sections', 'questions'])
            ->when($request->filled('level_id'), fn ($q) => $q->where('level_id', $request->integer('level_id')))
            ->orderBy('position')
            ->get();

        $this->attachProgress($books, $request);

        return BookResource::collection($books);
    }

    public function show(Request $request, Book $book): BookResource
    {
        // The level is loaded once and then kept: isAvailable() needs it to
        // answer, and load() would fetch it a second time.
        $book->loadMissing('level');

        abort_unless($book->isAvailable(), 404);

        $this->assertCanRead($request, $book);

        $book->load(['sections' => fn ($q) => $q->withCount('questions')])
            ->loadCount('questions');

        $read = $request->user()?->readSectionIds() ?? [];

        $book->readerFullyRead = $request->user()
            ? $book->fullyReadBy($read, $request->user()->readBookIds(), $book->sections->pluck('id'))
            : null;

        foreach ($book->sections as $section) {
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

        $passedBooks = $user->passedBookIds();
        $readSections = $user->readSectionIds();
        $readBooks = $user->readBookIds();

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
            $read = $sectionIds->intersect($readSections)->count();

            // Sections are read, not passed — the exam is the book's. So the
            // only section-level measure is how much has been read, and
            // `completed` answers whether the book itself is done.
            $book->readerProgress = [
                'sections_total' => $total,
                'sections_read' => $read,
                'read_percent' => $total > 0 ? (int) round($read / $total * 100) : 0,
                'completed' => $book->completedBy($passedBooks),
            ];

            // What the exam button hangs on: the whole book read, not part.
            $book->readerFullyRead = $book->fullyReadBy($readSections, $readBooks, $sectionIds);
        }
    }
}
