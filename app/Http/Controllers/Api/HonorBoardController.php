<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $rows = $users->values()->map(fn (User $user, int $index) => [
            'rank' => $index + 1,
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
            'level' => $user->level?->t('name'),
            'books' => $this->booksCompleted($user),
            'points' => $scope === 'all'
                ? (int) $user->points
                : (int) ($user->scoped_points ?? 0),
        ]);

        return response()->json([
            'data' => $rows,
            'meta' => ['scope' => $scope],
        ]);
    }

    private function booksCompleted(User $user): int
    {
        $passed = $user->passedSectionIds();

        return \App\Models\Book::published()
            ->with('sections:id,book_id')
            ->get()
            ->filter(function ($book) use ($passed) {
                $ids = $book->sections->pluck('id');

                return $ids->isNotEmpty() && $ids->diff($passed)->isEmpty();
            })
            ->count();
    }
}
