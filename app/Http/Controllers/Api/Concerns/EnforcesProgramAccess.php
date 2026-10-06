<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Book;
use App\Services\ProgramAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * The server half of the sequential lock.
 *
 * Every endpoint that hands over a book's content asks this first. A padlock
 * drawn in the app is a courtesy to the reader; it is not a control, because
 * the request can be made without the app.
 */
trait EnforcesProgramAccess
{
    protected function assertCanRead(Request $request, Book $book): void
    {
        $access = app(ProgramAccess::class);
        $user = $request->user();

        if ($access->canRead($user, $book)) {
            return;
        }

        $reason = $access->reason($user, $book);

        // A programme the reader was never assigned should read as absent
        // rather than as forbidden — 403 would confirm it exists.
        if ($reason === ProgramAccess::NOT_ASSIGNED) {
            abort(404);
        }

        throw new HttpResponseException(response()->json([
            'message' => __('Finish the previous book in this programme first.'),
            'reason' => $reason,
        ], 403));
    }
}
