<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\ProgramAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function __construct(private readonly ProgramAccess $access)
    {
    }

    /**
     * Programmes this reader may see, each with how far through it they are.
     *
     * Public, so the landing screen can show the general programmes before
     * anyone signs in; a selective programme simply never appears for a reader
     * it was not assigned to.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $programs = $this->access->visible($user)->map(function (Program $program) use ($user) {
            $books = $this->access->books($program, $user);

            return [
                'id' => $program->id,
                'title' => $program->t('title'),
                'description' => $program->t('description'),
                'type' => $program->type,
                'cover' => $program->cover,
                'books_count' => count($books),
                'books_completed' => count(array_filter($books, fn ($row) => $row['completed'])),
                'percent' => $this->percent($books),
            ];
        });

        return response()->json(['data' => $programs]);
    }

    /**
     * One programme with its books in order, each flagged open or locked.
     *
     * The lock carries the book that is blocking it, so the screen can say
     * "finish X first" rather than only showing a padlock.
     */
    public function show(Request $request, Program $program): JsonResponse
    {
        $user = $request->user();

        $visible = $this->access->visible($user)->contains('id', $program->id);

        // 404 rather than 403: a reader who was not assigned a selective
        // programme should not learn that it exists.
        abort_unless($visible, 404);

        $rows = $this->access->books($program, $user);

        $books = collect($rows)->map(fn (array $row) => [
            'id' => $row['book']->id,
            'title' => $row['book']->t('title'),
            'author' => $row['book']->t('author'),
            'cover' => $row['book']->cover,
            'points' => $row['book']->points,
            'order_index' => $row['book']->pivot->order_index ?? 0,
            'unlocked' => $row['unlocked'],
            'status' => $row['status'],
            'blocked_by' => $row['blocked_by'],
            'completed' => $row['completed'],
            'sections_passed' => $row['sections_passed'],
            'sections_total' => $row['sections_total'],
        ]);

        return response()->json([
            'data' => [
                'id' => $program->id,
                'title' => $program->t('title'),
                'description' => $program->t('description'),
                'type' => $program->type,
                'cover' => $program->cover,
                'books' => $books,
                'books_completed' => $books->where('completed', true)->count(),
                'percent' => $this->percent($rows),
            ],
        ]);
    }

    /**
     * Percentage by sections rather than by books, so a long book in progress
     * registers as progress instead of as nothing.
     */
    private function percent(array $rows): int
    {
        $total = array_sum(array_column($rows, 'sections_total'));
        $done = array_sum(array_column($rows, 'sections_passed'));

        return $total > 0 ? (int) round($done / $total * 100) : 0;
    }
}
