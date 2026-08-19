<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Badge;
use App\Models\Book;
use App\Models\BookSection;
use App\Models\Certificate;
use App\Models\Level;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    /**
     * The admin overview cards plus a short activity feed.
     */
    public function __invoke(): JsonResponse
    {
        $attempts = QuizAttempt::query();

        return response()->json([
            'totals' => [
                'users' => User::where('role', 'student')->count(),
                'admins' => User::where('role', 'admin')->count(),
                'levels' => Level::count(),
                'books' => Book::count(),
                'sections' => BookSection::count(),
                'badges' => Badge::count(),
                'certificates' => Certificate::count(),
                'announcements' => Announcement::count(),
            ],
            'quizzes' => [
                'attempts' => (clone $attempts)->count(),
                'passed' => (clone $attempts)->where('passed', true)->count(),
                'attempts_this_month' => (clone $attempts)->where('created_at', '>=', now()->startOfMonth())->count(),
                'points_awarded' => (int) (clone $attempts)->sum('points_awarded'),
            ],
            'signups_last_7_days' => User::where('role', 'student')
                ->where('created_at', '>=', now()->subDays(7)->startOfDay())
                ->count(),
            'top_readers' => User::where('role', 'student')
                ->orderByDesc('points')
                ->take(5)
                ->get(['id', 'name', 'points', 'avatar']),
            'recent_attempts' => QuizAttempt::with(['user:id,name', 'section:id,book_id,title'])
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }
}
