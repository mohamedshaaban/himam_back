<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supported locales
    |--------------------------------------------------------------------------
    |
    | The single source of truth for which languages Himam speaks. Adding a
    | language is a one-line edit here plus a matching locale file in the
    | frontend (src/i18n/locales). Everything else — the SetLocale middleware,
    | the admin translation editors, the API validation rules — reads this list.
    |
    */

    'locales' => [
        'ar' => ['name' => 'العربية', 'english_name' => 'Arabic', 'dir' => 'rtl'],
        'en' => ['name' => 'English', 'english_name' => 'English', 'dir' => 'ltr'],
        'fr' => ['name' => 'Français', 'english_name' => 'French', 'dir' => 'ltr'],
        'ur' => ['name' => 'اردو', 'english_name' => 'Urdu', 'dir' => 'rtl'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Program rules
    |--------------------------------------------------------------------------
    |
    | A section quiz is passed at `quiz_pass_ratio` of its questions; passing a
    | section for the first time credits `section_points`. A level certificate
    | is issued once every section of every book in that level is passed.
    |
    */

    'quiz_pass_ratio' => 0.6,

    'section_points' => 150,

];
