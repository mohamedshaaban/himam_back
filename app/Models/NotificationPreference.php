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
    // 'certificate' and 'badge' are listed here as well as in the admin's own
    // set, so a reader can mute the automatic notices like any other kind.
    // The feed filters on these; a category absent from the list can never be
    // turned off.
    public const CATEGORIES = ['program', 'exam', 'results', 'honor', 'certificate', 'badge', 'general'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
