<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HandlesTranslatableInput;
use App\Http\Controllers\Controller;
use App\Models\ContactDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A single row, so this is show/update rather than a resource — there is no
 * meaningful list of contact details, and nothing to create or delete.
 */
class ContactController extends Controller
{
    use HandlesTranslatableInput;

    public function show(): JsonResponse
    {
        return response()->json(['data' => ContactDetail::current()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            ...$this->translatableRules('address', false),
            ...$this->translatableRules('working_hours', false),
            ...$this->translatableRules('note', false),
            'social' => ['nullable', 'array'],
            'social.*.platform' => ['required', 'string', 'max:50'],
            'social.*.url' => ['required', 'url', 'max:255'],
        ]);

        $contact = ContactDetail::current();

        $contact->update($this->cleanTranslations($data, 'address', 'working_hours', 'note'));

        return response()->json(['data' => $contact->fresh()]);
    }
}
