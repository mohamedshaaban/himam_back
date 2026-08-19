<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'level_id', 'title', 'author', 'description',
        'cover', 'pages', 'points', 'position', 'is_published',
    ];

    public array $translatable = ['title', 'author', 'description'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'author' => 'array',
            'description' => 'array',
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
}
