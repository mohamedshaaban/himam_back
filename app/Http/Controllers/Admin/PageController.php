<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableInput;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The static pages behind /api/pages/{slug} — About, Privacy, and any others
 * added later. The slug is editable because the point of keying on one is that
 * a new page needs no route and no deploy.
 */
class PageController extends Controller
{
    use HandlesTranslatableInput;

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Page::orderBy('slug')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->assertHasAnyTranslation($request, 'title');

        $data = $request->validate($this->rules());

        $page = Page::create($this->cleanTranslations($data, 'title', 'body'));

        return response()->json(['data' => $page], 201);
    }

    public function show(Page $page): JsonResponse
    {
        return response()->json(['data' => $page]);
    }

    public function update(Request $request, Page $page): JsonResponse
    {
        $data = $request->validate($this->rules(required: false, ignore: $page->id));

        $page->update($this->cleanTranslations($data, 'title', 'body'));

        return response()->json(['data' => $page->fresh()]);
    }

    public function destroy(Page $page): JsonResponse
    {
        $page->delete();

        return response()->json(['message' => __('Page deleted.')]);
    }

    private function rules(bool $required = true, ?int $ignore = null): array
    {
        return [
            'slug' => [
                $required ? 'required' : 'sometimes',
                'string',
                'max:100',
                // Lower-case and hyphenated, because the slug goes straight into
                // a URL the app builds by hand.
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::unique('pages', 'slug')->ignore($ignore),
            ],
            ...$this->translatableRules('title', $required),
            ...$this->translatableRules('body', false),
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}
