<?php

namespace Database\Seeders;

use App\Models\ContactDetail;
use App\Models\Faq;
use App\Models\Page;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

/**
 * Starting text for the About, Privacy, Support and FAQ screens.
 *
 * Real copy rather than lorem ipsum, so the screens can be shown to the
 * programme's supervisors and edited from the dashboard rather than rewritten.
 */
class ContentPageSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        $this->pages();
        $this->faqs();
        $this->contact();
    }

    private function pages(): void
    {
        Page::updateOrCreate(['slug' => 'about'], [
            'is_published' => true,
            'title' => $this->tr('حول التطبيق', 'About Himam', 'À propos de Himam', 'ہمم کے بارے میں'),
            'body' => $this->tr(
                '<p>همم برنامج علمي نوعي من <strong>جمعية مرتقي العلمية</strong>، يهدف إلى تيسير القراءة المنظمة وتحويلها من رغبة متقطعة إلى عادة راسخة.</p>'
                .'<h3>كيف يعمل البرنامج</h3>'
                .'<p>ينتقي البرنامج أمهات الكتب والمصادر العلمية الموثوقة، ويقسّمها إلى أقسام قصيرة يتبع كل قسم منها اختبار وجيز يثبّت ما قُرئ. ومع كل قسم يُنجزه القارئ ترتفع نقاطه، وتُفتح له الأوسمة، وتصدر شهادته عند إتمام المستوى.</p>'
                .'<h3>رؤيتنا</h3>'
                .'<p>أن يصبح لكل طالب علم خطة قراءة واضحة يمشي عليها، وأثر يراه في نفسه قبل أن يراه في رصيد نقاطه.</p>',

                '<p>Himam is a scholarly programme from the <strong>Murtaqi Scientific Association</strong>, built to make structured reading achievable — to turn it from an intermittent wish into a settled habit.</p>'
                .'<h3>How the programme works</h3>'
                .'<p>It draws on the great books and trusted scholarly sources, divided into short sections, each followed by a brief quiz that settles what was read. Every section passed adds points, unlocks badges, and brings the level certificate closer.</p>'
                .'<h3>Our aim</h3>'
                .'<p>That every student of knowledge has a clear reading plan to follow, and sees the effect in themselves before they see it in a points total.</p>',

                '<p>Himam est un programme scientifique de l\'<strong>Association scientifique Murtaqi</strong>, conçu pour rendre la lecture structurée accessible et en faire une habitude durable plutôt qu\'un souhait intermittent.</p>'
                .'<h3>Comment fonctionne le programme</h3>'
                .'<p>Il puise dans les grands livres et les sources savantes de référence, découpés en sections courtes, chacune suivie d\'un bref quiz qui consolide la lecture. Chaque section validée ajoute des points, débloque des insignes et rapproche du certificat de niveau.</p>'
                .'<h3>Notre objectif</h3>'
                .'<p>Que chaque étudiant dispose d\'un plan de lecture clair, et en voie l\'effet en lui-même avant de le voir dans un total de points.</p>',

                '<p>ہمم <strong>مرتقی سائنٹیفک ایسوسی ایشن</strong> کا ایک منفرد علمی پروگرام ہے، جو منظم مطالعے کو آسان بنانے اور اسے وقتی خواہش کے بجائے پختہ عادت میں بدلنے کے لیے ہے۔</p>'
                .'<h3>پروگرام کیسے کام کرتا ہے</h3>'
                .'<p>یہ امہات الکتب اور معتبر علمی مصادر سے مواد لیتا ہے، جسے مختصر حصوں میں تقسیم کیا جاتا ہے اور ہر حصے کے بعد ایک مختصر امتحان ہوتا ہے۔ ہر مکمل حصہ نکات بڑھاتا ہے، اعزازات کھولتا ہے، اور درجے کی سند کو قریب لاتا ہے۔</p>'
                .'<h3>ہمارا مقصد</h3>'
                .'<p>ہر طالبِ علم کے پاس واضح منصوبۂ مطالعہ ہو، اور وہ اس کا اثر نکات کے مجموعے سے پہلے اپنے اندر دیکھے۔</p>',
            ),
        ]);

        Page::updateOrCreate(['slug' => 'privacy'], [
            'is_published' => true,
            'title' => $this->tr('سياسة الخصوصية', 'Privacy policy', 'Politique de confidentialité', 'رازداری کی پالیسی'),
            'body' => $this->tr(
                '<h3>البيانات التي نجمعها</h3>'
                .'<p>نجمع ما يلزم لتشغيل البرنامج فقط: الاسم، والبريد الإلكتروني، ورقم الجوال والمدينة إن أدخلتها، وسجل تقدّمك في القراءة والاختبارات.</p>'
                .'<h3>كيف نستخدمها</h3>'
                .'<p>تُستخدم بياناتك لعرض تقدّمك، وإصدار شهاداتك، وترتيبك في لوحة الشرف، وإرسال إشعارات البرنامج التي اخترت تفعيلها. لا نبيع بياناتك ولا نشاركها مع جهات إعلانية.</p>'
                .'<h3>ما يظهر للآخرين</h3>'
                .'<p>يظهر اسمك وصورتك ونقاطك في لوحة الشرف. أما بريدك ورقم جوالك فلا يظهران لغيرك.</p>'
                .'<h3>حقوقك</h3>'
                .'<p>يمكنك تعديل بياناتك من صفحة الحساب في أي وقت، أو طلب حذف حسابك بالتواصل معنا.</p>',

                '<h3>What we collect</h3>'
                .'<p>Only what the programme needs to run: your name and email, your phone number and city if you provide them, and your reading and quiz progress.</p>'
                .'<h3>How we use it</h3>'
                .'<p>To show your progress, issue your certificates, place you on the honour board, and send the programme notifications you have opted into. We do not sell your data or share it with advertisers.</p>'
                .'<h3>What others can see</h3>'
                .'<p>Your name, picture and points appear on the honour board. Your email and phone number are never shown to other readers.</p>'
                .'<h3>Your rights</h3>'
                .'<p>You can change your details from the account screen at any time, or ask us to delete your account by getting in touch.</p>',

                '<h3>Ce que nous collectons</h3>'
                .'<p>Uniquement ce qui est nécessaire : nom et e-mail, téléphone et ville si vous les fournissez, et votre progression de lecture et de quiz.</p>'
                .'<h3>Comment nous l\'utilisons</h3>'
                .'<p>Pour afficher votre progression, émettre vos certificats, vous classer au tableau d\'honneur et envoyer les notifications que vous avez acceptées. Nous ne vendons ni ne partageons vos données à des fins publicitaires.</p>'
                .'<h3>Ce que voient les autres</h3>'
                .'<p>Votre nom, votre image et vos points apparaissent au tableau d\'honneur. Votre e-mail et votre téléphone ne sont jamais affichés.</p>'
                .'<h3>Vos droits</h3>'
                .'<p>Vous pouvez modifier vos informations depuis la page Compte, ou demander la suppression de votre compte en nous contactant.</p>',

                '<h3>ہم کیا جمع کرتے ہیں</h3>'
                .'<p>صرف وہی جو پروگرام چلانے کے لیے ضروری ہے: نام اور ای میل، فون اور شہر اگر آپ دیں، اور آپ کے مطالعے و امتحانات کی پیش رفت۔</p>'
                .'<h3>ہم اسے کیسے استعمال کرتے ہیں</h3>'
                .'<p>آپ کی پیش رفت دکھانے، اسناد جاری کرنے، لوحِ اعزاز میں درجہ بندی کرنے، اور آپ کی منتخب کردہ اطلاعات بھیجنے کے لیے۔ ہم آپ کا ڈیٹا فروخت یا اشتہاری اداروں سے شیئر نہیں کرتے۔</p>'
                .'<h3>دوسروں کو کیا نظر آتا ہے</h3>'
                .'<p>آپ کا نام، تصویر اور نکات لوحِ اعزاز پر نظر آتے ہیں۔ ای میل اور فون نمبر کبھی ظاہر نہیں ہوتے۔</p>'
                .'<h3>آپ کے حقوق</h3>'
                .'<p>آپ اکاؤنٹ کے صفحے سے کسی بھی وقت اپنی تفصیلات بدل سکتے ہیں، یا ہم سے رابطہ کر کے اکاؤنٹ حذف کرا سکتے ہیں۔</p>',
            ),
        ]);
    }

    private function faqs(): void
    {
        $items = [
            [
                'q' => ['كيف أبدأ في البرنامج؟', 'How do I start?', 'Comment commencer ?', 'میں کیسے شروع کروں؟'],
                'a' => [
                    'اختر كتاباً من صفحة الكتب، ثم ابدأ بالقسم الأول: اقرأه ثم أجب عن اختباره القصير. بمجرد اجتيازك للاختبار تُضاف نقاط القسم إلى رصيدك.',
                    'Pick a book from the Books page and start with its first section: read it, then answer the short quiz. Passing the quiz credits the section\'s points to your balance.',
                    'Choisissez un livre puis commencez par sa première section : lisez-la, puis répondez au court quiz. La réussite du quiz ajoute les points de la section à votre solde.',
                    'کتابوں کے صفحے سے کتاب منتخب کریں اور پہلے حصے سے آغاز کریں: پڑھیں، پھر مختصر امتحان دیں۔ کامیابی پر حصے کے نکات آپ کے کھاتے میں شامل ہو جاتے ہیں۔',
                ],
            ],
            [
                'q' => ['كم درجة يلزم لاجتياز الاختبار؟', 'What score do I need to pass?', 'Quel score faut-il pour réussir ?', 'کامیابی کے لیے کتنے نمبر چاہییں؟'],
                'a' => [
                    'يلزم الإجابة الصحيحة عن ٦٠٪ من أسئلة القسم على الأقل. وإن لم تجتز الاختبار فبإمكانك إعادة المحاولة دون حد.',
                    'At least 60% of the section\'s questions. If you do not pass, you can retry as many times as you like.',
                    'Au moins 60 % des questions de la section. En cas d\'échec, vous pouvez réessayer autant de fois que nécessaire.',
                    'حصے کے کم از کم ۶۰٪ سوالات درست ہونے چاہییں۔ ناکامی کی صورت میں آپ جتنی بار چاہیں دوبارہ کوشش کر سکتے ہیں۔',
                ],
            ],
            [
                'q' => ['هل تُحتسب النقاط عند إعادة الاختبار؟', 'Do retries earn points again?', 'Une nouvelle tentative rapporte-t-elle des points ?', 'دوبارہ کوشش پر نکات ملتے ہیں؟'],
                'a' => [
                    'تُحتسب نقاط القسم مرة واحدة عند أول اجتياز. وإعادة المحاولة بعد النجاح متاحة للمراجعة، لكنها لا تضيف نقاطاً جديدة.',
                    'A section\'s points are credited once, on your first pass. Retaking a quiz afterwards is free and useful for revision, but earns nothing further.',
                    'Les points d\'une section sont crédités une seule fois, à la première réussite. Refaire le quiz ensuite est utile pour réviser mais ne rapporte plus de points.',
                    'حصے کے نکات صرف پہلی کامیابی پر ملتے ہیں۔ بعد میں دوبارہ امتحان دینا مراجعے کے لیے مفید ہے، مگر مزید نکات نہیں ملتے۔',
                ],
            ],
            [
                'q' => ['متى أحصل على الشهادة؟', 'When do I get a certificate?', 'Quand reçois-je un certificat ?', 'سند کب ملتی ہے؟'],
                'a' => [
                    'تصدر شهادة المستوى تلقائياً بمجرد اجتيازك جميع أقسام كتب ذلك المستوى، وتجدها في صفحة الشهادات برقم موثّق ورمز تحقق.',
                    'A level certificate is issued automatically once you have passed every section of every book in that level. You will find it on the Certificates page with a serial number and a verification code.',
                    'Le certificat de niveau est émis automatiquement dès que vous avez validé toutes les sections de tous les livres du niveau. Il apparaît sur la page Certificats avec un numéro et un code de vérification.',
                    'درجے کی سند خودکار طور پر اس وقت جاری ہوتی ہے جب آپ اس درجے کی تمام کتابوں کے سب حصے مکمل کر لیں۔ یہ اسناد کے صفحے پر سند نمبر اور تصدیقی کوڈ کے ساتھ ملے گی۔',
                ],
            ],
            [
                'q' => ['كيف يُحتسب ترتيب لوحة الشرف؟', 'How is the honour board ranked?', 'Comment le tableau d\'honneur est-il classé ?', 'لوحِ اعزاز کی درجہ بندی کیسے ہوتی ہے؟'],
                'a' => [
                    'يُرتَّب القرّاء بحسب مجموع النقاط. ويمكن عرض الترتيب على مستوى الشهر أو السنة أو منذ البداية، وتُحتسب في الشهري والسنوي النقاط المكتسبة داخل تلك المدة فقط.',
                    'Readers are ranked by points. The board can be viewed for this month, this year, or all time — and the monthly and yearly views count only the points earned inside that window.',
                    'Les lecteurs sont classés par points. Le tableau peut être consulté pour le mois, l\'année ou depuis le début ; les vues mensuelle et annuelle ne comptent que les points gagnés sur la période.',
                    'قارئین کی درجہ بندی نکات کے مطابق ہوتی ہے۔ بورڈ ماہانہ، سالانہ یا مجموعی طور پر دیکھا جا سکتا ہے، اور ماہانہ و سالانہ میں صرف اسی مدت کے نکات شمار ہوتے ہیں۔',
                ],
            ],
            [
                'q' => ['هل يمكنني تغيير لغة التطبيق؟', 'Can I change the app language?', 'Puis-je changer la langue ?', 'کیا میں ایپ کی زبان بدل سکتا ہوں؟'],
                'a' => [
                    'نعم، من أيقونة اللغة في الأعلى أو من صفحة الحساب. ويُحفظ اختيارك مع حسابك فيتبعك على أي جهاز تدخل منه.',
                    'Yes — from the language icon at the top, or from the Account page. Your choice is saved with your account, so it follows you to any device.',
                    'Oui, depuis l\'icône de langue en haut ou depuis la page Compte. Votre choix est enregistré avec votre compte et vous suit sur tous vos appareils.',
                    'جی ہاں، اوپر زبان کے آئیکن سے یا اکاؤنٹ کے صفحے سے۔ آپ کا انتخاب اکاؤنٹ کے ساتھ محفوظ ہوتا ہے، اس لیے ہر ڈیوائس پر آپ کے ساتھ رہتا ہے۔',
                ],
            ],
        ];

        foreach ($items as $position => $item) {
            Faq::updateOrCreate(
                ['position' => $position],
                [
                    'question' => $this->tr(...$item['q']),
                    'answer' => $this->tr(...$item['a']),
                    'is_active' => true,
                ],
            );
        }
    }

    private function contact(): void
    {
        $contact = ContactDetail::current();

        $contact->update([
            'email' => 'support@himam.test',
            'phone' => '+966 11 000 0000',
            'whatsapp' => '+966 55 000 0000',
            'website' => 'https://murtaqi.example.com',
            'address' => $this->tr(
                'الرياض، المملكة العربية السعودية',
                'Riyadh, Saudi Arabia',
                'Riyad, Arabie saoudite',
                'ریاض، سعودی عرب',
            ),
            'working_hours' => $this->tr(
                'من الأحد إلى الخميس، ٩ صباحاً حتى ٥ مساءً',
                'Sunday to Thursday, 9am to 5pm',
                'Du dimanche au jeudi, 9h à 17h',
                'اتوار تا جمعرات، صبح ۹ سے شام ۵ بجے تک',
            ),
            'note' => $this->tr(
                'لأي استفسار عن البرنامج أو مشكلة في حسابك، تواصل معنا وسنرد خلال يوم عمل بإذن الله.',
                'For any question about the programme or trouble with your account, get in touch and we will reply within one working day.',
                'Pour toute question sur le programme ou un problème de compte, écrivez-nous : nous répondons sous un jour ouvré.',
                'پروگرام سے متعلق کسی سوال یا اکاؤنٹ کے مسئلے کے لیے رابطہ کریں، ہم ایک کاروباری دن میں جواب دیں گے۔',
            ),
            'social' => [
                ['platform' => 'x', 'url' => 'https://x.com/'],
                ['platform' => 'youtube', 'url' => 'https://youtube.com/'],
                ['platform' => 'telegram', 'url' => 'https://t.me/'],
            ],
        ]);
    }
}
