<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BadgeResource;
use App\Models\Badge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BadgeController extends Controller
{
    /**
     * All active badges, each flagged with whether this reader has it. Locked
     * badges stay visible on purpose — the design shows them greyed out.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $badges = Badge::where('is_active', true)->orderBy('position')->get();
        $earned = $request->user()?->badges()->pluck('badges.id')->all() ?? [];

        foreach ($badges as $badge) {
            $badge->readerEarned = in_array($badge->id, $earned, true);
        }

        return BadgeResource::collection($badges);
    }
}
