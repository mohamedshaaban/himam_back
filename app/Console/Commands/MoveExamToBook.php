<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Question;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Moves a book's examination from its sections to the book itself.
 *
 * The questions an editor has already written become the book's exam, in the
 * order they were read — section by section, then question by question. Nothing
 * is invented and nothing is discarded: a question keeps its book_section_id,
 * which is both the record of where it was written and what makes --revert
 * possible.
 *
 * Readers keep what they earned. Someone who had passed every section of a book
 * had finished it under the old rule, and the new rule reads completion off the
 * book's own exam — so their completion is carried across explicitly rather
 * than left to evaporate. Points are not touched, because they were credited
 * when those sections were passed.
 */
class MoveExamToBook extends Command
{
    protected $signature = 'himam:exam-on-book
        {--book=* : Book ids to act on; every published book when omitted}
        {--revert : Put the questions back on the sections they came from}
        {--dry-run : Report what would change and write nothing}';

    protected $description = "Move a book's exam from its sections to the book itself";

    public function handle(): int
    {
        $revert = (bool) $this->option('revert');
        $dry = (bool) $this->option('dry-run');

        $books = $this->books();

        if ($books->isEmpty()) {
            $this->warn('No books matched.');

            return self::SUCCESS;
        }

        if ($dry) {
            $this->line('<comment>Dry run — nothing will be written.</comment>');
            $this->newLine();
        }

        $rows = [];
        $changed = 0;

        foreach ($books as $book) {
            $result = $revert
                ? $this->revert($book, $dry)
                : $this->move($book, $dry);

            $rows[] = [$book->id, $this->titleOf($book), $result['questions'], $result['readers'], $result['note']];

            if ($result['questions'] > 0 || $result['readers'] > 0) {
                $changed++;
            }
        }

        $this->table(
            ['Book', 'Title', $revert ? 'Returned' : 'Moved', 'Readers kept', 'Note'],
            $rows
        );

        $this->info(sprintf(
            '%s %d of %d book(s).',
            $dry ? 'Would change' : 'Changed',
            $changed,
            $books->count()
        ));

        if (! $dry && ! $revert && $changed > 0) {
            $this->newLine();
            $this->line('The exam button appears on each changed book as soon as its');
            $this->line('questions are in place. To undo: <info>php artisan himam:exam-on-book --revert</info>');
        }

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Book>
     */
    private function books(): \Illuminate\Support\Collection
    {
        $ids = array_filter($this->option('book'));

        return Book::query()
            // Deliberately not published(): a book withdrawn with its level is
            // still a book, and it should come back with its exam in the right
            // place when the level is reinstated.
            ->when($ids !== [], fn ($q) => $q->whereIn('id', $ids), fn ($q) => $q->where('is_published', true))
            ->with(['sections:id,book_id'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{questions: int, readers: int, note: string}
     */
    private function move(Book $book, bool $dry): array
    {
        $sectionIds = $book->sections->pluck('id');

        if ($sectionIds->isEmpty()) {
            return ['questions' => 0, 'readers' => 0, 'note' => 'read as one document already'];
        }

        if (Question::where('book_id', $book->id)->exists()) {
            return ['questions' => 0, 'readers' => 0, 'note' => 'already examined as a whole'];
        }

        // Section order, then the order within each section: the exam reads in
        // the order the book does.
        // Both tables carry a book_id, so every column here is qualified.
        $questions = Question::query()
            ->whereIn('questions.book_section_id', $sectionIds)
            ->whereNull('questions.book_id')
            ->join('book_sections', 'book_sections.id', '=', 'questions.book_section_id')
            ->orderBy('book_sections.position')
            ->orderBy('questions.position')
            ->select('questions.*')
            ->get();

        if ($questions->isEmpty()) {
            return ['questions' => 0, 'readers' => 0, 'note' => 'no questions to move'];
        }

        $finishers = $this->readersWhoFinished($book, $sectionIds);

        if ($dry) {
            return [
                'questions' => $questions->count(),
                'readers' => $finishers->count(),
                'note' => 'would move',
            ];
        }

        DB::transaction(function () use ($questions, $book, $finishers) {
            foreach ($questions->values() as $index => $question) {
                $question->forceFill([
                    'book_id' => $book->id,
                    'position' => $index + 1,
                ])->save();
            }

            $this->carryCompletionAcross($book, $finishers);
        });

        return [
            'questions' => $questions->count(),
            'readers' => $finishers->count(),
            'note' => 'moved',
        ];
    }

    /**
     * @return array{questions: int, readers: int, note: string}
     */
    private function revert(Book $book, bool $dry): array
    {
        // Only questions that remember a section can go back to one. A question
        // written for the book directly has nowhere to return to, and moving it
        // to some section would be inventing editorial intent.
        $questions = Question::where('book_id', $book->id)
            ->whereNotNull('book_section_id')
            ->get();

        $orphans = Question::where('book_id', $book->id)
            ->whereNull('book_section_id')
            ->count();

        if ($questions->isEmpty()) {
            return [
                'questions' => 0,
                'readers' => 0,
                'note' => $orphans > 0
                    ? "{$orphans} written for the book; left alone"
                    : 'nothing to return',
            ];
        }

        if ($dry) {
            return ['questions' => $questions->count(), 'readers' => 0, 'note' => 'would return'];
        }

        DB::transaction(function () use ($questions) {
            foreach ($questions->groupBy('book_section_id') as $inSection) {
                foreach ($inSection->values() as $index => $question) {
                    $question->forceFill([
                        'book_id' => null,
                        'position' => $index + 1,
                    ])->save();
                }
            }
        });

        return [
            'questions' => $questions->count(),
            'readers' => 0,
            'note' => $orphans > 0 ? "returned; {$orphans} left on the book" : 'returned',
        ];
    }

    /**
     * Readers who had passed every section of this book, and so had finished it
     * under the rule in force before the move.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function readersWhoFinished(Book $book, \Illuminate\Support\Collection $sectionIds): \Illuminate\Support\Collection
    {
        return ReadingProgress::query()
            ->whereIn('book_section_id', $sectionIds)
            ->whereNotNull('passed_at')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT book_section_id) = ?', [$sectionIds->count()])
            ->pluck('user_id');
    }

    /**
     * Marks the book itself passed for readers who had already finished it.
     *
     * Written straight to the progress row rather than through the grading
     * service: they have sat no book exam, and the points for this material
     * were credited section by section when they passed those. Paying again
     * would reward them twice for one book.
     */
    private function carryCompletionAcross(Book $book, \Illuminate\Support\Collection $userIds): void
    {
        foreach ($userIds as $userId) {
            $progress = ReadingProgress::firstOrNew([
                'user_id' => $userId,
                'book_id' => $book->id,
            ]);

            $progress->read_at ??= now();
            $progress->passed_at ??= now();
            $progress->save();
        }
    }

    private function titleOf(Book $book): string
    {
        $title = $book->t('title') ?? '';

        return mb_strlen($title) > 34 ? mb_substr($title, 0, 33).'…' : $title;
    }
}
