<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableInput;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\BookSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    use HandlesTranslatableInput;

    public function index(Book $book): JsonResponse
    {
        return response()->json([
            'data' => $book->sections()->withCount('questions')->get(),
        ]);
    }

    public function store(Request $request, Book $book): JsonResponse
    {
        $this->assertHasAnyTranslation($request, 'title');

        $data = $request->validate([
            ...$this->translatableRules('title'),
            ...$this->translatableRules('body', false),
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        // Default to appending, so an author adding sections in order never has
        // to think about the position field.
        $data['position'] ??= ($book->sections()->max('position') ?? 0) + 1;

        $section = $book->sections()->create($this->cleanTranslations($data, 'title', 'body'));

        return response()->json(['data' => $section], 201);
    }

    public function show(BookSection $section): JsonResponse
    {
        return response()->json(['data' => $section->load('questions.options')]);
    }

    public function update(Request $request, BookSection $section): JsonResponse
    {
        $data = $request->validate([
            ...$this->translatableRules('title', false),
            ...$this->translatableRules('body', false),
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $section->update($this->cleanTranslations($data, 'title', 'body'));

        return response()->json(['data' => $section->fresh()]);
    }

    public function destroy(BookSection $section): JsonResponse
    {
        $section->delete();

        return response()->json(['message' => __('Section deleted.')]);
    }
}
