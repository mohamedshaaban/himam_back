<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['slug', 'title', 'body', 'is_published'];

    public array $translatable = ['title', 'body'];

    protected function casts(): array
    {
        return ['title' => 'array', 'body' => 'array', 'is_published' => 'boolean'];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
