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
| Each sentence is built around a fixed masculine noun (حقل, طول, حجم) rather
| than around :attribute itself, because the field names differ in gender —
| الاسم is masculine, كلمة المرور feminine — and a verb agreeing with one
| would be wrong for the other.
|
| `attributes` matters as much as the messages: without it a reader sees the
| database column ("age_band") inside an otherwise Arabic sentence.
|
*/

return [
    'accepted' => 'يجب قبول حقل :attribute.',
    'array' => 'يجب أن يكون حقل :attribute قائمة.',
    'boolean' => 'يجب أن يكون حقل :attribute صحيحاً أو خطأ.',
    'confirmed' => 'تأكيد حقل :attribute غير مطابق.',
    'date' => 'يجب أن يكون حقل :attribute تاريخاً صحيحاً.',
    'email' => 'يجب أن يكون حقل :attribute بريداً إلكترونياً صحيحاً.',
    'exists' => 'قيمة حقل :attribute غير موجودة.',
    'image' => 'يجب أن يكون حقل :attribute صورة.',
    'in' => 'قيمة حقل :attribute غير صحيحة.',
    'integer' => 'يجب أن يكون حقل :attribute عدداً صحيحاً.',
    'mimes' => 'يجب أن يكون حقل :attribute ملفاً من نوع: :values.',
    'numeric' => 'يجب أن يكون حقل :attribute رقماً.',
    'regex' => 'صيغة حقل :attribute غير صحيحة.',
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'يجب أن يكون حقل :attribute نصاً.',
    'unique' => 'قيمة حقل :attribute مستخدمة من قبل.',
    'url' => 'يجب أن يكون حقل :attribute رابطاً صحيحاً.',

    'min' => [
        'numeric' => 'يجب ألا تقل قيمة حقل :attribute عن :min.',
        'string' => 'يجب ألا يقل طول حقل :attribute عن :min حروف.',
        'array' => 'يجب ألا تقل عناصر حقل :attribute عن :min.',
        'file' => 'يجب ألا يقل حجم حقل :attribute عن :min كيلوبايت.',
    ],

    'max' => [
        'numeric' => 'يجب ألا تزيد قيمة حقل :attribute عن :max.',
        'string' => 'يجب ألا يزيد طول حقل :attribute عن :max حرفاً.',
        'array' => 'يجب ألا تزيد عناصر حقل :attribute عن :max.',
        'file' => 'يجب ألا يزيد حجم حقل :attribute عن :max كيلوبايت.',
    ],

    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'gender' => 'الجنس',
        'age_band' => 'العمر',
        'education_level' => 'المستوى التعليمي',
        'phone' => 'رقم الجوال',
        'whatsapp' => 'رقم الواتساب',
        'city' => 'المدينة',
        'country' => 'الدولة',
        'accepts_email' => 'الموافقة على البريد',
        'locale' => 'اللغة',
        'token' => 'الرمز',
        'title' => 'العنوان',
        'body' => 'المحتوى',
        'slug' => 'المعرّف',
        'question' => 'السؤال',
        'answer' => 'الإجابة',
        'category' => 'التصنيف',
        'position' => 'الترتيب',
        'image' => 'الصورة',
        'cover' => 'الغلاف',
        'points' => 'النقاط',
        'level_id' => 'المستوى',
        'user_id' => 'المستخدم',
        'role' => 'الصلاحية',
        'is_published' => 'النشر',
        'is_active' => 'التفعيل',
        'code' => 'الرمز',
        'direction' => 'الاتجاه',
        'website' => 'الموقع الإلكتروني',
        'url' => 'الرابط',
        'platform' => 'المنصة',
    ],
];
