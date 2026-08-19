<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'book_id' => $this->book_id,
            'title' => $this->t('title'),
            'position' => $this->position,
            'questions_count' => $this->whenCounted('questions'),

            // The section text is large, so it ships only from the "read"
            // endpoint, which loads it deliberately.
            'body' => $this->when($request->routeIs('*sections.show'), fn () => $this->t('body')),

            'read' => $this->when(isset($this->readerRead), fn () => (bool) $this->readerRead),
            'passed' => $this->when(isset($this->readerPassed), fn () => (bool) $this->readerPassed),
        ];
    }
}
