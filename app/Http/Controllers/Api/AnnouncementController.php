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

        $this->assertVisible($announcement, $user);

        // Opening one marks it read.
        $announcement->readers()->syncWithoutDetaching([
            $user->id => ['read_at' => now()],
        ]);

        $announcement->readerRead = true;

        return new AnnouncementResource($announcement);
    }

    /**
     * Marks a single notification read.
     *
     * `show` already does this as a side effect of opening one, but relying on
     * that alone is fragile: a GET is the request most likely to be served from
     * a cache and never reach the server, and a reader who opens a notification
     * from a list they already hold has no reason to fetch the body again.
     * This gives the app something explicit to call at the moment it decides
     * the notification has been seen.
     *
     * Returns the new unread count so the bell can update without refetching
     * the whole feed.
     */
    public function markRead(Request $request, Announcement $announcement): JsonResponse
    {
        $user = $request->user();

        $this->assertVisible($announcement, $user);

        $announcement->readers()->syncWithoutDetaching([
            $user->id => ['read_at' => now()],
        ]);

        return response()->json([
            'data' => [
                'id' => $announcement->id,
                'read' => true,
            ],
            'meta' => [
                'unread' => $this->unreadCount($user),
            ],
        ]);
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

    /**
     * A reader may only touch a published notification, and a targeted one only
     * if it is addressed to them.
     */
    private function assertVisible(Announcement $announcement, $user): void
    {
        abort_if(
            $announcement->user_id !== null && $announcement->user_id !== $user->id,
            403
        );
        abort_unless($announcement->published_at !== null && $announcement->published_at <= now(), 404);
    }

    /**
     * Unread count for the bell, counted the same way the feed does — muted
     * categories are excluded, so the badge can never sit on a number the
     * reader has no way to clear.
     */
    private function unreadCount($user): int
    {
        $muted = $user->notificationPreferences()
            ->where('enabled', false)
            ->pluck('category')
            ->all();

        return Announcement::published()
            ->visibleTo($user)
            ->when($muted !== [], fn ($q) => $q->whereNotIn('category', $muted))
            ->whereDoesntHave('readers', fn ($q) => $q->where('users.id', $user->id))
            ->count();
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
