<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    /**
     * The notification feed: broadcasts plus anything addressed to this reader,
     * minus the categories they have muted.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $muted = $user->notificationPreferences()
            ->where('enabled', false)
            ->pluck('category')
            ->all();

        $announcements = Announcement::published()
            ->visibleTo($user)
            ->when($muted !== [], fn ($q) => $q->whereNotIn('category', $muted))
            ->with(['readers' => fn ($q) => $q->where('users.id', $user->id)])
            ->latest('published_at')
            ->get();

        foreach ($announcements as $announcement) {
            $announcement->readerRead = $announcement->readers->isNotEmpty();
        }

        return response()->json([
            'data' => AnnouncementResource::collection($announcements)->resolve(),
            'meta' => [
                'unread' => $announcements->where('readerRead', false)->count(),
            ],
        ]);
    }

    public function show(Request $request, Announcement $announcement): AnnouncementResource
    {
        $user = $request->user();

        abort_if(
            $announcement->user_id !== null && $announcement->user_id !== $user->id,
            403
        );
        abort_unless($announcement->published_at !== null && $announcement->published_at <= now(), 404);

        // Opening one marks it read.
        $announcement->readers()->syncWithoutDetaching([
            $user->id => ['read_at' => now()],
        ]);

        $announcement->readerRead = true;

        return new AnnouncementResource($announcement);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        $ids = Announcement::published()->visibleTo($user)->pluck('id');

        $user->readAnnouncements()->syncWithoutDetaching(
            $ids->mapWithKeys(fn ($id) => [$id => ['read_at' => now()]])->all()
        );

        return response()->json(['message' => __('All notifications marked as read.')]);
    }

    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $saved = $user->notificationPreferences()->pluck('enabled', 'category');

        $preferences = collect(NotificationPreference::CATEGORIES)->map(fn ($category) => [
            'category' => $category,
            'enabled' => (bool) ($saved[$category] ?? true),
        ]);

        return response()->json(['data' => $preferences]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(NotificationPreference::CATEGORIES)],
            'enabled' => ['required', 'boolean'],
        ]);

        $request->user()->notificationPreferences()->updateOrCreate(
            ['category' => $data['category']],
            ['enabled' => $data['enabled']]
        );

        return $this->preferences($request);
    }
}
