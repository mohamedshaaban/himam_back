<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Badge;
use App\Models\Book;
use App\Models\BookSection;
use App\Models\Certificate;
use App\Models\Level;
use App\Models\QuizAttempt;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The rules of the reading programme in one place: what a passing quiz is,
 * when points are credited, which badges unlock, and when a level certificate
 * is issued. Controllers stay thin and call in here.
 */
class ProgressService
{
    /**
     * Grades a submitted attempt and applies every consequence of it.
     *
     * @param  array<int,int>  $answers  question id => chosen option id
     * @return array{attempt: QuizAttempt, correct: array<int,int>, incorrect: array<int,int>, new_badges: \Illuminate\Support\Collection, new_certificates: \Illuminate\Support\Collection}
     */
    public function submitQuiz(User $user, BookSection $section, array $answers): array
    {
        $questions = $section->questions()->with('options')->get();

        // The answer key is read here, server-side, and never sent to the
        // client before grading.
        $key = $questions->mapWithKeys(
            fn ($question) => [$question->id => $question->correctOption()?->id]
        );

        $right = $questions->filter(
            fn ($question) => isset($answers[$question->id])
                && (int) $answers[$question->id] === $key[$question->id]
        );

        $score = $right->count();

        // Named so a failed attempt can be shown what to go back and read,
        // without being shown the answers.
        $incorrect = $questions->whereNotIn('id', $right->pluck('id'))
            ->pluck('id')
            ->values()
            ->all();

        $total = $questions->count();
        $passed = $total > 0 && ($score / $total) >= config('himam.quiz_pass_ratio');

        return DB::transaction(function () use ($user, $section, $answers, $score, $total, $passed, $key, $incorrect) {
            $progress = ReadingProgress::firstOrNew([
                'user_id' => $user->id,
                'book_section_id' => $section->id,
            ]);

            // Points are credited the first time a section is passed only —
            // retries after a pass are free to take but earn nothing.
            $alreadyPassed = $progress->exists && $progress->passed_at !== null;
            $pointsAwarded = ($passed && ! $alreadyPassed) ? (int) config('himam.section_points') : 0;

            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'book_section_id' => $section->id,
                'score' => $score,
                'total' => $total,
                'passed' => $passed,
                'points_awarded' => $pointsAwarded,
                'answers' => $answers,
            ]);

            $progress->read_at ??= now();
            if ($passed && ! $alreadyPassed) {
                $progress->passed_at = now();
            }
            $progress->save();

            // Badges and certificates are decided from this reader's passes a
            // few lines below, and those lists are remembered per request.
            $user->forgetProgress();

            if ($pointsAwarded > 0) {
                $user->increment('points', $pointsAwarded);
                $user->refresh();
            }

            return [
                'attempt' => $attempt,
                'correct' => $key->filter()->all(),
                'incorrect' => $incorrect,
                'new_badges' => $this->syncBadges($user),
                'new_certificates' => $this->issueEarnedCertificates($user),
            ];
        });
    }

    /**
     * Grades the quiz for a whole book.
     *
     * The section version in reverse: a book without sections carries one quiz,
     * so the pass is recorded against the book and the points are credited
     * once, the first time it is passed. Everything downstream — badges,
     * certificates, the honour board — reads the same progress table either way.
     */
    public function submitBookQuiz(User $user, Book $book, array $answers): array
    {
        $questions = $book->questions()->with('options')->get();

        // The answer key is read here, server-side, and never sent to the
        // client before grading.
        $key = $questions->mapWithKeys(
            fn ($question) => [$question->id => $question->correctOption()?->id]
        );

        $right = $questions->filter(
            fn ($question) => isset($answers[$question->id])
                && (int) $answers[$question->id] === $key[$question->id]
        );

        $score = $right->count();

        // Named so a failed attempt can be shown what to go back and read,
        // without being shown the answers.
        $incorrect = $questions->whereNotIn('id', $right->pluck('id'))
            ->pluck('id')
            ->values()
            ->all();

        $total = $questions->count();
        $passed = $total > 0 && ($score / $total) >= config('himam.quiz_pass_ratio');

        return DB::transaction(function () use ($user, $book, $answers, $score, $total, $passed, $key, $incorrect) {
            $progress = ReadingProgress::firstOrNew([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);

            $alreadyPassed = $progress->exists && $progress->passed_at !== null;

            // A whole book is worth what its sections would have been, so a
            // book-level pass does not pay less than the same material split up.
            $pointsAwarded = ($passed && ! $alreadyPassed)
                ? (int) ($book->points ?: config('himam.section_points'))
                : 0;

            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'score' => $score,
                'total' => $total,
                'passed' => $passed,
                'points_awarded' => $pointsAwarded,
                'answers' => $answers,
            ]);

            $progress->read_at ??= now();

            if ($passed && ! $alreadyPassed) {
                $progress->passed_at = now();
            }

            $progress->save();

            // Badges and certificates are decided from this reader's passes a
            // few lines below, and those lists are remembered per request.
            $user->forgetProgress();

            if ($pointsAwarded > 0) {
                $user->increment('points', $pointsAwarded);
                $user->refresh();
            }

            return [
                'attempt' => $attempt,
                'correct' => $key->filter()->all(),
                'incorrect' => $incorrect,
                'new_badges' => $this->syncBadges($user),
                'new_certificates' => $this->issueEarnedCertificates($user),
            ];
        });
    }

    /**
     * Records that a reader opened a book that has no sections.
     */
    public function markBookRead(User $user, Book $book): ReadingProgress
    {
        $progress = ReadingProgress::firstOrNew([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $progress->read_at ??= now();
        $progress->save();

        $user->forgetProgress();

        return $progress;
    }

    /**
     * Records that a reader opened a section, without touching pass state.
     */
    public function markRead(User $user, BookSection $section): ReadingProgress
    {
        $progress = ReadingProgress::firstOrNew([
            'user_id' => $user->id,
            'book_section_id' => $section->id,
        ]);

        $progress->read_at ??= now();
        $progress->save();

        $user->forgetProgress();

        return $progress;
    }

    /**
     * Awards any automatic badge whose threshold this reader now meets.
     *
     * Badges are never revoked — once earned, they stay.
     */
    public function syncBadges(User $user): \Illuminate\Support\Collection
    {
        $stats = $this->stats($user);
        $alreadyHeld = $user->badges()->pluck('badges.id')->all();

        $newlyEarned = Badge::query()
            ->where('is_active', true)
            ->where('criteria_type', '!=', 'manual')
            ->whereNotIn('id', $alreadyHeld)
            ->get()
            ->filter(function (Badge $badge) use ($stats) {
                $actual = match ($badge->criteria_type) {
                    'sections_passed' => $stats['sections_passed'],
                    'books_completed' => $stats['books_completed'],
                    'points' => $stats['points'],
                    default => null,
                };

                return $actual !== null && $actual >= $badge->criteria_value;
            });

        foreach ($newlyEarned as $badge) {
            $user->badges()->attach($badge->id, ['awarded_at' => now()]);
            $this->announceBadge($user, $badge);
        }

        return $newlyEarned->values();
    }

    /**
     * Issues every certificate this reader has now earned and does not hold.
     *
     * Two kinds, and a reader finishing the last book of a level earns both:
     * one for the book, awarded the moment its exam is passed, and one for the
     * level, awarded when every book in it is done. The book certificate is
     * what the programme promises on finishing a single book; the level one
     * stays as the larger milestone above it.
     */
    public function issueEarnedCertificates(User $user): \Illuminate\Support\Collection
    {
        $passedSectionIds = $user->passedSectionIds();
        $passedBookIds = $user->passedBookIds();

        $issued = $this->issueBookCertificates($user, $passedSectionIds, $passedBookIds);

        $held = $user->certificates()->whereNotNull('level_id')->get()->keyBy('level_id');

        $levels = Level::with('books.sections')->where('is_active', true)->get();

        foreach ($levels as $level) {
            $existing = $held->get($level->id);

            $books = $level->books->where('is_published', true);

            // A level with no content yet must not hand out certificates.
            if ($books->isEmpty()) {
                continue;
            }

            $unfinished = $books->first(
                fn (Book $book) => ! $book->completedBy($passedSectionIds, $passedBookIds)
            );

            if ($unfinished) {
                continue;
            }

            // A reader who retakes the quiz and passes again gets the same
            // certificate re-dated, not a new one. The serial and verification
            // code are printed on copies and shared with people who are not
            // readers of this app, and a reissue would quietly stop every one
            // of them verifying.
            if ($existing) {
                $existing->forceFill(['issued_at' => now()])->save();

                continue;
            }

            $issued->push($this->issueCertificate($user, $level));
        }

        return $issued;
    }

    /**
     * A certificate per book this reader has finished.
     *
     * "Finished" means what it means everywhere else in the app: every section
     * passed, or — for a book examined as a whole — its own quiz passed. A book
     * with neither sections nor questions cannot be finished, so it never
     * issues one.
     *
     * @param  array<int,int>  $passedSectionIds
     * @param  array<int,int>  $passedBookIds
     * @return \Illuminate\Support\Collection<int, Certificate>
     */
    private function issueBookCertificates(User $user, array $passedSectionIds, array $passedBookIds): \Illuminate\Support\Collection
    {
        $held = $user->certificates()->whereNotNull('book_id')->get()->keyBy('book_id');

        $issued = collect();

        $books = Book::published()->with('sections:id,book_id')->withCount('questions')->get();

        foreach ($books as $book) {
            if (! $book->completedBy($passedSectionIds, $passedBookIds)) {
                continue;
            }

            // As with levels: a retake re-dates the certificate rather than
            // replacing it, so a serial already shared with someone outside the
            // app keeps verifying.
            if ($existing = $held->get($book->id)) {
                $existing->forceFill(['issued_at' => now()])->save();

                continue;
            }

            $issued->push($this->issueCertificate($user, null, $book));
        }

        return $issued;
    }

    /**
     * Tells the reader they have earned a badge.
     *
     * The same inbox as the certificate notice, for the same reasons, and muted
     * by the same preference machinery.
     */
    private function announceBadge(User $user, Badge $badge): void
    {
        $name = $this->perLocale($badge->name);

        Announcement::create([
            'user_id' => $user->id,
            'category' => 'badge',
            'image' => $badge->image,
            'published_at' => now(),
            'tag' => [
                'ar' => 'وسام',
                'en' => 'Badge',
                'fr' => 'Badge',
                'ur' => 'اعزاز',
            ],
            'title' => [
                'ar' => "حصلت على وسام {$name['ar']}",
                // No article before the name: badge names carry their own
                // ("The Persistent"), and the two together read as a stutter.
                'en' => "New badge: {$name['en']}",
                'fr' => "Nouveau badge : {$name['fr']}",
                'ur' => "آپ نے {$name['ur']} اعزاز حاصل کیا",
            ],
            'body' => [
                'ar' => "تهانينا، أضيف وسام «{$name['ar']}» إلى أوسمتك.",
                'en' => "Congratulations — “{$name['en']}” has been added to your badges.",
                'fr' => "Félicitations — « {$name['fr']} » a été ajouté à vos badges.",
                'ur' => "مبارک ہو، ”{$name['ur']}“ اعزاز آپ کے اعزازات میں شامل ہو گیا۔",
            ],
        ]);
    }

    /**
     * Reads a translatable attribute as one string per shipped locale, so a
     * notification can be written in all of them at once.
     *
     * A language the record is missing falls back to one it has, rather than
     * leaving a hole in the middle of the sentence.
     *
     * @return array<string,string>
     */
    private function perLocale(mixed $value): array
    {
        $values = is_array($value)
            ? array_filter($value, fn ($text) => filled($text))
            : array_filter(['en' => $value], fn ($text) => filled($text));

        $fallback = $values[config('app.fallback_locale')] ?? (reset($values) ?: '');

        return [
            'ar' => $values['ar'] ?? $fallback,
            'en' => $values['en'] ?? $fallback,
            'fr' => $values['fr'] ?? $fallback,
            'ur' => $values['ur'] ?? $fallback,
        ];
    }

    /**
     * Tells the reader a certificate has been issued to them.
     *
     * Written as a targeted announcement rather than a separate notification
     * system: it is the same inbox the reader already has, it carries the
     * unread count on the bell, and a reader who has muted the certificate
     * category is respected without any extra work here — the feed filters on
     * that already.
     *
     * Only on a new certificate. Re-dating one after a retake is not news.
     */
    private function announceCertificate(User $user, Certificate $certificate): void
    {
        // The subject is named in the notice itself: a reader finishing the last
        // book of a level receives two certificates at once, and "your
        // certificate has been issued" twice over says nothing about which.
        $subject = $this->perLocale($certificate->title);
        $serial = $certificate->serial;

        Announcement::create([
            'user_id' => $user->id,
            'category' => 'certificate',
            'published_at' => now(),
            'tag' => [
                'ar' => 'شهادة',
                'en' => 'Certificate',
                'fr' => 'Certificat',
                'ur' => 'سند',
            ],
            'title' => [
                'ar' => "صدرت شهادتك في «{$subject['ar']}»",
                'en' => "Your certificate for “{$subject['en']}” has been issued",
                'fr' => "Votre certificat pour « {$subject['fr']} » a été émis",
                'ur' => "”{$subject['ur']}“ کی آپ کی سند جاری ہو گئی",
            ],
            'body' => [
                'ar' => "تهانينا، صدرت شهادتك في «{$subject['ar']}» برقم {$serial}. تجدها في صفحة الشهادات.",
                'en' => "Congratulations — your certificate for “{$subject['en']}” has been issued with serial {$serial}. You will find it on the certificates page.",
                'fr' => "Félicitations — votre certificat pour « {$subject['fr']} » a été émis sous le numéro {$serial}. Vous le trouverez sur la page des certificats.",
                'ur' => "مبارک ہو، ”{$subject['ur']}“ کی آپ کی سند نمبر {$serial} جاری ہو گئی۔ یہ اسناد کے صفحے پر ملے گی۔",
            ],
        ]);
    }

    public function issueCertificate(User $user, ?Level $level = null, ?Book $book = null): Certificate
    {
        $certificate = Certificate::create([
            'user_id' => $user->id,
            'level_id' => $level?->id,
            'book_id' => $book?->id,
            'serial' => $this->nextSerial(),
            'verification_code' => (string) Str::uuid(),
            'title' => $level?->name ?? $book?->title,
            'issued_at' => now(),
        ]);

        $this->announceCertificate($user, $certificate);

        return $certificate;
    }

    /**
     * Aggregate counters used by the home screen and by badge evaluation.
     */
    /**
     * @param  \Illuminate\Support\Collection<int, Book>|null  $books  the published
     *         catalogue, with sections and question counts, when the caller has
     *         already loaded it — the progress screen has.
     */
    public function stats(User $user, ?\Illuminate\Support\Collection $books = null): array
    {
        $passedSectionIds = $user->passedSectionIds();
        $passedBookIds = $user->passedBookIds();

        $books ??= Book::published()
            ->with('sections:id,book_id')
            ->withCount('questions')
            ->get();

        $booksCompleted = $books
            ->filter(fn (Book $book) => $book->completedBy($passedSectionIds, $passedBookIds))
            ->count();

        return [
            'points' => (int) $user->points,
            'sections_passed' => count($passedSectionIds),

            // Deliberately not counted off $books: a caller may hand in a
            // narrower set, and these two have always meant every published
            // section and book. Deriving them from whatever was passed would
            // change what the screen reports under cover of a speed-up.
            'sections_total' => BookSection::whereHas('book', fn ($q) => $q->published())->count(),
            'books_completed' => $booksCompleted,
            'books_total' => Book::published()->count(),
            'badges_earned' => $user->badges()->count(),
            'certificates' => $user->certificates()->count(),
        ];
    }

    /**
     * The full picture of one reader's journey, for the progress screen.
     *
     * Everything is resolved from three bulk reads — progress rows, attempts and
     * the catalogue — rather than querying per section, so the payload stays a
     * handful of queries no matter how many books the programme grows to.
     */
    public function detail(User $user): array
    {
        // Every figure on this screen comes out of three tables, so each is
        // read once. It used to read reading_progress twice and quiz_attempts
        // three times, which on a database in another region is five round
        // trips for rows already in memory.
        $progressRows = $user->progressRows()->keyBy('book_section_id');
        $allAttempts = $user->quizAttempts()->get();
        $attempts = $allAttempts->groupBy('book_section_id');
        $certificates = $user->certificates()->whereNotNull('level_id')->get()->keyBy('level_id');

        $passedSectionIds = $user->passedSectionIds();
        $passedBookIds = $user->passedBookIds();

        $levels = Level::query()
            ->where('is_active', true)
            ->with(['books' => fn ($q) => $q->where('is_published', true)->withCount('questions')->orderBy('position'),
                    'books.sections' => fn ($q) => $q->withCount('questions')->orderBy('position')])
            ->orderBy('position')
            ->get();

        $levelPayload = $levels->map(function (Level $level) use ($progressRows, $attempts, $certificates, $passedSectionIds, $passedBookIds) {
            $books = $level->books->map(function (Book $book) use ($progressRows, $attempts, $passedSectionIds, $passedBookIds) {
                $sections = $book->sections->map(function (BookSection $section) use ($progressRows, $attempts) {
                    $row = $progressRows->get($section->id);
                    $tries = $attempts->get($section->id, collect());

                    return [
                        'id' => $section->id,
                        'title' => $section->t('title'),
                        'position' => $section->position,
                        'read' => (bool) $row?->read_at,
                        'passed' => (bool) $row?->passed_at,
                        'attempts' => $tries->count(),
                        'best_score' => $tries->max('score'),
                        'questions' => $section->questions_count,
                        'passed_at' => $row?->passed_at?->toDateString(),
                    ];
                })->values();

                $passed = $sections->where('passed', true)->count();
                $read = $sections->where('read', true)->count();
                $total = $sections->count();

                return [
                    'id' => $book->id,
                    'title' => $book->t('title'),
                    'points' => $book->points,
                    'pages' => $book->pages,
                    'has_own_quiz' => $book->hasOwnQuiz(),
                    'sections_total' => $total,
                    'sections_read' => $read,
                    'sections_passed' => $passed,
                    'read_percent' => $total > 0 ? (int) round($read / $total * 100) : 0,
                    'percent' => $total > 0 ? (int) round($passed / $total * 100) : 0,
                    'completed' => $book->completedBy($passedSectionIds, $passedBookIds),
                    'sections' => $sections,
                ];
            })->values();

            $sectionsTotal = $books->sum('sections_total');
            $sectionsPassed = $books->sum('sections_passed');
            $certificate = $certificates->get($level->id);

            return [
                'id' => $level->id,
                'name' => $level->t('name'),
                'books_total' => $books->count(),
                'books_completed' => $books->where('completed', true)->count(),
                'sections_total' => $sectionsTotal,
                'sections_passed' => $sectionsPassed,
                'percent' => $sectionsTotal > 0 ? (int) round($sectionsPassed / $sectionsTotal * 100) : 0,
                'certificate' => $certificate ? [
                    'id' => $certificate->id,
                    'serial' => $certificate->serial,
                    'issued_at' => $certificate->issued_at?->toDateString(),
                ] : null,
                'books' => $books,
            ];
        })->values();

        $stats = $this->stats($user);

        return [
            'stats' => $stats,
            'levels' => $levelPayload,
            'next_badges' => $this->nextBadges($user, 3, $stats),
            'recent_attempts' => $this->recentAttempts($user, 10, $allAttempts),
            'monthly_points' => $this->monthlyPoints($user, $allAttempts),
        ];
    }

    /**
     * The automatic badges this reader is closest to earning, so the screen can
     * show something to aim at rather than only what's already done.
     */
    private function nextBadges(User $user, int $limit = 3, ?array $stats = null): \Illuminate\Support\Collection
    {
        // detail() has already worked these out; recomputing them walked the
        // whole catalogue a second time for one screen.
        $stats ??= $this->stats($user);
        $held = $user->badges()->pluck('badges.id')->all();

        return Badge::query()
            ->where('is_active', true)
            ->where('criteria_type', '!=', 'manual')
            ->whereNotIn('id', $held)
            ->get()
            ->map(function (Badge $badge) use ($stats) {
                $current = match ($badge->criteria_type) {
                    'sections_passed' => $stats['sections_passed'],
                    'books_completed' => $stats['books_completed'],
                    'points' => $stats['points'],
                    default => 0,
                };

                return [
                    'id' => $badge->id,
                    'name' => $badge->t('name'),
                    'description' => $badge->t('description'),
                    'image' => $badge->image,
                    'criteria_type' => $badge->criteria_type,
                    'criteria_value' => $badge->criteria_value,
                    'current' => $current,
                    'remaining' => max(0, $badge->criteria_value - $current),
                    'percent' => $badge->criteria_value > 0
                        ? min(100, (int) round($current / $badge->criteria_value * 100))
                        : 0,
                ];
            })
            ->sortByDesc('percent')
            ->take($limit)
            ->values();
    }

    private function recentAttempts(User $user, int $limit = 10, ?\Illuminate\Support\Collection $attempts = null): \Illuminate\Support\Collection
    {
        $recent = $attempts
            ? $attempts->sortByDesc('created_at')->take($limit)->values()->load('section.book')
            : $user->quizAttempts()->with('section.book')->latest()->take($limit)->get();

        return $recent
            ->map(fn (QuizAttempt $attempt) => [
                'id' => $attempt->id,
                'score' => $attempt->score,
                'total' => $attempt->total,
                'passed' => $attempt->passed,
                'points_awarded' => $attempt->points_awarded,
                'created_at' => $attempt->created_at->toIso8601String(),
                'section' => [
                    'id' => $attempt->section?->id,
                    'title' => $attempt->section?->t('title'),
                ],
                'book' => [
                    'id' => $attempt->section?->book?->id,
                    'title' => $attempt->section?->book?->t('title'),
                ],
            ]);
    }

    /**
     * Points credited per month over the last year, for the trend bars.
     */
    private function monthlyPoints(User $user, ?\Illuminate\Support\Collection $attempts = null): \Illuminate\Support\Collection
    {
        $since = now()->subMonths(11)->startOfMonth();

        $rows = ($attempts
                ? $attempts->filter(fn (QuizAttempt $attempt) => $attempt->created_at >= $since)
                : $user->quizAttempts()->where('created_at', '>=', $since)->get(['points_awarded', 'created_at']))
            ->groupBy(fn (QuizAttempt $attempt) => $attempt->created_at->format('Y-m'))
            ->map(fn ($group) => (int) $group->sum('points_awarded'));

        // Emit every month in the window, including the empty ones, so the chart
        // shows real gaps instead of silently compressing inactive months.
        return collect(range(11, 0))
            ->map(function (int $monthsAgo) use ($rows) {
                $month = now()->subMonths($monthsAgo)->startOfMonth();
                $key = $month->format('Y-m');

                return [
                    'month' => $key,
                    'label' => $month->translatedFormat('M'),
                    'points' => $rows->get($key, 0),
                ];
            })
            ->values();
    }

    private function nextSerial(): string
    {
        $year = now()->year;

        do {
            $serial = sprintf('CERT-%d-%s', $year, strtoupper(Str::random(6)));
        } while (Certificate::where('serial', $serial)->exists());

        return $serial;
    }
}
