<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Locale;
use App\Services\LocaleRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    public function __construct(private readonly LocaleRegistry $registry)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Locale::orderBy('position')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $locale = DB::transaction(function () use ($data) {
            $locale = Locale::create($data);
            $this->settleDefault($locale, $data);

            return $locale;
        });

        $this->registry->forget();

        return response()->json(['data' => $locale->fresh()], 201);
    }

    public function update(Request $request, Locale $locale): JsonResponse
    {
        $data = $request->validate($this->rules($locale));

        DB::transaction(function () use ($locale, $data) {
            $locale->update($data);
            $this->settleDefault($locale, $data);
        });

        $this->registry->forget();

        return response()->json(['data' => $locale->fresh()]);
    }

    public function destroy(Locale $locale): JsonResponse
    {
        // Deleting the fallback would leave records whose only translation is in
        // a language the platform no longer resolves.
        if ($locale->is_default) {
            return response()->json([
                'message' => __('Set another language as the default before deleting this one.'),
            ], 422);
        }

        if (Locale::count() <= 1) {
            return response()->json([
                'message' => __('At least one language must remain.'),
            ], 422);
        }

        $locale->delete();
        $this->registry->forget();

        return response()->json(['message' => __('Language deleted.')]);
    }

    /**
     * Exactly one locale is the default, and the default cannot be inactive —
     * otherwise the fallback would point at a language nothing can resolve.
     */
    private function settleDefault(Locale $locale, array $data): void
    {
        if (! ($data['is_default'] ?? false)) {
            return;
        }

        Locale::where('id', '!=', $locale->id)->update(['is_default' => false]);
        $locale->forceFill(['is_default' => true, 'is_active' => true])->save();
    }

    private function rules(?Locale $locale = null): array
    {
        return [
            'code' => [
                $locale ? 'sometimes' : 'required',
                'string',
                'max:10',
                'regex:/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})?$/',
                Rule::unique('locales', 'code')->ignore($locale?->id),
            ],
            'name' => [$locale ? 'sometimes' : 'required', 'string', 'max:80'],
            'english_name' => [$locale ? 'sometimes' : 'required', 'string', 'max:80'],
            'direction' => [$locale ? 'sometimes' : 'required', Rule::in(['ltr', 'rtl'])],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
