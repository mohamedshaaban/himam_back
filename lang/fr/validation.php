<?php

/*
|--------------------------------------------------------------------------
| Validation messages
|--------------------------------------------------------------------------
|
| Only the rules this API uses are translated. Laravel falls back to English
| for anything absent, so an untranslated rule degrades to a readable message
| rather than to the rule's raw name.
|
| `attributes` matters as much as the messages: without it a reader sees the
| database column ("age_band") inside an otherwise Arabic sentence.
|
*/

return [
    'accepted' => 'Le champ :attribute doit être accepté.',
    'array' => 'Le champ :attribute doit être une liste.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'date' => 'Le champ :attribute doit être une date valide.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'exists' => 'La valeur du champ :attribute est invalide.',
    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'La valeur du champ :attribute est invalide.',
    'integer' => 'Le champ :attribute doit être un entier.',
    'mimes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être une chaîne.',
    'unique' => 'Cette valeur du champ :attribute est déjà utilisée.',
    'url' => 'Le champ :attribute doit être une URL valide.',

    'min' => [
        'numeric' => 'Le champ :attribute doit être au minimum :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
        'file' => 'Le champ :attribute doit faire au moins :min kilo-octets.',
    ],

    'max' => [
        'numeric' => 'Le champ :attribute ne peut dépasser :max.',
        'string' => 'Le champ :attribute ne peut dépasser :max caractères.',
        'array' => 'Le champ :attribute ne peut contenir plus de :max éléments.',
        'file' => 'Le champ :attribute ne peut dépasser :max kilo-octets.',
    ],

    'attributes' => [
        'name' => 'nom',
        'email' => 'adresse e-mail',
        'password' => 'mot de passe',
        'current_password' => 'mot de passe actuel',
        'gender' => 'genre',
        'age_band' => 'âge',
        'education_level' => 'niveau d\'études',
        'phone' => 'téléphone',
        'whatsapp' => 'numéro WhatsApp',
        'city' => 'ville',
        'country' => 'pays',
        'accepts_email' => 'consentement e-mail',
        'locale' => 'langue',
        'token' => 'jeton',
        'title' => 'titre',
        'body' => 'contenu',
        'slug' => 'identifiant',
        'question' => 'question',
        'answer' => 'réponse',
        'category' => 'catégorie',
        'position' => 'ordre',
        'image' => 'image',
        'cover' => 'couverture',
        'points' => 'points',
        'level_id' => 'niveau',
        'user_id' => 'utilisateur',
        'role' => 'rôle',
        'is_published' => 'publication',
        'is_active' => 'activation',
        'code' => 'code',
        'direction' => 'direction',
        'website' => 'site web',
        'url' => 'lien',
        'platform' => 'plateforme',
    ],
];
