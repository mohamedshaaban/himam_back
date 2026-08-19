<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'category', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    /**
     * The categories a reader can mute, in the order the account screen shows
     * them. Kept here so the API and the seeder agree on one list.
     */
    public const CATEGORIES = ['program', 'exam', 'results', 'honor', 'general'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
