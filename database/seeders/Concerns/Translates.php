<?php

namespace Database\Seeders\Concerns;

trait Translates
{
    /**
     * Builds a locale => text map in the order declared in config/himam.php.
     *
     * Positional on purpose: seed content reads as a parallel column of the
     * same sentence in each language, which makes gaps obvious at a glance.
     */
    protected function tr(string $ar, string $en, string $fr, string $ur): array
    {
        return array_filter([
            'ar' => $ar,
            'en' => $en,
            'fr' => $fr,
            'ur' => $ur,
        ], fn ($text) => $text !== '');
    }
}
