<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableInput;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramController extends Controller
{
    use HandlesTranslatableInput;

    public function index(): JsonResponse
    {
        $programs = Program::query()
            ->withCount(['books', 'members'])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $programs]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertHasAnyTranslation($request, 'title');

        $data = $request->validate($this->rules());

        $program = Program::create([
            ...$this->cleanTranslations($data, 'title', 'description'),
            'created_by' => $request->user()->id,
        ]);

        $program->settleVisibility();

        return response()->json(['data' => $program->fresh()], 201);
    }

    public function show(Program $program): JsonResponse
    {
        $program->load([
            'books:id,title,cover,points',
            'members:id,name,email',
        ]);

        return response()->json(['data' => $program]);
    }

    public function update(Request $request, Program $program): JsonResponse
    {
        $data = $request->validate($this->rules(required: false));

        $program->update($this->cleanTranslations($data, 'title', 'description'));
        $program->settleVisibility();

        return response()->json(['data' => $program->fresh()]);
    }

    public function destroy(Program $program): JsonResponse
    {
        $program->delete();

        return response()->json(['message' => __('Program deleted.')]);
    }

    // ─── Books ──────────────────────────────────────────────────────────────

    /**
     * Replaces the programme's book list and their order in one request.
     *
     * Sent whole rather than one book at a time: a sequential programme's
     * meaning depends on the order, and applying a reshuffle in pieces would
     * leave readers briefly looking at an order nobody intended.
     */
    public function syncBooks(Request $request, Program $program): JsonResponse
    {
        $data = $request->validate([
            'book_ids' => ['present', 'array'],
            'book_ids.*' => ['integer', 'exists:books,id'],
        ]);

        $program->books()->sync(
            collect($data['book_ids'])
                ->values()
                ->mapWithKeys(fn ($id, $index) => [$id => ['order_index' => $index]])
                ->all()
        );

        return response()->json([
            'data' => $program->fresh()->load('books:id,title,cover,points'),
        ]);
    }

    // ─── Assigned readers ───────────────────────────────────────────────────

    /**
     * Replaces the assigned readers of a selective programme.
     */
    public function syncMembers(Request $request, Program $program): JsonResponse
    {
        abort_unless(
            $program->isSelective(),
            422,
        );

        $data = $request->validate([
            'user_ids' => ['present', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $program->members()->sync(
            collect($data['user_ids'])
                ->mapWithKeys(fn ($id) => [$id => [
                    'assigned_by' => $request->user()->id,
                    'assigned_at' => now(),
                ]])
                ->all()
        );

        return response()->json([
            'data' => $program->fresh()->load('members:id,name,email'),
        ]);
    }

    private function rules(bool $required = true): array
    {
        return [
            ...$this->translatableRules('title', $required),
            ...$this->translatableRules('description', false),
            'type' => [$required ? 'required' : 'sometimes', Rule::in(Program::TYPES)],
            'cover' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_public' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
