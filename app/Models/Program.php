<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A reading programme: an ordered container of books with its own audience.
 *
 * The type decides two separate things — who may see the programme, and in what
 * order its books may be opened — and they do not vary together, which is why
 * both questions are answered here rather than by one flag.
 */
class Program extends Model
{
    use HasFactory, HasTranslations;

    public const GENERAL = 'general';
    public const SEQUENTIAL = 'sequential';
    public const SELECTIVE = 'selective';

    public const TYPES = [self::GENERAL, self::SEQUENTIAL, self::SELECTIVE];

    /** How a reader came to be in a programme. */
    public const ASSIGNED = 'assigned';
    public const SELF = 'self';

    protected $fillable = [
        'title', 'description', 'type', 'is_public',
        'is_active', 'position', 'cover', 'created_by',
    ];

    public array $translatable = ['title', 'description'];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class)
            ->withPivot('order_index')
            ->withTimestamps()
            ->orderBy('book_program.order_index');
    }

    /** Everyone in this programme, however they got there. */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['source', 'assigned_by', 'assigned_at', 'enrolled_at'])
            ->withTimestamps();
    }

    /** Readers an administrator put here. This is what a selective programme
     *  checks: a self-enrolment must never grant sight of a hidden programme. */
    public function assignees(): BelongsToMany
    {
        return $this->members()->wherePivot('source', self::ASSIGNED);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isSequential(): bool
    {
        return $this->type === self::SEQUENTIAL;
    }

    public function isSelective(): bool
    {
        return $this->type === self::SELECTIVE;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The programmes a reader may see: every public one, plus the selective
     * ones assigned to them.
     *
     * A selective programme is hidden rather than locked — the point of it is
     * that the people it was not meant for never learn it exists.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->active()->where(function (Builder $q) use ($user) {
            $q->where(fn (Builder $inner) => $inner
                ->where('is_public', true)
                ->where('type', '!=', self::SELECTIVE));

            if ($user) {
                $q->orWhereHas(
                    'assignees',
                    fn (Builder $m) => $m->where('users.id', $user->id)
                );
            }
        });
    }

    /**
     * Selective programmes are never public and general ones always are, so the
     * flag is only the administrator's to set on a sequential programme.
     */
    public function settleVisibility(): void
    {
        $settled = match ($this->type) {
            self::SELECTIVE => false,
            self::GENERAL => true,
            default => $this->is_public,
        };

        if ($this->is_public !== $settled) {
            $this->forceFill(['is_public' => $settled])->save();
        }
    }
}
