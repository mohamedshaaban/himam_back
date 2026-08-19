<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Admin endpoints read and write translatable fields as full locale => text
 * maps, so the dashboard can offer one input per language. Reader endpoints
 * still receive a single resolved string via the API resources.
 */
trait HandlesTranslatableInput
{
    /**
     * Validation rules accepting a locale => text map for one field.
     */
    protected function translatableRules(string $field, bool $required = true): array
    {
        $rules = [$field => [$required ? 'required' : 'nullable', 'array']];

        foreach (array_keys(config('himam.locales')) as $locale) {
            $rules["{$field}.{$locale}"] = ['nullable', 'string'];
        }

        return $rules;
    }

    /**
     * Requires text in at least one language.
     *
     * Demanding every locale up front would block authors from saving a draft
     * they intend to translate later, but an entirely empty field would render
     * as a blank row in the app — so one language is the floor.
     */
    protected function assertHasAnyTranslation(Request $request, string ...$fields): void
    {
        foreach ($fields as $field) {
            $provided = array_filter((array) $request->input($field, []), fn ($text) => filled($text));

            if ($provided === []) {
                throw ValidationException::withMessages([
                    $field => [__('Provide a value in at least one language.')],
                ]);
            }
        }
    }

    /**
     * Drops empty locales so a blank input doesn't shadow the fallback text.
     */
    protected function cleanTranslations(array $data, string ...$fields): array
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $data) && is_array($data[$field])) {
                $data[$field] = array_filter($data[$field], fn ($text) => filled($text));
            }
        }

        return $data;
    }
}
