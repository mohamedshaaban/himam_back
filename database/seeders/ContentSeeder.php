<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\BookSection;
use App\Models\Level;
use Database\Seeders\Concerns\Translates;
use Illuminate\Database\Seeder;

/**
 * Seeds the reading programme: three levels, six books, three sections each,
 * and a three-question quiz per section — the catalogue the Himam design
 * mocks up, carried into all four supported languages.
 */
class ContentSeeder extends Seeder
{
    use Translates;

    public function run(): void
    {
        $levels = $this->seedLevels();

        foreach ($this->books() as $position => $book) {
            $created = Book::create([
                'level_id' => $levels[$book['level']]->id,
                'title' => $book['title'],
                'author' => $this->tr(
                    'إعداد إدارة البرنامج',
                    'Prepared by the programme administration',
                    'Préparé par l\'administration du programme',
                    'پروگرام انتظامیہ کی تیاری',
                ),
                'description' => $book['description'],
                'cover' => 'assets/banner.svg',
                'pages' => $book['pages'],
                'points' => $book['points'],
                'position' => $position,
                'is_published' => true,
            ]);

            $this->seedSections($created);
        }
    }

    /**
     * @return array<int, Level> keyed by the level number used in books()
     */
    private function seedLevels(): array
    {
        $definitions = [
            1 => [
                'name' => $this->tr('المستوى الأول', 'First Level', 'Premier niveau', 'پہلا درجہ'),
                'description' => $this->tr(
                    'مدخل إلى القراءة المنظمة وأدواتها الأساسية.',
                    'An introduction to structured reading and its basic tools.',
                    'Une introduction à la lecture structurée et à ses outils de base.',
                    'منظم مطالعے اور اس کے بنیادی اوزار کا تعارف۔',
                ),
            ],
            2 => [
                'name' => $this->tr('المستوى الثاني', 'Second Level', 'Deuxième niveau', 'دوسرا درجہ'),
                'description' => $this->tr(
                    'التوسع في أمهات الكتب وبناء الوعي المعرفي.',
                    'Moving into the great books and building cognitive awareness.',
                    'Aborder les grands livres et développer la conscience cognitive.',
                    'امہات الکتب کی طرف پیش قدمی اور معرفتی شعور کی تعمیر۔',
                ),
            ],
            3 => [
                'name' => $this->tr('المستوى الثالث', 'Third Level', 'Troisième niveau', 'تیسرا درجہ'),
                'description' => $this->tr(
                    'إتقان مهارات التلخيص وإدارة وقت القراءة.',
                    'Mastering summarising skills and managing reading time.',
                    'Maîtriser la synthèse et la gestion du temps de lecture.',
                    'تلخیص کی مہارت اور مطالعے کے وقت کے انتظام میں مہارت۔',
                ),
            ],
        ];

        $levels = [];

        foreach ($definitions as $number => $definition) {
            $levels[$number] = Level::create([
                ...$definition,
                'position' => $number,
                'is_active' => true,
            ]);
        }

        return $levels;
    }

    private function books(): array
    {
        return [
            [
                'level' => 1, 'pages' => 140, 'points' => 400,
                'title' => $this->tr(
                    'مقدمة في القراءة المنظمة',
                    'Introduction to Structured Reading',
                    'Introduction à la lecture structurée',
                    'منظم مطالعے کا تعارف',
                ),
                'description' => $this->tr(
                    'مدخل عملي يوضح معنى القراءة المنظمة وأدواتها، ويضع للقارئ خطة أسبوعية تبدأ من تحديد الهدف وتنتهي بمراجعة ما قرأ.',
                    'A practical introduction to what structured reading means and the tools it uses, setting out a weekly plan that begins with a goal and ends with a review.',
                    'Une introduction pratique à la lecture structurée et à ses outils, avec un plan hebdomadaire qui part d\'un objectif et se termine par une révision.',
                    'ایک عملی تعارف جو منظم مطالعے کے معنی اور اوزار واضح کرتا ہے، اور ہفتہ وار منصوبہ دیتا ہے جو ہدف سے شروع ہو کر مراجعے پر ختم ہوتا ہے۔',
                ),
            ],
            [
                'level' => 1, 'pages' => 165, 'points' => 400,
                'title' => $this->tr(
                    'أصول التفكير المنهجي',
                    'Foundations of Systematic Thinking',
                    'Les fondements de la pensée méthodique',
                    'منہجی تفکر کے اصول',
                ),
                'description' => $this->tr(
                    'يعرض قواعد التفكير المرتب وكيفية تطبيقها على ما يقرأ الإنسان، حتى يتحول النص إلى فهم لا إلى معلومات متفرقة.',
                    'Sets out the rules of ordered thinking and how to apply them to what you read, so a text becomes understanding rather than scattered facts.',
                    'Présente les règles d\'une pensée ordonnée et leur application à la lecture, afin qu\'un texte devienne compréhension et non information éparse.',
                    'مرتب تفکر کے قواعد اور مطالعے پر ان کا اطلاق بیان کرتا ہے، تاکہ متن بکھری معلومات کے بجائے فہم بن جائے۔',
                ),
            ],
            [
                'level' => 2, 'pages' => 210, 'points' => 500,
                'title' => $this->tr(
                    'أمهات الكتب: دليل القارئ',
                    'The Great Books: A Reader\'s Guide',
                    'Les grands livres : guide du lecteur',
                    'امہات الکتب: قاری کی رہنمائی',
                ),
                'description' => $this->tr(
                    'دليل يعرّف القارئ بالمصادر العلمية الموثوقة، ويبيّن كيف يقاربها ومن أين يبدأ فيها.',
                    'A guide to the trusted scholarly sources: how to approach them and where to begin.',
                    'Un guide des sources savantes de référence : comment les aborder et par où commencer.',
                    'معتبر علمی مصادر کا تعارف: ان تک کیسے پہنچا جائے اور آغاز کہاں سے ہو۔',
                ),
            ],
            [
                'level' => 2, 'pages' => 180, 'points' => 500,
                'title' => $this->tr('الوعي المعرفي', 'Cognitive Awareness', 'La conscience cognitive', 'معرفتی شعور'),
                'description' => $this->tr(
                    'يبني عند القارئ وعياً بما يقرأ: كيف يميّز الرأي من الدليل، والمحكم من المتشابه.',
                    'Builds an awareness of what you are reading: telling opinion from evidence, and the settled from the ambiguous.',
                    'Développe une conscience de sa lecture : distinguer l\'opinion de la preuve, l\'établi de l\'ambigu.',
                    'قاری میں شعور پیدا کرتا ہے: رائے کو دلیل سے، اور محکم کو متشابہ سے جدا کرنا۔',
                ),
            ],
            [
                'level' => 3, 'pages' => 120, 'points' => 600,
                'title' => $this->tr('مهارات التلخيص', 'Summarising Skills', 'Les techniques de synthèse', 'تلخیص کی مہارتیں'),
                'description' => $this->tr(
                    'يعلّم كيف يُختصر الكتاب في صفحات قليلة دون أن تضيع أفكاره الرئيسية.',
                    'Teaches how to reduce a book to a few pages without losing its main ideas.',
                    'Enseigne comment ramener un livre à quelques pages sans en perdre les idées maîtresses.',
                    'سکھاتا ہے کہ کتاب کو چند صفحات میں کیسے سمیٹا جائے بغیر اس کے بنیادی خیالات کھوئے۔',
                ),
            ],
            [
                'level' => 3, 'pages' => 135, 'points' => 600,
                'title' => $this->tr(
                    'إدارة وقت القراءة',
                    'Managing Reading Time',
                    'La gestion du temps de lecture',
                    'مطالعے کے وقت کا انتظام',
                ),
                'description' => $this->tr(
                    'يعالج أكثر ما يعيق القارئ: الوقت. ويضع جداول عملية تجعل القراءة عادة يومية ثابتة.',
                    'Tackles the reader\'s biggest obstacle — time — with practical schedules that make reading a settled daily habit.',
                    'Traite le principal obstacle du lecteur, le temps, avec des horaires pratiques qui font de la lecture une habitude quotidienne.',
                    'قاری کی سب سے بڑی رکاوٹ یعنی وقت پر بات کرتا ہے، اور ایسے عملی نظام الاوقات دیتا ہے جو مطالعے کو روزمرہ عادت بنا دیں۔',
                ),
            ],
        ];
    }

    private function seedSections(Book $book): void
    {
        $titles = [
            $this->tr('لماذا نقرأ بانتظام', 'Why We Read Regularly', 'Pourquoi lire régulièrement', 'ہم باقاعدگی سے کیوں پڑھتے ہیں'),
            $this->tr('بناء خطة القراءة', 'Building a Reading Plan', 'Construire un plan de lecture', 'مطالعے کا منصوبہ بنانا'),
            $this->tr('المراجعة والتلخيص', 'Review and Summary', 'Révision et synthèse', 'مراجعہ اور تلخیص'),
        ];

        foreach ($titles as $index => $title) {
            $section = $book->sections()->create([
                'title' => $title,
                'body' => $this->sectionBody(),
                'position' => $index + 1,
            ]);

            $this->seedQuestions($section, $index);
        }
    }

    /**
     * The four paragraphs the design's reading screen shows.
     */
    private function sectionBody(): array
    {
        return $this->tr(
            implode("\n\n", [
                'يبدأ هذا القسم بتحديد الغاية من القراءة: ما الذي يريد القارئ الوصول إليه بعد إتمام الكتاب، وكيف يُترجم ذلك إلى خطوات أسبوعية واضحة.',
                'ثم ينتقل إلى الأدوات العملية: تقسيم الكتاب إلى أقسام متساوية، وتحديد وقت ثابت للقراءة، وتدوين الملاحظات على هامش النص أو في بطاقة مستقلة.',
                'ويؤكد أن القراءة المنظمة ليست سرعة في الإنجاز، بل انتظام في المتابعة؛ فصفحات قليلة كل يوم أنفع من قراءة متقطعة تنقطع بعد أيام.',
                'وفي ختام القسم يُطلب من القارئ مراجعة ما قرأه بإيجاز، وكتابة ثلاث أفكار رئيسية بلغته الخاصة قبل الانتقال إلى الاختبار.',
            ]),
            implode("\n\n", [
                'This section opens by settling the purpose of reading: what the reader wants to arrive at once the book is finished, and how that translates into clear weekly steps.',
                'It then turns to the practical tools: dividing the book into equal sections, fixing a set time to read, and taking notes in the margin or on a separate card.',
                'It insists that structured reading is not speed of completion but regularity of follow-through; a few pages every day serve better than bursts of reading that stop after a week.',
                'The section closes by asking the reader to review briefly what they have read, and to write three main ideas in their own words before moving on to the quiz.',
            ]),
            implode("\n\n", [
                'Cette section commence par fixer le but de la lecture : ce que le lecteur veut atteindre une fois le livre terminé, et comment le traduire en étapes hebdomadaires claires.',
                'Elle passe ensuite aux outils pratiques : diviser le livre en sections égales, fixer une heure de lecture, et prendre des notes en marge ou sur une fiche séparée.',
                'Elle rappelle que la lecture structurée n\'est pas une course, mais une régularité dans le suivi ; quelques pages chaque jour valent mieux qu\'une lecture par à-coups qui s\'interrompt.',
                'La section se termine en demandant au lecteur de réviser brièvement sa lecture et d\'écrire trois idées principales avec ses propres mots avant de passer au quiz.',
            ]),
            implode("\n\n", [
                'یہ حصہ مطالعے کے مقصد کے تعین سے شروع ہوتا ہے: قاری کتاب مکمل کرنے کے بعد کہاں پہنچنا چاہتا ہے، اور یہ واضح ہفتہ وار اقدامات میں کیسے ڈھلتا ہے۔',
                'پھر عملی اوزار کی طرف آتا ہے: کتاب کو برابر حصوں میں تقسیم کرنا، مطالعے کا وقت مقرر کرنا، اور حاشیے یا الگ کارڈ پر نوٹ لکھنا۔',
                'یہ زور دیتا ہے کہ منظم مطالعہ رفتار نہیں بلکہ تسلسل کا نام ہے؛ روزانہ چند صفحات اس وقفے وقفے سے پڑھنے سے بہتر ہیں جو چند دن بعد رک جائے۔',
                'حصے کے اختتام پر قاری سے کہا جاتا ہے کہ وہ پڑھے ہوئے کا مختصر مراجعہ کرے اور امتحان سے پہلے تین بنیادی خیالات اپنے الفاظ میں لکھے۔',
            ]),
        );
    }

    /**
     * Three questions per section, varying by the section's position so the
     * quizzes track the section that precedes them.
     */
    private function seedQuestions(BookSection $section, int $sectionIndex): void
    {
        foreach ($this->questionBank()[$sectionIndex] as $position => $question) {
            $created = $section->questions()->create([
                'text' => $question['text'],
                'position' => $position,
            ]);

            foreach ($question['options'] as $optionPosition => $option) {
                $created->options()->create([
                    'text' => $option['text'],
                    'is_correct' => $option['correct'] ?? false,
                    'position' => $optionPosition,
                ]);
            }
        }
    }

    private function questionBank(): array
    {
        return [
            // ── Why we read regularly ────────────────────────────────────────
            0 => [
                [
                    'text' => $this->tr(
                        'ما الغاية الأولى التي يبدأ بها القسم؟',
                        'What does the section begin by settling?',
                        'Par quoi la section commence-t-elle ?',
                        'یہ حصہ کس چیز کے تعین سے شروع ہوتا ہے؟',
                    ),
                    'options' => [
                        ['correct' => true, 'text' => $this->tr('تحديد الغاية من القراءة', 'Settling the purpose of reading', 'Fixer le but de la lecture', 'مطالعے کے مقصد کا تعین')],
                        ['text' => $this->tr('حفظ فهرس الكتاب', 'Memorising the table of contents', 'Mémoriser la table des matières', 'کتاب کی فہرست یاد کرنا')],
                        ['text' => $this->tr('قراءة الخاتمة أولاً', 'Reading the conclusion first', 'Lire la conclusion en premier', 'پہلے خاتمہ پڑھنا')],
                    ],
                ],
                [
                    'text' => $this->tr(
                        'أي العبارات يوافق منهج القراءة المنظمة؟',
                        'Which statement matches the structured reading method?',
                        'Quelle affirmation correspond à la lecture structurée ?',
                        'کون سا بیان منظم مطالعے کے منہج سے مطابقت رکھتا ہے؟',
                    ),
                    'options' => [
                        ['text' => $this->tr('إنهاء الكتاب في جلسة واحدة', 'Finishing the book in one sitting', 'Finir le livre d\'une traite', 'کتاب ایک ہی نشست میں ختم کرنا')],
                        ['correct' => true, 'text' => $this->tr('صفحات قليلة بانتظام يومي', 'A few pages every day', 'Quelques pages chaque jour', 'روزانہ چند صفحات باقاعدگی سے')],
                        ['text' => $this->tr('القراءة عند توفر الوقت فقط', 'Reading only when time allows', 'Lire seulement quand on a le temps', 'صرف فرصت ملنے پر پڑھنا')],
                    ],
                ],
                [
                    'text' => $this->tr(
                        'ما المطلوب من القارئ قبل الانتقال إلى الاختبار؟',
                        'What is asked of the reader before moving on to the quiz?',
                        'Que demande-t-on au lecteur avant de passer au quiz ?',
                        'امتحان سے پہلے قاری سے کیا مطلوب ہے؟',
                    ),
                    'options' => [
                        ['text' => $this->tr('إعادة قراءة القسم كاملاً', 'Re-reading the whole section', 'Relire toute la section', 'پورا حصہ دوبارہ پڑھنا')],
                        ['correct' => true, 'text' => $this->tr('كتابة ثلاث أفكار رئيسية بلغته', 'Writing three main ideas in their own words', 'Écrire trois idées principales avec ses mots', 'تین بنیادی خیالات اپنے الفاظ میں لکھنا')],
                        ['text' => $this->tr('الانتقال مباشرة دون مراجعة', 'Moving on with no review at all', 'Passer directement sans révision', 'بغیر مراجعے کے آگے بڑھ جانا')],
                    ],
                ],
            ],

            // ── Building a reading plan ──────────────────────────────────────
            1 => [
                [
                    'text' => $this->tr(
                        'ما أول خطوة عملية في بناء خطة القراءة؟',
                        'What is the first practical step in building a reading plan?',
                        'Quelle est la première étape pratique d\'un plan de lecture ?',
                        'مطالعے کا منصوبہ بنانے میں پہلا عملی قدم کیا ہے؟',
                    ),
                    'options' => [
                        ['correct' => true, 'text' => $this->tr('تقسيم الكتاب إلى أقسام متساوية', 'Dividing the book into equal sections', 'Diviser le livre en sections égales', 'کتاب کو برابر حصوں میں تقسیم کرنا')],
                        ['text' => $this->tr('شراء كتب إضافية', 'Buying additional books', 'Acheter d\'autres livres', 'اضافی کتابیں خریدنا')],
                        ['text' => $this->tr('تحديد موعد الاختبار', 'Fixing the exam date', 'Fixer la date de l\'examen', 'امتحان کی تاریخ مقرر کرنا')],
                    ],
                ],
                [
                    'text' => $this->tr(
                        'ما فائدة تحديد وقت ثابت للقراءة؟',
                        'Why fix a set time to read?',
                        'Pourquoi fixer une heure de lecture ?',
                        'مطالعے کا مقررہ وقت رکھنے کا کیا فائدہ ہے؟',
                    ),
                    'options' => [
                        ['correct' => true, 'text' => $this->tr('ترسيخ العادة والاستمرار عليها', 'It settles the habit and keeps it going', 'Cela ancre l\'habitude et la maintient', 'عادت پختہ ہوتی ہے اور تسلسل قائم رہتا ہے')],
                        ['text' => $this->tr('إنهاء الكتاب في وقت أقصر', 'It finishes the book faster', 'Cela permet de finir le livre plus vite', 'کتاب جلد ختم ہو جاتی ہے')],
                        ['text' => $this->tr('تقليل عدد الصفحات المقروءة', 'It reduces the number of pages read', 'Cela réduit le nombre de pages lues', 'پڑھے جانے والے صفحات کم ہو جاتے ہیں')],
                    ],
                ],
                [
                    'text' => $this->tr(
                        'أين تُدوَّن الملاحظات بحسب القسم؟',
                        'Where does the section say notes should be taken?',
                        'Où la section conseille-t-elle de prendre des notes ?',
                        'حصے کے مطابق نوٹ کہاں لکھے جائیں؟',
                    ),
                    'options' => [
                        ['text' => $this->tr('في نهاية الكتاب فقط', 'Only at the end of the book', 'Seulement à la fin du livre', 'صرف کتاب کے آخر میں')],
                        ['correct' => true, 'text' => $this->tr('على هامش النص أو في بطاقة مستقلة', 'In the margin or on a separate card', 'En marge ou sur une fiche séparée', 'حاشیے پر یا الگ کارڈ پر')],
                        ['text' => $this->tr('لا تُدوَّن الملاحظات', 'Notes are not taken at all', 'On ne prend pas de notes', 'نوٹ لکھے ہی نہیں جاتے')],
                    ],
                ],
            ],

            // ── Review and summary ───────────────────────────────────────────
            2 => [
                [
                    'text' => $this->tr(
                        'متى تكون المراجعة أنفع؟',
                        'When is review most useful?',
                        'Quand la révision est-elle la plus utile ?',
                        'مراجعہ کب سب سے زیادہ مفید ہے؟',
                    ),
                    'options' => [
                        ['correct' => true, 'text' => $this->tr('عقب إنهاء كل قسم', 'Right after finishing each section', 'Juste après chaque section', 'ہر حصہ ختم کرنے کے فوراً بعد')],
                        ['text' => $this->tr('بعد إنهاء الكتاب كاملاً', 'Only after the whole book is finished', 'Seulement à la fin du livre', 'پوری کتاب ختم کرنے کے بعد')],
                        ['text' => $this->tr('قبل بدء القراءة', 'Before reading begins', 'Avant de commencer la lecture', 'مطالعہ شروع کرنے سے پہلے')],
                    ],
                ],
                [
                    'text' => $this->tr(
                        'كم فكرة رئيسية يُطلب من القارئ كتابتها؟',
                        'How many main ideas is the reader asked to write?',
                        'Combien d\'idées principales le lecteur doit-il écrire ?',
                        'قاری سے کتنے بنیادی خیالات لکھنے کو کہا جاتا ہے؟',
                    ),
                    'options' => [
                        ['text' => $this->tr('فكرة واحدة', 'One', 'Une seule', 'ایک')],
                        ['correct' => true, 'text' => $this->tr('ثلاث أفكار', 'Three', 'Trois', 'تین')],
                        ['text' => $this->tr('عشر أفكار', 'Ten', 'Dix', 'دس')],
                    ],
                ],
                [
                    'text' => $this->tr(
                        'بأي صيغة تُكتب خلاصة القسم؟',
                        'In what form should the section summary be written?',
                        'Sous quelle forme rédiger le résumé de la section ?',
                        'حصے کا خلاصہ کس صورت میں لکھا جائے؟',
                    ),
                    'options' => [
                        ['correct' => true, 'text' => $this->tr('بلغة القارئ الخاصة', 'In the reader\'s own words', 'Avec les mots du lecteur', 'قاری کے اپنے الفاظ میں')],
                        ['text' => $this->tr('بنسخ نص الكتاب حرفياً', 'By copying the book\'s text word for word', 'En recopiant le texte mot pour mot', 'کتاب کا متن لفظ بہ لفظ نقل کر کے')],
                        ['text' => $this->tr('بعناوين الفصول فقط', 'As chapter headings only', 'Seulement les titres de chapitres', 'صرف ابواب کے عنوانات کی صورت میں')],
                    ],
                ],
            ],
        ];
    }
}
