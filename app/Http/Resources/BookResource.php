<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->t('title'),
            'author' => $this->t('author'),
            'description' => $this->t('description'),
            'cover' => $this->cover,
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
