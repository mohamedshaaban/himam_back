<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Support\Media;

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

            // Whether this section has an exam a reader can actually sit.
            //
            // Its own questions are not enough: once the book has an exam of
            // its own, the section endpoints answer 404 whatever the section
            // holds. Reporting true there would have the client render a
            // button that leads nowhere.
            'has_quiz' => $this->whenCounted(
                'questions',
                fn () => $this->questions_count > 0 && ! ($this->bookHasOwnQuiz ?? false)
            ),

            // The section text is large, so it ships only from the "read"
            // endpoint, which loads it deliberately.
            'body' => $this->when($request->routeIs('*sections.show'), fn () => $this->t('body')),

            // Sent with the list too: the book screen can mark which sections
            // are a document before the reader opens one.
            'pdf' => Media::url($this->t('pdf')),

            'read' => $this->when(isset($this->readerRead), fn () => (bool) $this->readerRead),
            'passed' => $this->when(isset($this->readerPassed), fn () => (bool) $this->readerPassed),
        ];
    }
}
