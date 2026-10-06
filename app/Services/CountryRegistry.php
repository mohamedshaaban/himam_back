<?php

namespace App\Services;

use Collator;
use Illuminate\Support\Facades\Cache;
use Locale;
use Throwable;

/**
 * The country list the registration form offers.
 *
 * Only ISO 3166-1 codes and dialling codes are stored here. The names come from
 * PHP's own locale data, so the list reads correctly in Arabic, English, French
 * and Urdu — and in any language added later — without four hand-maintained
 * translations of 118 country names drifting apart.
 *
 * Sorting uses the requested locale too: Arabic names order by the Arabic
 * alphabet, not by their underlying codes.
 */
class CountryRegistry
{
    private const CACHE_PREFIX = 'himam.countries.';

    /**
     * ISO 3166-1 alpha-2 => international dialling code.
     *
     * The dialling code rides along because any screen asking for a phone
     * number wants it, and deriving it from the country the reader already
     * picked is kinder than asking them to remember it.
     */
    private const COUNTRIES = [
        'AF' => '+93',  'AL' => '+355', 'DZ' => '+213', 'AR' => '+54',  'AM' => '+374',
        'AU' => '+61',  'AT' => '+43',  'AZ' => '+994', 'BH' => '+973', 'BD' => '+880',
        'BY' => '+375', 'BE' => '+32',  'BJ' => '+229', 'BA' => '+387', 'BR' => '+55',
        'BN' => '+673', 'BG' => '+359', 'BF' => '+226', 'BI' => '+257', 'KH' => '+855',
        'CM' => '+237', 'CA' => '+1',   'TD' => '+235', 'CN' => '+86',  'KM' => '+269',
        'CI' => '+225', 'HR' => '+385', 'CY' => '+357', 'CZ' => '+420', 'DK' => '+45',
        'DJ' => '+253', 'EG' => '+20',  'ER' => '+291', 'EE' => '+372', 'ET' => '+251',
        'FI' => '+358', 'FR' => '+33',  'GM' => '+220', 'GE' => '+995', 'DE' => '+49',
        'GH' => '+233', 'GR' => '+30',  'GN' => '+224', 'GW' => '+245', 'HU' => '+36',
        'IN' => '+91',  'ID' => '+62',  'IR' => '+98',  'IQ' => '+964', 'IE' => '+353',
        'IT' => '+39',  'JP' => '+81',  'JO' => '+962', 'KZ' => '+7',   'KE' => '+254',
        'KW' => '+965', 'KG' => '+996', 'LB' => '+961', 'LR' => '+231', 'LY' => '+218',
        'MY' => '+60',  'MV' => '+960', 'ML' => '+223', 'MR' => '+222', 'MA' => '+212',
        'MZ' => '+258', 'MM' => '+95',  'NL' => '+31',  'NZ' => '+64',  'NE' => '+227',
        'NG' => '+234', 'NO' => '+47',  'OM' => '+968', 'PK' => '+92',  'PS' => '+970',
        'PH' => '+63',  'PL' => '+48',  'PT' => '+351', 'QA' => '+974', 'RO' => '+40',
        'RU' => '+7',   'RW' => '+250', 'SA' => '+966', 'SN' => '+221', 'RS' => '+381',
        'SL' => '+232', 'SG' => '+65',  'SK' => '+421', 'SI' => '+386', 'SO' => '+252',
        'ZA' => '+27',  'KR' => '+82',  'ES' => '+34',  'LK' => '+94',  'SD' => '+249',
        'SE' => '+46',  'CH' => '+41',  'SY' => '+963', 'TJ' => '+992', 'TZ' => '+255',
        'TH' => '+66',  'TG' => '+228', 'TN' => '+216', 'TR' => '+90',  'TM' => '+993',
        'UG' => '+256', 'UA' => '+380', 'AE' => '+971', 'GB' => '+44',  'US' => '+1',
        'UZ' => '+998', 'YE' => '+967', 'ZM' => '+260', 'ZW' => '+263',
    ];

    /**
     * @return array<int, array{code: string, name: string, dial_code: string}>
     */
    public function all(string $locale): array
    {
        return Cache::rememberForever(self::CACHE_PREFIX.$locale, function () use ($locale) {
            $countries = [];

            foreach (self::COUNTRIES as $code => $dial) {
                $countries[] = [
                    'code' => $code,
                    'name' => $this->name($code, $locale),
                    'dial_code' => $dial,
                ];
            }

            $this->sortByName($countries, $locale);

            return $countries;
        });
    }

    public function supports(?string $code): bool
    {
        return $code !== null && array_key_exists(strtoupper($code), self::COUNTRIES);
    }

    /**
     * @return array<int, string>
     */
    public function codes(): array
    {
        return array_keys(self::COUNTRIES);
    }

    public function forget(): void
    {
        foreach (app(LocaleRegistry::class)->codes() as $locale) {
            Cache::forget(self::CACHE_PREFIX.$locale);
        }
    }

    /**
     * Falls back to the code itself rather than to an empty label — a reader
     * seeing "KW" can still pick their country; a reader seeing nothing cannot.
     */
    private function name(string $code, string $locale): string
    {
        try {
            return Locale::getDisplayRegion('-'.$code, $locale) ?: $code;
        } catch (Throwable) {
            return $code;
        }
    }

    private function sortByName(array &$countries, string $locale): void
    {
        try {
            $collator = new Collator($locale);
            usort($countries, fn ($a, $b) => $collator->compare($a['name'], $b['name']));
        } catch (Throwable) {
            // Without ICU collation, alphabetical by bytes still beats the
            // arbitrary order the codes happen to be written in.
            usort($countries, fn ($a, $b) => strcmp($a['name'], $b['name']));
        }
    }
}
