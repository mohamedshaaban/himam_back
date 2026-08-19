<?php

namespace App\Services;

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
     * @return array{attempt: QuizAttempt, correct: array<int,int>, new_badges: \Illuminate\Support\Collection, new_certificates: \Illuminate\Support\Collection}
     */
    public function submitQuiz(User $user, BookSection $section, array $answers): array
    {
        $questions = $section->questions()->with('options')->get();

        // The answer key is read here, server-side, and never sent to the
        // client before grading.
        $key = $questions->mapWithKeys(
            fn ($question) => [$question->id => $question->correctOption()?->id]
        );

        $score = $questions->filter(
            fn ($question) => isset($answers[$question->id])
                && (int) $answers[$question->id] === $key[$question->id]
        )->count();

        $total = $questions->count();
        $passed = $total > 0 && ($score / $total) >= config('himam.quiz_pass_ratio');

        return DB::transaction(function () use ($user, $section, $answers, $score, $total, $passed, $key) {
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

            if ($pointsAwarded > 0) {
                $user->increment('points', $pointsAwarded);
                $user->refresh();
            }

            return [
                'attempt' => $attempt,
                'correct' => $key->filter()->all(),
                'new_badges' => $this->syncBadges($user),
                'new_certificates' => $this->issueEarnedCertificates($user),
            ];
        });
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
        }

        return $newlyEarned->values();
    }

    /**
     * Issues a certificate for every level whose books this reader has fully
     * passed and that they don't already hold one for.
     */
    public function issueEarnedCertificates(User $user): \Illuminate\Support\Collection
    {
        $passedSectionIds = $user->passedSectionIds();
        $heldLevelIds = $user->certificates()->whereNotNull('level_id')->pluck('level_id')->all();

        $issued = collect();

        $levels = Level::with('books.sections')->where('is_active', true)->get();

        foreach ($levels as $level) {
            if (in_array($level->id, $heldLevelIds, true)) {
                continue;
            }

            $sectionIds = $level->books
                ->where('is_published', true)
                ->flatMap->sections
                ->pluck('id');

            // A level with no content yet must not hand out certificates.
            if ($sectionIds->isEmpty()) {
                continue;
            }

            if ($sectionIds->diff($passedSectionIds)->isNotEmpty()) {
                continue;
            }

            $issued->push($this->issueCertificate($user, $level));
        }

        return $issued;
    }

    public function issueCertificate(User $user, ?Level $level = null, ?Book $book = null): Certificate
    {
        return Certificate::create([
            'user_id' => $user->id,
            'level_id' => $level?->id,
            'book_id' => $book?->id,
            'serial' => $this->nextSerial(),
            'verification_code' => (string) Str::uuid(),
            'title' => $level?->name ?? $book?->title,
            'issued_at' => now(),
        ]);
    }

    /**
     * Aggregate counters used by the home screen and by badge evaluation.
     */
    public function stats(User $user): array
    {
        $passedSectionIds = $user->passedSectionIds();

        $booksCompleted = Book::published()
            ->withCount('sections')
            ->get()
            ->filter(function (Book $book) use ($passedSectionIds) {
                if ($book->sections_count === 0) {
                    return false;
                }

                $sectionIds = $book->sections()->pluck('id');

                return $sectionIds->diff($passedSectionIds)->isEmpty();
            })
            ->count();

        return [
            'points' => (int) $user->points,
            'sections_passed' => count($passedSectionIds),
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
        $progressRows = $user->progress()->get()->keyBy('book_section_id');
        $attempts = $user->quizAttempts()->get()->groupBy('book_section_id');
        $certificates = $user->certificates()->whereNotNull('level_id')->get()->keyBy('level_id');

        $levels = Level::query()
            ->where('is_active', true)
            ->with(['books' => fn ($q) => $q->where('is_published', true)->orderBy('position'),
                    'books.sections' => fn ($q) => $q->withCount('questions')->orderBy('position')])
            ->orderBy('position')
            ->get();

        $levelPayload = $levels->map(function (Level $level) use ($progressRows, $attempts, $certificates) {
            $books = $level->books->map(function (Book $book) use ($progressRows, $attempts) {
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
                $total = $sections->count();

                return [
                    'id' => $book->id,
                    'title' => $book->t('title'),
                    'points' => $book->points,
                    'pages' => $book->pages,
                    'sections_total' => $total,
                    'sections_passed' => $passed,
                    'percent' => $total > 0 ? (int) round($passed / $total * 100) : 0,
                    'completed' => $total > 0 && $passed === $total,
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

        return [
            'stats' => $this->stats($user),
            'levels' => $levelPayload,
            'next_badges' => $this->nextBadges($user),
            'recent_attempts' => $this->recentAttempts($user),
            'monthly_points' => $this->monthlyPoints($user),
        ];
    }

    /**
     * The automatic badges this reader is closest to earning, so the screen can
     * show something to aim at rather than only what's already done.
     */
    private function nextBadges(User $user, int $limit = 3): \Illuminate\Support\Collection
    {
        $stats = $this->stats($user);
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

    private function recentAttempts(User $user, int $limit = 10): \Illuminate\Support\Collection
    {
        return $user->quizAttempts()
            ->with('section.book')
            ->latest()
            ->take($limit)
            ->get()
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
    private function monthlyPoints(User $user): \Illuminate\Support\Collection
    {
        $rows = $user->quizAttempts()
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->get(['points_awarded', 'created_at'])
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
