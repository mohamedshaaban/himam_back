<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\Media;

class HonorBoardController extends Controller
{
    /**
     * The leaderboard, ranked by points.
     *
     * `scope` narrows it to points earned this month or this year by summing
     * the attempts in that window, rather than the lifetime total on the user
     * row — otherwise "monthly" would just repeat the all-time board.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $scope = $request->query('scope', 'all');
        $limit = min($request->integer('limit', 20) ?: 20, 100);

        $query = User::query()->where('role', 'student')->with('level');

        if (in_array($scope, ['month', 'year'], true)) {
            $since = $scope === 'month' ? now()->startOfMonth() : now()->startOfYear();

            $query->withSum(
                ['quizAttempts as scoped_points' => fn ($q) => $q->where('created_at', '>=', $since)],
                'points_awarded'
            )->orderByDesc('scoped_points');
        } else {
            $query->orderByDesc('points');
        }

        $users = $query->take($limit)->get();

        // The catalogue is the same for every reader on the board, so it is
        // read once here rather than rebuilt inside booksCompleted() for each
        // of them — which is what made this endpoint fifty-one queries.
        $books = \App\Models\Book::published()
            ->with('sections:id,book_id')
            ->withCount('questions')
            ->get();

        // And every reader's passes in one query rather than two apiece. A
        // board of twenty readers was asking the progress table forty times
        // for rows one query returns.
        $passesByUser = \App\Models\ReadingProgress::whereIn('user_id', $users->pluck('id'))
            ->whereNotNull('passed_at')
            ->get(['user_id', 'book_section_id', 'book_id'])
            ->groupBy('user_id');

        $rows = $users->values()->map(fn (User $user, int $index) => [
            'rank' => $index + 1,
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => Media::url($user->avatar),
            'level' => $user->level?->t('name'),
            'books' => $this->booksCompleted($user, $books, $passesByUser->get($user->id, collect())),
            'points' => $scope === 'all'
                ? (int) $user->points
                : (int) ($user->scoped_points ?? 0),
        ]);

        return response()->json([
            'data' => $rows,
            'meta' => ['scope' => $scope],
        ]);
    }

    private function booksCompleted(User $user, \Illuminate\Support\Collection $books, \Illuminate\Support\Collection $passes): int
    {
        $passed = $passes->whereNotNull('book_section_id')->pluck('book_section_id')->all();
        $passedBooks = $passes->whereNotNull('book_id')->pluck('book_id')->all();

        return $books->filter(fn ($book) => $book->completedBy($passed, $passedBooks))->count();
    }
}
