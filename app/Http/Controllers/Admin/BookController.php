<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableInput;
use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController extends Controller
{
    use HandlesTranslatableInput;

    public function index(Request $request): JsonResponse
    {
        $books = Book::with('level')
            ->withCount('sections')
            ->when($request->filled('level_id'), fn ($q) => $q->where('level_id', $request->integer('level_id')))
            ->orderBy('position')
            ->get();

        return response()->json(['data' => $books]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertHasAnyTranslation($request, 'title');

        $data = $request->validate([
            'level_id' => ['required', 'exists:levels,id'],
            ...$this->translatableRules('title'),
            ...$this->translatableRules('author', false),
            ...$this->translatableRules('description', false),
            'cover' => ['nullable', 'string', 'max:255'],
            'pages' => ['nullable', 'integer', 'min:0'],
            'points' => ['nullable', 'integer', 'min:0'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $book = Book::create($this->cleanTranslations($data, 'title', 'author', 'description'));

        return response()->json(['data' => $book->load('level')], 201);
    }

    public function show(Book $book): JsonResponse
    {
        return response()->json([
            'data' => $book->load(['level', 'sections.questions.options']),
        ]);
    }

    public function update(Request $request, Book $book): JsonResponse
    {
        $data = $request->validate([
            'level_id' => ['sometimes', 'exists:levels,id'],
            ...$this->translatableRules('title', false),
            ...$this->translatableRules('author', false),
            ...$this->translatableRules('description', false),
            'cover' => ['nullable', 'string', 'max:255'],
            'pages' => ['nullable', 'integer', 'min:0'],
            'points' => ['nullable', 'integer', 'min:0'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $book->update($this->cleanTranslations($data, 'title', 'author', 'description'));

        return response()->json(['data' => $book->fresh()->load('level')]);
    }

    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json(['message' => __('Book deleted.')]);
    }
}
