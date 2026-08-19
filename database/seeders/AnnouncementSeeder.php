<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        $announcements = [
            [
                'category' => 'program',
                'published_at' => now()->subHour(),
                'tag' => $this->tr('برنامج جديد', 'New programme', 'Nouveau programme', 'نیا پروگرام'),
                'title' => $this->tr(
                    'إطلاق برنامج القراءة المنظمة — الفصل الأول',
                    'Structured Reading programme opens — first term',
                    'Lancement du programme de lecture structurée — premier trimestre',
                    'منظم مطالعہ پروگرام کا آغاز — پہلا سمسٹر',
                ),
                'body' => $this->tr(
                    'التسجيل مفتوح حتى نهاية الأسبوع.',
                    'Registration is open until the end of the week.',
                    'Les inscriptions sont ouvertes jusqu\'à la fin de la semaine.',
                    'اندراج ہفتے کے اختتام تک کھلا ہے۔',
                ),
            ],
            [
                'category' => 'exam',
                'published_at' => now()->subDay(),
                'tag' => $this->tr('اختبار', 'Exam', 'Examen', 'امتحان'),
                'title' => $this->tr(
                    'موعد اختبار المستوى الثاني',
                    'Second level exam date',
                    'Date de l\'examen du deuxième niveau',
                    'دوسرے درجے کے امتحان کی تاریخ',
                ),
                'body' => $this->tr(
                    'الأحد 2026/08/16، الساعة 7 مساءً.',
                    'Sunday 16 August 2026, 7 p.m.',
                    'Dimanche 16 août 2026, 19 h.',
                    'اتوار 16 اگست 2026، شام 7 بجے۔',
                ),
            ],
            [
                'category' => 'results',
                'published_at' => now()->subDays(2),
                'tag' => $this->tr('نتائج', 'Results', 'Résultats', 'نتائج'),
                'title' => $this->tr(
                    'صدور نتائج اختبار المستوى الأول',
                    'First level results are out',
                    'Les résultats du premier niveau sont publiés',
                    'پہلے درجے کے نتائج جاری',
                ),
                'body' => $this->tr(
                    'يمكنك استعراض النتيجة من صفحة الشهادات.',
                    'You can view your result on the certificates page.',
                    'Vous pouvez consulter votre résultat sur la page des certificats.',
                    'آپ اپنا نتیجہ اسناد کے صفحے پر دیکھ سکتے ہیں۔',
                ),
            ],
            [
                'category' => 'honor',
                'published_at' => now()->subDays(3),
                'tag' => $this->tr('لوحة الشرف', 'Honour board', 'Tableau d\'honneur', 'لوحِ اعزاز'),
                'title' => $this->tr(
                    'تحديث لوحة الشرف الشهرية',
                    'Monthly honour board updated',
                    'Mise à jour du tableau d\'honneur mensuel',
                    'ماہانہ لوحِ اعزاز کی تجدید',
                ),
                'body' => $this->tr(
                    'اطّلع على ترتيبك الحالي على مستوى الجمعية.',
                    'Check where you now stand across the association.',
                    'Découvrez votre classement actuel au sein de l\'association.',
                    'انجمن کی سطح پر اپنی موجودہ درجہ بندی دیکھیں۔',
                ),
            ],
            [
                'category' => 'certificate',
                'published_at' => now()->subWeek(),
                'tag' => $this->tr('شهادة', 'Certificate', 'Certificat', 'سند'),
                'title' => $this->tr(
                    'شهادة برنامج همم جاهزة للتحميل',
                    'Your Himam certificate is ready to download',
                    'Votre certificat Himam est prêt à télécharger',
                    'آپ کی ہمم سند ڈاؤن لوڈ کے لیے تیار ہے',
                ),
                'body' => $this->tr(
                    'يمكنك تنزيلها والتحقق منها عبر رمز الاستجابة السريعة.',
                    'Download it and verify it with the QR code.',
                    'Téléchargez-le et vérifiez-le avec le code QR.',
                    'اسے ڈاؤن لوڈ کریں اور کیو آر کوڈ سے تصدیق کریں۔',
                ),
            ],
        ];

        foreach ($announcements as $announcement) {
            Announcement::create([
                ...$announcement,
                'image' => 'assets/banner.svg',
                'user_id' => null, // broadcast to everyone
            ]);
        }
    }
}
