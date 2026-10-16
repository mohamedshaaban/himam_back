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

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
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
     * A book with its own exam is finished by passing it, however it is laid
     * out — sections are then the way through the reading, not five separate
     * examinations. A book without one is finished by passing every section.
     * A book with neither cannot be finished at all, and must not become a wall
     * the rest of a programme sits behind.
     *
     * @param  array<int,int>  $passedSectionIds
     * @param  array<int,int>  $passedBookIds
     */
    public function completedBy(array $passedSectionIds, array $passedBookIds): bool
    {
        if ($this->hasOwnQuiz()) {
            return in_array($this->id, $passedBookIds, true);
        }

        $sectionIds = $this->relationLoaded('sections')
            ? $this->sections->pluck('id')
            : $this->sections()->pluck('id');

        if ($sectionIds->isEmpty()) {
            return false;
        }

        return $sectionIds->diff($passedSectionIds)->isEmpty();
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
    public function quizButton(): array
    {
        $hasQuestions = $this->hasOwnQuiz();

        return [
            'visible' => $hasQuestions && (bool) $this->quiz_button_visible,
            'label' => $this->t('quiz_button_label') ?: __('Take the quiz'),
        ];
    }

    /**
     * Where this book's examination lives, said plainly.
     *
     * A client should not have to work this out from the shape of the payload —
     * counting sections, noticing an empty questions_count, inferring that a
     * hidden button means the exam is somewhere else. It asks here and is told.
     *
     *   book     one exam over the whole book, at /books/{id}/quiz
     *   sections one exam per section, at /sections/{id}/quiz
     *   none     no questions have been written yet
     */
    public function examSummary(): array
    {
        if ($this->hasOwnQuiz()) {
            return [
                'scope' => 'book',
                'button' => $this->quizButton(),
                'questions_total' => $this->questions()->count(),
            ];
        }

        // Three ways to know, cheapest first: a count the query asked for, the
        // sections already loaded with their own counts, and only failing both
        // a query of its own.
        if (isset($this->sections_with_quiz_count)) {
            $sectionsWithQuiz = (int) $this->sections_with_quiz_count;
        } elseif ($this->relationLoaded('sections')) {
            $sectionsWithQuiz = $this->sections->filter(
                fn ($section) => isset($section->questions_count)
                    ? $section->questions_count > 0
                    : $section->questions()->exists()
            )->count();
        } else {
            $sectionsWithQuiz = $this->sections()->whereHas('questions')->count();
        }

        return [
            'scope' => $sectionsWithQuiz > 0 ? 'sections' : 'none',

            // There is no book-level button in either case, and saying so beats
            // leaving the field out and making a client guess.
            'button' => ['visible' => false, 'label' => $this->t('quiz_button_label') ?: __('Take the quiz')],
            'sections_with_quiz' => $sectionsWithQuiz,
        ];
    }

    /** The reading programmes this book appears in, if any. */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class)->withPivot('order_index');
    }
}
