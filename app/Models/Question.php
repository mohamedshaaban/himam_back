<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['book_section_id', 'text', 'position'];

    public array $translatable = ['text'];

    protected function casts(): array
    {
        return ['text' => 'array'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(BookSection::class, 'book_section_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position');
    }

    public function correctOption(): ?QuestionOption
    {
        return $this->options->firstWhere('is_correct', true);
    }
}
