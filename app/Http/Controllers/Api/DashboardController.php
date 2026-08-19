<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BadgeResource;
use App\Http\Resources\CertificateResource;
use App\Models\Announcement;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly ProgressService $progress)
    {
    }

    /**
     * Everything the home screen renders, in one round trip.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $badges = $user->badges()->orderBy('position')->take(4)->get()
            ->each(fn ($badge) => $badge->readerEarned = true);

        $unread = Announcement::published()
            ->visibleTo($user)
            ->whereDoesntHave('readers', fn ($q) => $q->where('users.id', $user->id))
            ->count();

        return response()->json([
            'stats' => $this->progress->stats($user),
            'unread_announcements' => $unread,
            'badges' => BadgeResource::collection($badges),
            'certificates' => CertificateResource::collection(
                $user->certificates()->with('level')->latest('issued_at')->take(3)->get()
            ),
            'honor_board' => $this->topReaders(),
        ]);
    }

    /**
     * The five leaders shown in the home sidebar.
     */
    private function topReaders(): array
    {
        return User::query()
            ->where('role', 'student')
            ->with('level')
            ->orderByDesc('points')
            ->take(5)
            ->get()
            ->values()
            ->map(fn (User $user, int $index) => [
                'rank' => $index + 1,
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
                'points' => (int) $user->points,
                'level' => $user->level?->t('name'),
            ])
            ->all();
    }
}
