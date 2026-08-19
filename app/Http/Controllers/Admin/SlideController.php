<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableInput;
use App\Http\Controllers\Controller;
use App\Models\Slide;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SlideController extends Controller
{
    use HandlesTranslatableInput;

    public function index(Request $request): JsonResponse
    {
        $slides = Slide::query()
            ->when($request->filled('screen'), fn ($q) => $q->where('screen', $request->string('screen')))
            ->orderBy('screen')
            ->orderBy('position')
            ->get();

        return response()->json(['data' => $slides]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $slide = Slide::create($this->cleanTranslations($data, 'caption'));

        return response()->json(['data' => $slide], 201);
    }

    public function update(Request $request, Slide $slide): JsonResponse
    {
        $data = $request->validate($this->rules(required: false));

        $slide->update($this->cleanTranslations($data, 'caption'));

        return response()->json(['data' => $slide->fresh()]);
    }

    public function destroy(Slide $slide): JsonResponse
    {
        $slide->delete();

        return response()->json(['message' => __('Slide deleted.')]);
    }

    private function rules(bool $required = true): array
    {
        return [
            'screen' => [$required ? 'required' : 'sometimes', 'string', 'max:60'],
            'image' => [$required ? 'required' : 'sometimes', 'string', 'max:255'],
            ...$this->translatableRules('caption', false),
            'href' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
