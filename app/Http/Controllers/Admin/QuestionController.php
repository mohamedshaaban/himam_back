<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableInput;
use App\Http\Controllers\Controller;
use App\Models\BookSection;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\LocaleRegistry;

class QuestionController extends Controller
{
    use HandlesTranslatableInput;

    public function index(BookSection $section): JsonResponse
    {
        return response()->json(['data' => $section->questions()->with('options')->get()]);
    }

    /**
     * Creates a question together with its options in one call — a question
     * without options is unanswerable, so they are never saved separately.
     */
    public function store(Request $request, BookSection $section): JsonResponse
    {
        $data = $this->validatePayload($request);

        $question = DB::transaction(function () use ($section, $data) {
            $question = $section->questions()->create([
                'text' => array_filter($data['text'], fn ($t) => filled($t)),
                'position' => $data['position'] ?? ($section->questions()->max('position') ?? 0) + 1,
            ]);

            $this->saveOptions($question, $data['options']);

            return $question;
        });

        return response()->json(['data' => $question->load('options')], 201);
    }

    public function update(Request $request, Question $question): JsonResponse
    {
        $data = $this->validatePayload($request);

        DB::transaction(function () use ($question, $data) {
            $question->update([
                'text' => array_filter($data['text'], fn ($t) => filled($t)),
                'position' => $data['position'] ?? $question->position,
            ]);

            // Options are replaced wholesale; editing them in place would leave
            // stale rows behind when an author removes a choice.
            $question->options()->delete();
            $this->saveOptions($question, $data['options']);
        });

        return response()->json(['data' => $question->fresh()->load('options')]);
    }

    public function destroy(Question $question): JsonResponse
    {
        $question->delete();

        return response()->json(['message' => __('Question deleted.')]);
    }

    private function validatePayload(Request $request): array
    {
        $this->assertHasAnyTranslation($request, 'text');

        $rules = [
            ...$this->translatableRules('text'),
            'position' => ['nullable', 'integer', 'min:0'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.is_correct' => ['required', 'boolean'],
            'options.*.text' => ['required', 'array'],
        ];

        foreach (app(LocaleRegistry::class)->codes() as $locale) {
            $rules["options.*.text.{$locale}"] = ['nullable', 'string'];
        }

        $data = $request->validate($rules);

        // Grading picks a single correct option, so anything else would make
        // the quiz unscoreable.
        $correct = collect($data['options'])->where('is_correct', true)->count();

        if ($correct !== 1) {
            throw ValidationException::withMessages([
                'options' => [__('Mark exactly one option as correct.')],
            ]);
        }

        foreach ($data['options'] as $index => $option) {
            if (array_filter($option['text'], fn ($t) => filled($t)) === []) {
                throw ValidationException::withMessages([
                    "options.{$index}.text" => [__('Provide a value in at least one language.')],
                ]);
            }
        }

        return $data;
    }

    private function saveOptions(Question $question, array $options): void
    {
        foreach (array_values($options) as $index => $option) {
            $question->options()->create([
                'text' => array_filter($option['text'], fn ($t) => filled($t)),
                'is_correct' => (bool) $option['is_correct'],
                'position' => $index,
            ]);
        }
    }
}
