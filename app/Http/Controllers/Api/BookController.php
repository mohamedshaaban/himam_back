<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $books = Book::published()
            ->with('level')
            ->withCount('sections')
            ->when($request->filled('level_id'), fn ($q) => $q->where('level_id', $request->integer('level_id')))
            ->orderBy('position')
            ->get();

        $this->attachProgress($books, $request);

        return BookResource::collection($books);
    }

    public function show(Request $request, Book $book): BookResource
    {
        abort_unless($book->is_published, 404);

        $book->load(['level', 'sections' => fn ($q) => $q->withCount('questions')]);

        $passed = $request->user()?->passedSectionIds() ?? [];
        $read = $request->user()
            ? $request->user()->progress()->whereNotNull('read_at')->pluck('book_section_id')->all()
            : [];

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

        foreach ($books as $book) {
            $sectionIds = $book->relationLoaded('sections')
                ? $book->sections->pluck('id')
                : $book->sections()->pluck('id');

            $done = $sectionIds->intersect($passed)->count();
            $total = $sectionIds->count();

            $book->readerProgress = [
                'sections_passed' => $done,
                'sections_total' => $total,
                'completed' => $total > 0 && $done === $total,
                'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
            ];
        }
    }
}
