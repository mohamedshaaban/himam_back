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
    'accepted' => ':attribute قبول کرنا ضروری ہے۔',
    'array' => ':attribute ایک فہرست ہونی چاہیے۔',
    'boolean' => ':attribute درست یا غلط ہونا چاہیے۔',
    'confirmed' => ':attribute کی تصدیق مطابقت نہیں رکھتی۔',
    'date' => ':attribute درست تاریخ ہونی چاہیے۔',
    'email' => ':attribute درست ای میل ہونا چاہیے۔',
    'exists' => 'منتخب کردہ :attribute موجود نہیں۔',
    'image' => ':attribute تصویر ہونی چاہیے۔',
    'in' => 'منتخب کردہ :attribute درست نہیں۔',
    'integer' => ':attribute عدد ہونا چاہیے۔',
    'mimes' => ':attribute ان اقسام کی فائل ہونی چاہیے: :values۔',
    'numeric' => ':attribute عدد ہونا چاہیے۔',
    'regex' => ':attribute کی صورت درست نہیں۔',
    'required' => ':attribute ضروری ہے۔',
    'string' => ':attribute متن ہونا چاہیے۔',
    'unique' => ':attribute پہلے سے استعمال میں ہے۔',
    'url' => ':attribute درست لنک ہونا چاہیے۔',

    'min' => [
        'numeric' => ':attribute کم از کم :min ہونا چاہیے۔',
        'string' => ':attribute کم از کم :min حروف کا ہونا چاہیے۔',
        'array' => ':attribute میں کم از کم :min عناصر ہونے چاہییں۔',
        'file' => ':attribute کم از کم :min کلوبائٹ ہونی چاہیے۔',
    ],

    'max' => [
        'numeric' => ':attribute :max سے زیادہ نہیں ہو سکتا۔',
        'string' => ':attribute :max حروف سے زیادہ نہیں ہو سکتا۔',
        'array' => ':attribute میں :max سے زیادہ عناصر نہیں ہو سکتے۔',
        'file' => ':attribute :max کلوبائٹ سے زیادہ نہیں ہو سکتی۔',
    ],

    'attributes' => [
        'name' => 'نام',
        'email' => 'ای میل',
        'password' => 'پاس ورڈ',
        'current_password' => 'موجودہ پاس ورڈ',
        'gender' => 'جنس',
        'age_band' => 'عمر',
        'education_level' => 'تعلیمی سطح',
        'phone' => 'فون نمبر',
        'whatsapp' => 'واٹس ایپ نمبر',
        'city' => 'شہر',
        'country' => 'ملک',
        'accepts_email' => 'ای میل کی اجازت',
        'locale' => 'زبان',
        'token' => 'ٹوکن',
        'title' => 'عنوان',
        'body' => 'متن',
        'slug' => 'شناخت',
        'question' => 'سوال',
        'answer' => 'جواب',
        'category' => 'زمرہ',
        'position' => 'ترتیب',
        'image' => 'تصویر',
        'cover' => 'سرورق',
        'points' => 'نکات',
        'level_id' => 'درجہ',
        'user_id' => 'صارف',
        'role' => 'کردار',
        'is_published' => 'اشاعت',
        'is_active' => 'فعالیت',
        'code' => 'کوڈ',
        'direction' => 'سمت',
        'website' => 'ویب سائٹ',
        'url' => 'لنک',
        'platform' => 'پلیٹ فارم',
    ],
];
