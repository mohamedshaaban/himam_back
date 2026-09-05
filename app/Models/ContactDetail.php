<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactDetail extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'email', 'phone', 'whatsapp', 'website',
        'address', 'working_hours', 'note', 'social',
    ];

    public array $translatable = ['address', 'working_hours', 'note'];

    protected function casts(): array
    {
        return [
            'address' => 'array',
            'working_hours' => 'array',
            'note' => 'array',
            'social' => 'array',
        ];
    }

    /**
     * There is only ever one row; this keeps every caller from re-deciding what
     * "the contact details" means when the table is empty.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], []);
    }
}
