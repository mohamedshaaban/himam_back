<?php

namespace Database\Seeders;

use App\Models\Locale;
use Illuminate\Database\Seeder;

/**
 * The languages the platform starts with. Administrators add, reorder, enable
 * and disable them from the dashboard afterwards — this is only the initial set.
 */
class LocaleSeeder extends Seeder
{
    public function run(): void
    {
        $locales = [
            ['code' => 'ar', 'name' => 'العربية', 'english_name' => 'Arabic', 'direction' => 'rtl', 'is_default' => true],
            ['code' => 'en', 'name' => 'English', 'english_name' => 'English', 'direction' => 'ltr'],
            ['code' => 'fr', 'name' => 'Français', 'english_name' => 'French', 'direction' => 'ltr'],
            ['code' => 'ur', 'name' => 'اردو', 'english_name' => 'Urdu', 'direction' => 'rtl'],
        ];

        foreach ($locales as $position => $locale) {
            Locale::updateOrCreate(
                ['code' => $locale['code']],
                $locale + ['position' => $position, 'is_active' => true],
            );
        }
    }
}
