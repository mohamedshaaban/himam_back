<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookSection extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['book_id', 'title', 'body', 'pdf', 'position'];

    public array $translatable = ['title', 'body', 'pdf'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'body' => 'array',
            'pdf' => 'array',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * The questions still belonging to this section.
     *
     * A question the book has claimed keeps its book_section_id, so the move
     * can be undone and so the record of where it was written survives. The
     * whereNull keeps it out of the section's own list in the meantime —
     * otherwise it would appear in two places at once in the editor.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)
            ->whereNull('book_id')
            ->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }
}
