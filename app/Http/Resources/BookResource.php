<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Support\Media;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->t('title'),
            'author' => $this->t('author'),
            'description' => $this->t('description'),
            'cover' => Media::url($this->cover),

            // How this book is read. A client needs to know before it decides
            // whether to show a sections list or a document and one quiz.
            'pdf' => Media::url($this->t('pdf')),
            'has_own_quiz' => $this->hasOwnQuiz(),

            // Whether to offer the exam, and what the button says, are set in
            // the dashboard — the client renders what it is told rather than
            // deciding for itself.
            'quiz_button' => $this->quizButton(),

            // Where the exam lives, so a client is told rather than left to
            // infer it from section counts and a hidden button.
            'exam' => $this->examSummary(),
            'pages' => $this->pages,
            'points' => $this->points,
            'position' => $this->position,
            'level' => new LevelResource($this->whenLoaded('level')),
            'sections_count' => $this->whenCounted('sections'),
            'sections' => SectionResource::collection($this->whenLoaded('sections')),

            // Present only on the authenticated book/index endpoints, where the
            // controller has resolved this reader's progress.
            'progress' => $this->when(isset($this->readerProgress), fn () => $this->readerProgress),
        ];
    }
}
