<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableInput;
use App\Http\Controllers\Controller;
use App\Models\Level;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    use HandlesTranslatableInput;

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Level::withCount('books')->orderBy('position')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertHasAnyTranslation($request, 'name');

        $data = $request->validate([
            ...$this->translatableRules('name'),
            ...$this->translatableRules('description', false),
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $level = Level::create($this->cleanTranslations($data, 'name', 'description'));

        return response()->json(['data' => $level], 201);
    }

    public function show(Level $level): JsonResponse
    {
        return response()->json(['data' => $level->load('books')]);
    }

    public function update(Request $request, Level $level): JsonResponse
    {
        $data = $request->validate([
            ...$this->translatableRules('name', false),
            ...$this->translatableRules('description', false),
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $level->update($this->cleanTranslations($data, 'name', 'description'));

        return response()->json(['data' => $level->fresh()]);
    }

    public function destroy(Level $level): JsonResponse
    {
        // Deleting a level cascades to its books and their sections, which
        // would silently void readers' progress — so require it be emptied.
        if ($level->books()->exists()) {
            return response()->json([
                'message' => __('Move or delete this level\'s books before deleting the level.'),
            ], 422);
        }

        $level->delete();

        return response()->json(['message' => __('Level deleted.')]);
    }
}
