<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'screen' => $this->screen,
            'image' => $this->image,
            'caption' => $this->t('caption'),
            'href' => $this->href,
            'position' => $this->position,
        ];
    }
}
