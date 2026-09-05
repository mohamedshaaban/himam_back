<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableInput;
use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    use HandlesTranslatableInput;

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Faq::orderBy('position')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertHasAnyTranslation($request, 'question', 'answer');

        $data = $request->validate($this->rules());

        // New questions go to the bottom rather than colliding at position 0.
        $data['position'] ??= (int) Faq::max('position') + 1;

        $faq = Faq::create($this->cleanTranslations($data, 'question', 'answer'));

        return response()->json(['data' => $faq], 201);
    }

    public function update(Request $request, Faq $faq): JsonResponse
    {
        $data = $request->validate($this->rules(required: false));

        $faq->update($this->cleanTranslations($data, 'question', 'answer'));

        return response()->json(['data' => $faq->fresh()]);
    }

    public function destroy(Faq $faq): JsonResponse
    {
        $faq->delete();

        return response()->json(['message' => __('Question deleted.')]);
    }

    /**
     * Saves a whole new ordering at once.
     *
     * Reordering one row at a time would leave the list briefly inconsistent
     * between requests, and the dashboard already knows the final order.
     */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:faqs,id'],
        ]);

        foreach ($data['ids'] as $position => $id) {
            Faq::whereKey($id)->update(['position' => $position]);
        }

        return response()->json([
            'data' => Faq::orderBy('position')->orderBy('id')->get(),
        ]);
    }

    private function rules(bool $required = true): array
    {
        return [
            ...$this->translatableRules('question', $required),
            ...$this->translatableRules('answer', $required),
            'category' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
