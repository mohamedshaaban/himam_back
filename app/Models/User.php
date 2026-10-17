<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /** The one registration field with a fixed set of answers. */
    public const GENDERS = ['male', 'female'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'city',
        'country',
        'accepts_email',
        'avatar',
        'role',
        'locale',
        'points',
        'level_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points' => 'integer',
            'accepts_email' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class)->withPivot('awarded_at')->withTimestamps();
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /**
     * Announcements addressed to this reader specifically. The feed also
     * includes broadcast announcements, which are resolved in the controller.
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function readAnnouncements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class)->withPivot('read_at')->withTimestamps();
    }

    /**
     * Sections this reader has passed the quiz for.
     */
    /** Selective programmes an administrator has assigned to this reader. */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class)->withPivot(['assigned_by', 'assigned_at']);
    }

    /**
     * Books passed by their own quiz — the books that have no sections.
     *
     * A book is finished by passing its own exam, and nothing else — so this
     * is the list every completion rule consults.
     */
    /**
     * This reader's progress rows, read once per request.
     *
     * The three lists below are three readings of the same table, and a single
     * book screen asked for them five times between the programme lock, the
     * progress block and the exam summary. Against a database in another
     * region that is five round trips for rows one query returns.
     *
     * Anything that writes progress calls forgetProgress() so the next read
     * sees it — grading does exactly that before it awards badges.
     */
    private ?\Illuminate\Support\Collection $progressRows = null;

    public function progressRows(): \Illuminate\Support\Collection
    {
        return $this->progressRows ??= $this->progress()
            ->get(['book_section_id', 'book_id', 'read_at', 'passed_at']);
    }

    public function forgetProgress(): static
    {
        $this->progressRows = null;
        $this->examResults = null;

        return $this;
    }

    public function passedBookIds(): array
    {
        return $this->progressRows()
            ->filter(fn ($row) => $row->book_id !== null && $row->passed_at !== null)
            ->pluck('book_id')
            ->all();
    }

    /**
     * The sections this reader has opened.
     *
     * Sections are read, not passed: the exam is the book's. This is what the
     * book screen measures, and what unlocks that exam.
     */
    /**
     * Books this reader has opened, for the ones that are one document and so
     * have no sections to count.
     */
    /**
     * The best this reader has done on each book's exam.
     *
     * One query for the whole catalogue rather than one per book: the screen
     * that lists books needs this for every row to decide what its exam button
     * should say.
     *
     * @return array<int, array{best: int, total: int, passed: bool}>
     */
    public function bookExamResults(): array
    {
        return $this->examResults ??= $this->quizAttempts()
            ->whereNotNull('book_id')
            ->selectRaw('book_id, MAX(score) as best, MAX(total) as total, MAX(passed) as passed')
            ->groupBy('book_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->book_id => [
                'best' => (int) $row->best,
                'total' => (int) $row->total,
                'passed' => (bool) $row->passed,
            ]])
            ->all();
    }

    private ?array $examResults = null;

    public function readBookIds(): array
    {
        return $this->progressRows()
            ->filter(fn ($row) => $row->book_id !== null && $row->read_at !== null)
            ->pluck('book_id')
            ->all();
    }

    public function readSectionIds(): array
    {
        return $this->progressRows()
            ->filter(fn ($row) => $row->book_section_id !== null && $row->read_at !== null)
            ->pluck('book_section_id')
            ->all();
    }

}
