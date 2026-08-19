<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private readonly ProgressService $progress)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('level')
            ->withCount(['badges', 'certificates'])
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->orderByDesc('points')
            ->paginate($request->integer('per_page', 25) ?: 25);

        return response()->json(UserResource::collection($users)->response()->getData(true));
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user->load(['level', 'badges', 'certificates.level'])),
            'stats' => $this->progress->stats($user),
            'attempts' => $user->quizAttempts()
                ->with('section:id,book_id,title')
                ->latest()
                ->take(20)
                ->get(),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:120'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'role' => ['sometimes', Rule::in(['student', 'admin'])],
            'locale' => ['sometimes', Rule::in(array_keys(config('himam.locales')))],
            'level_id' => ['nullable', 'exists:levels,id'],
            'points' => ['sometimes', 'integer', 'min:0'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        // Removing your own admin rights would lock you out of this dashboard
        // mid-session, so it has to be done by another administrator.
        if (
            isset($data['role'])
            && $data['role'] !== 'admin'
            && $user->id === $request->user()->id
        ) {
            return response()->json([
                'message' => __('You cannot remove your own administrator role.'),
            ], 422);
        }

        $user->update(array_filter(
            $data,
            fn ($value, $key) => $key !== 'password' || filled($value),
            ARRAY_FILTER_USE_BOTH
        ));

        return response()->json(['data' => new UserResource($user->fresh()->load('level'))]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => __('You cannot delete your own account.')], 422);
        }

        $user->delete();

        return response()->json(['message' => __('User deleted.')]);
    }

    /**
     * Re-runs badge and certificate rules for one reader. Useful after content
     * changes — for example when a level gains a book that readers had already
     * completed under the old structure.
     */
    public function recalculate(User $user): JsonResponse
    {
        return response()->json([
            'new_badges' => $this->progress->syncBadges($user),
            'new_certificates' => $this->progress->issueEarnedCertificates($user),
            'stats' => $this->progress->stats($user->refresh()),
        ]);
    }
}
