<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BadgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->t('name'),
            'description' => $this->t('description'),
            'image' => $this->image,
            'criteria_type' => $this->criteria_type,
            'criteria_value' => $this->criteria_value,
            'position' => $this->position,

            // The badges screen shows locked badges greyed out as motivation,
            // so every badge ships with an earned flag rather than being hidden.
            'earned' => $this->when(isset($this->readerEarned), fn () => (bool) $this->readerEarned),
            'awarded_at' => $this->whenPivotLoaded('badge_user', fn () => $this->pivot->awarded_at),
        ];
    }
}
