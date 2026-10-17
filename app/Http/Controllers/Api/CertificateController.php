<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CertificateController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return CertificateResource::collection(
            $request->user()->certificates()->with(['level', 'book'])->latest('issued_at')->get()
        );
    }

    /**
     * Every certificate the programme can issue, earned or not.
     *
     * This backs the "all" tab next to "earned": a reader should be able to see
     * what is still ahead of them, with how much of it is done, not just what
     * they already hold.
     */
    public function available(Request $request): JsonResponse
    {
        $user = $request->user();
        $read = $user->readSectionIds();
        $passedBooks = $user->passedBookIds();

        $heldLevels = $user->certificates()->whereNotNull('level_id')->get()->keyBy('level_id');
        $heldBooks = $user->certificates()->whereNotNull('book_id')->get()->keyBy('book_id');

        // One certificate per book, earned on passing that book's exam, and one
        // per level on finishing all of its books. Both are listed here, each
        // saying which it is, so a screen can show them together or filter.
        $books = \App\Models\Book::published()
            ->with('sections:id,book_id')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $bookRows = $books->map(function (\App\Models\Book $book) use ($read, $passedBooks, $heldBooks) {
            $sectionIds = $book->sections->pluck('id');
            $total = $sectionIds->count();
            $done = $sectionIds->intersect($read)->count();

            // A book examined as a whole has no sections to count, so it is one
            // step: taken or not.
            if ($total === 0) {
                $total = 1;
                $done = in_array($book->id, $passedBooks, true) ? 1 : 0;
            }

            return $this->row(
                $heldBooks->get($book->id),
                'book',
                $book->t('title'),
                ['book' => ['id' => $book->id, 'title' => $book->t('title')], 'level' => null],
                $done,
                $total
            );
        });

        $levels = \App\Models\Level::query()
            ->where('is_active', true)
            ->with(['books' => fn ($q) => $q->where('is_published', true), 'books.sections:id,book_id'])
            ->orderBy('position')
            ->get();

        $levelRows = $levels->map(function ($level) use ($read, $passedBooks, $heldLevels) {
            $books = $level->books;
            $sectionIds = $books->flatMap->sections->pluck('id');

            $done = $sectionIds->intersect($read)->count();
            $total = $sectionIds->count();

            // Books examined as a whole contribute one step each, the same way
            // they do towards the certificate itself.
            foreach ($books->filter(fn ($book) => $book->sections->isEmpty()) as $book) {
                $total++;
                $done += in_array($book->id, $passedBooks, true) ? 1 : 0;
            }

            return $this->row(
                $heldLevels->get($level->id),
                'level',
                $level->t('name'),
                ['book' => null, 'level' => ['id' => $level->id, 'name' => $level->t('name')]],
                $done,
                $total
            );
        });

        return response()->json(['data' => $bookRows->concat($levelRows)->values()]);
    }

    /**
     * One row of the issuable list, held or not yet earned.
     */
    private function row(?Certificate $certificate, string $scope, ?string $title, array $subject, int $done, int $total): array
    {
        return [
            'scope' => $scope,
            ...$subject,
            'title' => $title,
            'earned' => (bool) $certificate,
            'image' => \App\Support\Media::url(
                $certificate?->image ?: \App\Console\Commands\PublishCertificateTemplate::PATH
            ),
            'serial' => $certificate?->serial,
            'issued_at' => $certificate?->issued_at?->toDateString(),
            'verification_url' => $certificate
                ? url("/api/certificates/verify/{$certificate->verification_code}")
                : null,
            'sections_read' => $done,
            'sections_total' => $total,
            'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }

    public function show(Request $request, Certificate $certificate): CertificateResource
    {
        abort_unless($certificate->user_id === $request->user()->id, 403);

        return new CertificateResource($certificate->load(['level', 'book', 'user']));
    }

    /**
     * Public endpoint behind the QR code. Returns only what a verifier needs to
     * confirm the certificate is genuine — no contact details.
     */
    public function verify(string $code): JsonResponse
    {
        $certificate = Certificate::with(['level', 'book', 'user'])
            ->where('verification_code', $code)
            ->first();

        if (! $certificate) {
            return response()->json([
                'valid' => false,
                'message' => __('No certificate matches this code.'),
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'serial' => $certificate->serial,
            'holder' => $certificate->user->name,
            'title' => $certificate->t('title'),
            'level' => $certificate->level?->t('name'),
            'issued_at' => $certificate->issued_at?->toDateString(),
        ]);
    }
}
