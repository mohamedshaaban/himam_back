<?php

namespace App\Services;

use App\Models\Locale;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * The set of languages the platform speaks, resolved from the database.
 *
 * This replaces the old config('himam.locales') list so an administrator can add
 * or disable a language without a deploy. Content needs no schema change to
 * follow: translatable fields are already locale => text maps.
 *
 * Reads are cached because nearly every request needs the supported list (the
 * SetLocale middleware alone runs on all of them), and the list changes about
 * as often as someone edits it. Every write path calls forget().
 */
class LocaleRegistry
{
    private const CACHE_KEY = 'himam.locales';

    /**
     * Remembered for the length of one request, on top of the cache.
     *
     * The cache store is itself the database on this deployment, so a cache
     * read is a round trip to another region — and the SetLocale middleware,
     * the resources and the translatable fields all ask repeatedly within a
     * single request. Asking once is the difference between one round trip and
     * four for a list of four languages.
     */
    private ?array $memo = null;

    private ?string $memoDefault = null;

    private ?bool $memoTableExists = null;

    /**
     * Active locales as code => ['name', 'english_name', 'dir'].
     */
    public function all(): array
    {
        return $this->memo ??= Cache::rememberForever(self::CACHE_KEY, function () {
            // Falls back to the config list when the table isn't there yet —
            // during the very first migrate, or if someone runs artisan against
            // an empty database. Without this the app couldn't boot far enough
            // to create the table it needs.
            if (! $this->tableExists()) {
                return config('himam.locales', []);
            }

            $locales = Locale::active()->orderBy('position')->orderBy('code')->get();

            if ($locales->isEmpty()) {
                return config('himam.locales', []);
            }

            return $locales->mapWithKeys(fn (Locale $locale) => [
                $locale->code => [
                    'name' => $locale->name,
                    'english_name' => $locale->english_name,
                    'dir' => $locale->direction,
                ],
            ])->all();
        });
    }

    /**
     * @return array<int, string>
     */
    public function codes(): array
    {
        return array_keys($this->all());
    }

    public function supports(?string $code): bool
    {
        return $code !== null && array_key_exists($code, $this->all());
    }

    /**
     * The locale everything falls back to when a request asks for one we don't
     * have, or when a field has no translation in the requested language.
     */
    public function default(): string
    {
        $fallback = config('app.fallback_locale', 'en');

        if (! $this->tableExists()) {
            return $fallback;
        }

        if ($this->memoDefault !== null) {
            return $this->memoDefault;
        }

        try {
            $default = Cache::rememberForever(self::CACHE_KEY.'.default', function () {
                return Locale::active()->where('is_default', true)->value('code');
            });
        } catch (Throwable) {
            return $fallback;
        }

        return $this->memoDefault = $default
            ?: ($this->supports($fallback) ? $fallback : ($this->codes()[0] ?? $fallback));
    }

    public function forget(): void
    {
        $this->memo = null;
        $this->memoDefault = null;

        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY.'.default');
    }

    private function tableExists(): bool
    {
        if ($this->memoTableExists !== null) {
            return $this->memoTableExists;
        }

        try {
            // Schema::hasTable() queries the catalogue every call, and this was
            // being asked twice a request to answer a question whose answer
            // cannot change inside one.
            return $this->memoTableExists = Schema::hasTable('locales');
        } catch (Throwable) {
            // No database yet — during an initial container boot, for instance.
            return $this->memoTableExists = false;
        }
    }
}
