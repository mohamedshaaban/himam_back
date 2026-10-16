<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'level_id', 'title', 'author', 'description',
        'cover', 'pdf', 'pages', 'points', 'position', 'is_published',
        'quiz_button_visible', 'quiz_button_label',
    ];

    public array $translatable = ['title', 'author', 'description', 'pdf', 'quiz_button_label'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'author' => 'array',
            'description' => 'array',
            'pdf' => 'array',
            'quiz_button_label' => 'array',
            'quiz_button_visible' => 'boolean',
            'pages' => 'integer',
            'points' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * The books a reader may see.
     *
     * Published, and on a level that is still active. Deactivating a level
     * withdraws its books from the programme entirely — they leave the
     * catalogue, their sections and exams stop answering, and they leave both
     * sides of every ratio. The alternative, where an inactive level only
     * hid a heading while its books stayed open, meant "inactive" described
     * the heading rather than the content.
     *
     * Administrators are unaffected: nothing under /api/admin goes through
     * this scope, so a withdrawn book is still there to be edited and
     * reinstated.
     */
    /**
     * Set per request by whichever controller resolved this reader's progress.
     * Null when nobody is signed in, which is not the same as "not finished".
     */
    public ?bool $readerFullyRead = null;

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereHas('level', fn (Builder $level) => $level->where('is_active', true));
    }

    /**
     * The same rule for one book in hand, for the endpoints that are given a
     * book by the router rather than querying for it.
     */
    public function isAvailable(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        return (bool) $this->level?->is_active;
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(BookSection::class)->orderBy('position');
    }

    /**
     * Questions for the book as a whole.
     *
     * Only a book without sections has these: the quiz is either per section or
     * for the whole book, never both, so that the points for finishing it are
     * awarded once and in one place.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    /**
     * Whether the exam for this book is the book's own.
     *
     * Uses a loaded count or relation when the caller has one, so a list of
     * books does not become a query per book.
     */
    public function hasOwnQuiz(): bool
    {
        if (isset($this->questions_count)) {
            return $this->questions_count > 0;
        }

        if ($this->relationLoaded('questions')) {
            return $this->questions->isNotEmpty();
        }

        return $this->questions()->exists();
    }

    /**
     * Whether this reader has finished the book.
     *
     * One rule in one place. It was five, and one of them had already drifted.
     *
     * By passing its exam, and only that. The exam covers the whole book, so
     * there is nothing else that could finish it: sections are the way through
     * the reading, and a book whose questions have not been written yet cannot
     * be finished at all.
     *
     * @param  array<int,int>  $passedBookIds
     */
    public function completedBy(array $passedBookIds): bool
    {
        return in_array($this->id, $passedBookIds, true);
    }

    /**
     * How the quiz button should be presented for this book.
     *
     * The dashboard's switch decides whether the button is offered, but a book
     * whose questions have not been written yet never offers one whatever the
     * switch says — a button leading to an empty paper is worse than no button.
     *
     * The label falls back to the translated default, so a book that has
     * nothing special to say still gets correct wording in every language.
     */
    /**
     * The one flag that decides whether the exam is offered.
     *
     * Three things have to be true, and `reason` names the first that is not,
     * so a screen can say why the button is absent instead of leaving a reader
     * to guess:
     *
     *   no_questions    the exam has not been written yet
     *   hidden          an editor has turned it off
     *   not_signed_in   nobody to examine
     *   unread          the reading is not finished
     *
     * The reading requirement is the point: the exam covers the whole book, so
     * it is offered once the whole book has been read. `readerFullyRead` is set
     * by the controller, which already knows how much of this book the reader
     * has opened; without it this cannot be answered and the button stays shut.
     */
    public function quizButton(): array
    {
        $label = $this->t('quiz_button_label') ?: __('Take the quiz');

        $reason = match (true) {
            ! $this->hasOwnQuiz() => 'no_questions',
            ! $this->quiz_button_visible => 'hidden',
            $this->readerFullyRead === null => 'not_signed_in',
            $this->readerFullyRead === false => 'unread',
            default => null,
        };

        return [
            'visible' => $reason === null,
            'label' => $label,
            'reason' => $reason,
        ];
    }

    /**
     * Whether this reader has read the whole book.
     *
     * Every section opened, or — for a book that is one document — the book
     * itself opened. A book with neither sections nor a document cannot be
     * read through, so it is never finished.
     *
     * @param  array<int,int>  $readSectionIds
     * @param  array<int,int>  $readBookIds
     * @param  \Illuminate\Support\Collection|array|null  $sectionIds  when the caller has them
     */
    public function fullyReadBy(array $readSectionIds, array $readBookIds, $sectionIds = null): bool
    {
        $sectionIds = collect($sectionIds ?? ($this->relationLoaded('sections')
            ? $this->sections->pluck('id')
            : $this->sections()->pluck('id')));

        if ($sectionIds->isNotEmpty()) {
            return $sectionIds->diff($readSectionIds)->isEmpty();
        }

        return in_array($this->id, $readBookIds, true);
    }


    /** The reading programmes this book appears in, if any. */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class)->withPivot('order_index');
    }
}
