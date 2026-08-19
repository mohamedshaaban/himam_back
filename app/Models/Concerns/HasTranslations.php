<?php

namespace App\Models\Concerns;

/**
 * Stores translatable attributes as a JSON map of locale => text.
 *
 * Models opt in by casting the attribute to 'array' and listing it in
 * $translatable. Reads go through t() which degrades gracefully: requested
 * locale, then the fallback locale, then whatever translation exists — so a
 * partially translated record still renders instead of showing a blank.
 */
trait HasTranslations
{
    /**
     * The raw locale => text map for an attribute.
     */
    public function translations(string $attribute): array
    {
        $value = $this->getAttribute($attribute);

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            // A plain string means the row predates translation; treat it as
            // being written in the fallback locale rather than losing it.
            $value = is_array($decoded)
                ? $decoded
                : [config('app.fallback_locale') => $value];
        }

        return is_array($value) ? array_filter($value, fn ($text) => $text !== null && $text !== '') : [];
    }

    /**
     * The best available translation for the given (or current) locale.
     */
    public function t(string $attribute, ?string $locale = null): ?string
    {
        $translations = $this->translations($attribute);

        if ($translations === []) {
            return null;
        }

        $locale ??= app()->getLocale();

        return $translations[$locale]
            ?? $translations[config('app.fallback_locale')]
            ?? reset($translations);
    }

    /**
     * Attributes stored as locale => text maps.
     */
    public function translatableAttributes(): array
    {
        return $this->translatable ?? [];
    }
}
