<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableInput;
use App\Http\Controllers\Controller;
use App\Models\Badge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BadgeController extends Controller
{
    use HandlesTranslatableInput;

    private const CRITERIA = ['manual', 'sections_passed', 'books_completed', 'points'];

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Badge::withCount('users')->orderBy('position')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertHasAnyTranslation($request, 'name');

        $data = $request->validate($this->rules());

        $badge = Badge::create($this->cleanTranslations($data, 'name', 'description'));

        return response()->json(['data' => $badge], 201);
    }

    public function update(Request $request, Badge $badge): JsonResponse
    {
        $data = $request->validate($this->rules(required: false));

        $badge->update($this->cleanTranslations($data, 'name', 'description'));

        return response()->json(['data' => $badge->fresh()]);
    }

    public function destroy(Badge $badge): JsonResponse
    {
        $badge->delete();

        return response()->json(['message' => __('Badge deleted.')]);
    }

    /**
     * Grants a badge outright, for the recognitions that aren't automatic.
     */
    public function award(Request $request, Badge $badge): JsonResponse
    {
        $data = $request->validate(['user_id' => ['required', 'exists:users,id']]);

        $badge->users()->syncWithoutDetaching([
            $data['user_id'] => ['awarded_at' => now()],
        ]);

        return response()->json(['message' => __('Badge awarded.')]);
    }

    public function revoke(Request $request, Badge $badge): JsonResponse
    {
        $data = $request->validate(['user_id' => ['required', 'exists:users,id']]);

        $badge->users()->detach($data['user_id']);

        return response()->json(['message' => __('Badge revoked.')]);
    }

    private function rules(bool $required = true): array
    {
        return [
            ...$this->translatableRules('name', $required),
            ...$this->translatableRules('description', false),
            'image' => ['nullable', 'string', 'max:255'],
            'criteria_type' => [$required ? 'required' : 'sometimes', Rule::in(self::CRITERIA)],
            'criteria_value' => ['nullable', 'integer', 'min:0'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
