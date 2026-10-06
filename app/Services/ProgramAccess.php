<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Decides what a reader may open, and says why when the answer is no.
 *
 * The specification is explicit that the lock cannot live in the interface
 * alone, so every reading endpoint asks this service rather than trusting the
 * screen that sent the request.
 */
class ProgramAccess
{
    public const OPEN = 'open';
    public const LOCKED_PREVIOUS = 'locked_previous';
    public const NOT_ASSIGNED = 'not_assigned';

    /**
     * The programmes this reader may see.
     *
     * @return Collection<int, Program>
     */
    public function visible(?User $user): Collection
    {
        return Program::query()
            ->visibleTo($user)
            ->with(['books' => fn ($q) => $q->published()->with('sections:id,book_id')])
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * Books in one programme, each with whether it is open and what is blocking
     * it. Order follows the programme's own order_index.
     *
     * @return array<int, array{book: Book, unlocked: bool, status: string, reason: ?int, completed: bool, sections_passed: int, sections_total: int}>
     */
    public function books(Program $program, ?User $user): array
    {
        $passed = $user ? $user->passedSectionIds() : [];
        $books = $program->relationLoaded('books')
            ? $program->books
            : $program->books()->published()->with('sections:id,book_id')->get();

        $rows = [];
        $previousComplete = true;      // The first book is open by definition.
        $previousBookId = null;

        foreach ($books as $book) {
            $sectionIds = $book->sections->pluck('id');
            $total = $sectionIds->count();
            $done = $sectionIds->intersect($passed)->count();

            // A book with no sections cannot be "completed" by reading, so it
            // must not become a wall the rest of the programme sits behind.
            $completed = $total > 0 && $done === $total;

            // A book the reader has already finished stays open even if the
            // one now before it has not been. Reordering a programme must not
            // take back what someone has already read — and showing a locked
            // padlock on a book they completed would be telling them their own
            // history is out of reach.
            $unlocked = ! $program->isSequential() || $previousComplete || $completed;

            $rows[] = [
                'book' => $book,
                'unlocked' => $unlocked,
                'status' => $this->status($unlocked, $completed, $done),
                'blocked_by' => $unlocked ? null : $previousBookId,
                'completed' => $completed,
                'sections_passed' => $done,
                'sections_total' => $total,
            ];

            if ($program->isSequential()) {
                $previousComplete = $completed || $total === 0;
                $previousBookId = $book->id;
            }
        }

        return $rows;
    }

    /**
     * Whether a reader may open this book at all.
     *
     * A book can sit in several programmes at once, so one grant is enough: a
     * book locked in a sequential programme is still readable if a general
     * programme also offers it. The alternative — the strictest programme
     * winning — would let adding a book to one programme silently withdraw it
     * from readers of another.
     *
     * Books in no programme are unaffected, so the existing catalogue keeps
     * working exactly as it did.
     */
    public function canRead(?User $user, Book $book): bool
    {
        $programIds = $book->programs()->pluck('programs.id');

        if ($programIds->isEmpty()) {
            return true;
        }

        foreach ($this->visible($user) as $program) {
            foreach ($this->books($program, $user) as $row) {
                if ($row['book']->id === $book->id && $row['unlocked']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Why a book is closed, phrased for the reader rather than for the log.
     */
    public function reason(?User $user, Book $book): string
    {
        $programIds = $book->programs()->pluck('programs.id');

        if ($programIds->isEmpty()) {
            return self::OPEN;
        }

        $seen = $this->visible($user)->pluck('id');

        // In no visible programme at all: this is a selective programme the
        // reader was not assigned to.
        if ($programIds->intersect($seen)->isEmpty()) {
            return self::NOT_ASSIGNED;
        }

        return self::LOCKED_PREVIOUS;
    }

    private function status(bool $unlocked, bool $completed, int $done): string
    {
        if (! $unlocked) {
            return 'locked';
        }

        if ($completed) {
            return 'completed';
        }

        return $done > 0 ? 'in_progress' : 'available';
    }
}
