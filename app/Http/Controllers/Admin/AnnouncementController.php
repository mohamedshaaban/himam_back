<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableInput;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    use HandlesTranslatableInput;

    private const CATEGORIES = ['general', 'program', 'exam', 'results', 'honor', 'certificate'];

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Announcement::with('user:id,name')->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertHasAnyTranslation($request, 'title');

        $data = $request->validate($this->rules());

        $announcement = Announcement::create($this->cleanTranslations($data, 'tag', 'title', 'body'));

        return response()->json(['data' => $announcement], 201);
    }

    public function show(Announcement $announcement): JsonResponse
    {
        return response()->json([
            'data' => $announcement->loadCount('readers'),
        ]);
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $data = $request->validate($this->rules(required: false));

        $announcement->update($this->cleanTranslations($data, 'tag', 'title', 'body'));

        return response()->json(['data' => $announcement->fresh()]);
    }

    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return response()->json(['message' => __('Announcement deleted.')]);
    }

    /**
     * Publishes now (or unpublishes back to a draft).
     */
    public function publish(Request $request, Announcement $announcement): JsonResponse
    {
        $data = $request->validate(['published' => ['required', 'boolean']]);

        $announcement->update([
            'published_at' => $data['published'] ? now() : null,
        ]);

        return response()->json(['data' => $announcement->fresh()]);
    }

    private function rules(bool $required = true): array
    {
        return [
            ...$this->translatableRules('tag', false),
            ...$this->translatableRules('title', $required),
            ...$this->translatableRules('body', false),
            'image' => ['nullable', 'string', 'max:255'],
            'category' => [$required ? 'required' : 'sometimes', Rule::in(self::CATEGORIES)],
            // Null targets everyone; an id sends it to one reader only.
            'user_id' => ['nullable', 'exists:users,id'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
