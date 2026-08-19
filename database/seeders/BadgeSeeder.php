<?php

namespace Database\Seeders;

use App\Models\Badge;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        $badges = [
            [
                'name' => $this->tr('المثابر', 'The Persistent', 'Le persévérant', 'ثابت قدم'),
                'description' => $this->tr(
                    'أنهيت أول قسم من أقسام البرنامج.',
                    'Passed your first section of the programme.',
                    'Vous avez validé votre première section du programme.',
                    'آپ نے پروگرام کا پہلا حصہ مکمل کر لیا۔',
                ),
                'criteria_type' => 'sections_passed',
                'criteria_value' => 1,
            ],
            [
                'name' => $this->tr('القارئ المنتظم', 'The Regular Reader', 'Le lecteur assidu', 'باقاعدہ قاری'),
                'description' => $this->tr(
                    'أنهيت خمسة أقسام بنجاح.',
                    'Passed five sections.',
                    'Cinq sections validées.',
                    'پانچ حصے کامیابی سے مکمل کیے۔',
                ),
                'criteria_type' => 'sections_passed',
                'criteria_value' => 5,
            ],
            [
                'name' => $this->tr('متمم الكتاب', 'Book Finisher', 'Livre achevé', 'کتاب مکمل کرنے والا'),
                'description' => $this->tr(
                    'أتممت كتاباً كاملاً بجميع أقسامه.',
                    'Completed every section of one book.',
                    'Toutes les sections d\'un livre terminées.',
                    'ایک کتاب کے تمام حصے مکمل کیے۔',
                ),
                'criteria_type' => 'books_completed',
                'criteria_value' => 1,
            ],
            [
                'name' => $this->tr('قارئ المستوى', 'Level Reader', 'Lecteur de niveau', 'درجے کا قاری'),
                'description' => $this->tr(
                    'أتممت كتابين كاملين.',
                    'Completed two whole books.',
                    'Deux livres entiers terminés.',
                    'دو مکمل کتابیں ختم کیں۔',
                ),
                'criteria_type' => 'books_completed',
                'criteria_value' => 2,
            ],
            [
                'name' => $this->tr('جامع النقاط', 'Point Collector', 'Collectionneur de points', 'نکات جمع کرنے والا'),
                'description' => $this->tr(
                    'بلغت ألف نقطة في البرنامج.',
                    'Reached one thousand points.',
                    'Mille points atteints.',
                    'ایک ہزار نکات تک پہنچے۔',
                ),
                'criteria_type' => 'points',
                'criteria_value' => 1000,
            ],
            [
                'name' => $this->tr('المجتهد', 'The Diligent', 'L\'assidu', 'محنتی'),
                'description' => $this->tr(
                    'بلغت خمسة آلاف نقطة.',
                    'Reached five thousand points.',
                    'Cinq mille points atteints.',
                    'پانچ ہزار نکات تک پہنچے۔',
                ),
                'criteria_type' => 'points',
                'criteria_value' => 5000,
            ],
            [
                'name' => $this->tr('صاحب الهمة', 'The Resolute', 'Le résolu', 'صاحبِ ہمت'),
                'description' => $this->tr(
                    'أتممت جميع كتب البرنامج.',
                    'Completed every book in the programme.',
                    'Tous les livres du programme terminés.',
                    'پروگرام کی تمام کتابیں مکمل کیں۔',
                ),
                'criteria_type' => 'books_completed',
                'criteria_value' => 6,
            ],
            [
                'name' => $this->tr('وسام التميز', 'Badge of Distinction', 'Insigne d\'excellence', 'اعزازِ امتیاز'),
                'description' => $this->tr(
                    'وسام تمنحه إدارة البرنامج تقديراً للتميز.',
                    'Awarded by the programme administration in recognition of distinction.',
                    'Décerné par l\'administration du programme pour l\'excellence.',
                    'انتظامیہ کی جانب سے امتیاز کے اعتراف میں دیا جانے والا اعزاز۔',
                ),
                'criteria_type' => 'manual',
                'criteria_value' => 0,
            ],
        ];

        foreach ($badges as $position => $badge) {
            Badge::create([
                ...$badge,
                'image' => 'assets/badge.png',
                'position' => $position,
                'is_active' => true,
            ]);
        }
    }
}
