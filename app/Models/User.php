<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /** The one registration field with a fixed set of answers. */
    public const GENDERS = ['male', 'female'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'gender',
        'city',
        'country',
        'accepts_email',
        'avatar',
        'role',
        'locale',
        'points',
        'level_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'points' => 'integer',
            'accepts_email' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class)->withPivot('awarded_at')->withTimestamps();
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    /**
     * Announcements addressed to this reader specifically. The feed also
     * includes broadcast announcements, which are resolved in the controller.
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function readAnnouncements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class)->withPivot('read_at')->withTimestamps();
    }

    /**
     * Sections this reader has passed the quiz for.
     */
    /** Selective programmes an administrator has assigned to this reader. */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class)->withPivot(['assigned_by', 'assigned_at']);
    }

    public function passedSectionIds(): array
    {
        return $this->progress()
            ->whereNotNull('passed_at')
            ->pluck('book_section_id')
            ->all();
    }
}
