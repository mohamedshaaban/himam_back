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

    public function hasOwnQuiz(): bool
    {
        return $this->questions()->exists();
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

    /** The reading programmes this book appears in, if any. */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class)->withPivot('order_index');
    }
}
