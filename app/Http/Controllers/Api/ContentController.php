<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactDetail;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Http\JsonResponse;

/**
 * The static-content endpoints: about, privacy, support and the FAQ.
 *
 * Public on purpose — a privacy policy or a support number that only signed-in
 * readers can reach is no use to someone deciding whether to sign up.
 */
class ContentController extends Controller
{
    /**
     * One content page by slug (about, privacy, or anything added later).
     */
    public function page(string $slug): JsonResponse
    {
        $page = Page::published()->where('slug', $slug)->first();

        if (! $page) {
            return response()->json(['message' => __('That page does not exist.')], 404);
        }

        return response()->json([
            'data' => [
                'slug' => $page->slug,
                'title' => $page->t('title'),
                'body' => $page->t('body'),
                'updated_at' => $page->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function faqs(): JsonResponse
    {
        $faqs = Faq::active()->orderBy('position')->get()->map(fn (Faq $faq) => [
            'id' => $faq->id,
            'question' => $faq->t('question'),
            'answer' => $faq->t('answer'),
            'category' => $faq->category,
        ]);

        return response()->json(['data' => $faqs]);
    }

    public function contact(): JsonResponse
    {
        $contact = ContactDetail::current();

        return response()->json([
            'data' => [
                'email' => $contact->email,
                'phone' => $contact->phone,
                'whatsapp' => $contact->whatsapp,
                'website' => $contact->website,
                'address' => $contact->t('address'),
                'working_hours' => $contact->t('working_hours'),
                'note' => $contact->t('note'),
                'social' => $contact->social ?? [],
            ],
        ]);
    }
}
