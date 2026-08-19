<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = ['screen', 'image', 'caption', 'href', 'position', 'is_active'];

    public array $translatable = ['caption'];

    protected function casts(): array
    {
        return [
            'caption' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
