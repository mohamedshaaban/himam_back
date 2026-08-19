<?php

namespace Database\Seeders;

use App\Models\Slide;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

class SlideSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        $video = 'https://youtu.be/6W8Xg6-VUAc?list=PLGeu80ApFDrljQny9KhmUBJrDZhjM_NV-';

        $slides = [
            'home' => [
                [
                    'image' => 'https://img.youtube.com/vi/6W8Xg6-VUAc/maxresdefault.jpg',
                    'href' => $video,
                    'caption' => $this->tr(
                        'فيديو تعريفي من قائمة تشغيل البرنامج',
                        'Introductory video from the programme playlist',
                        'Vidéo de présentation de la playlist du programme',
                        'پروگرام کی پلے لسٹ سے تعارفی ویڈیو',
                    ),
                ],
                [
                    'image' => 'assets/banner.svg',
                    'caption' => $this->tr('إعلان البرنامج — الفصل الأول', 'Programme announcement — first term', 'Annonce du programme — premier trimestre', 'پروگرام کا اعلان — پہلا سمسٹر'),
                ],
                [
                    'image' => 'assets/certificate.png',
                    'caption' => $this->tr('نموذج شهادة برنامج همم', 'Sample Himam certificate', 'Exemple de certificat Himam', 'ہمم سند کا نمونہ'),
                ],
            ],
            'books' => [
                [
                    'image' => 'assets/banner.svg',
                    'caption' => $this->tr('كتب المستوى الأول', 'First level books', 'Livres du premier niveau', 'پہلے درجے کی کتابیں'),
                ],
                [
                    'image' => 'assets/certificate.png',
                    'caption' => $this->tr('شهادة إتمام المستوى', 'Level completion certificate', 'Certificat de fin de niveau', 'درجہ مکمل کرنے کی سند'),
                ],
                [
                    'image' => 'assets/badge.png',
                    'caption' => $this->tr('أوسمة القراءة', 'Reading badges', 'Insignes de lecture', 'مطالعے کے اعزازات'),
                ],
            ],
            'badges' => [
                [
                    'image' => 'assets/badge.png',
                    'caption' => $this->tr('وسام المثابر', 'The Persistent badge', 'L\'insigne du persévérant', 'ثابت قدم کا اعزاز'),
                ],
                [
                    'image' => 'assets/banner.svg',
                    'caption' => $this->tr('أوسمة قادمة', 'Badges still to come', 'Insignes à venir', 'آنے والے اعزازات'),
                ],
            ],
            'certificates' => [
                [
                    'image' => 'assets/certificate.png',
                    'caption' => $this->tr('شهادة المستوى الأول', 'First level certificate', 'Certificat du premier niveau', 'پہلے درجے کی سند'),
                ],
                [
                    'image' => 'assets/qr.png',
                    'caption' => $this->tr('رمز التحقق من الشهادة', 'Certificate verification code', 'Code de vérification du certificat', 'سند کی تصدیق کا کوڈ'),
                ],
            ],
            'honor' => [
                [
                    'image' => 'assets/banner.svg',
                    'caption' => $this->tr('تكريم المتصدرين', 'Honouring the leaders', 'À l\'honneur : les premiers', 'سرفہرست قارئین کا اعزاز'),
                ],
                [
                    'image' => 'assets/badge.png',
                    'caption' => $this->tr('أوسمة لوحة الشرف', 'Honour board badges', 'Insignes du tableau d\'honneur', 'لوحِ اعزاز کے تمغے'),
                ],
            ],
            'notifications' => [
                [
                    'image' => 'assets/banner.svg',
                    'caption' => $this->tr('إعلان مصوّر مرفق بالإشعار', 'Illustrated announcement', 'Annonce illustrée', 'تصویری اعلان'),
                ],
            ],
            'account' => [
                [
                    'image' => 'assets/badge.png',
                    'caption' => $this->tr('آخر وسام حصلت عليه', 'Your latest badge', 'Votre dernier insigne', 'آپ کا تازہ ترین اعزاز'),
                ],
                [
                    'image' => 'assets/certificate.png',
                    'caption' => $this->tr('آخر شهادة', 'Your latest certificate', 'Votre dernier certificat', 'آپ کی تازہ ترین سند'),
                ],
            ],
        ];

        foreach ($slides as $screen => $screenSlides) {
            foreach ($screenSlides as $position => $slide) {
                Slide::create([
                    ...$slide,
                    'screen' => $screen,
                    'position' => $position,
                    'is_active' => true,
                ]);
            }
        }
    }
}
