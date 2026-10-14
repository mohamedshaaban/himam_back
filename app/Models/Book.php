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
    ];

    public array $translatable = ['title', 'author', 'description', 'pdf'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'author' => 'array',
            'description' => 'array',
            'pdf' => 'array',
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

    /** The reading programmes this book appears in, if any. */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class)->withPivot('order_index');
    }
}
