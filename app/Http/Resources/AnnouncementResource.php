<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tag' => $this->t('tag'),
            'title' => $this->t('title'),
            'body' => $this->t('body'),
            'image' => $this->image,
            'category' => $this->category,
            'published_at' => $this->published_at?->toIso8601String(),
            'read' => $this->when(isset($this->readerRead), fn () => (bool) $this->readerRead),
        ];
    }
}
