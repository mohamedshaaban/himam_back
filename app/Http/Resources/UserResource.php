<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'gender' => $this->gender,
            'age_band' => $this->age_band,
            'education_level' => $this->education_level,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'city' => $this->city,
            'country' => $this->country,
            'accepts_email' => (bool) $this->accepts_email,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'locale' => $this->locale,
            'points' => $this->points,
            'level' => new LevelResource($this->whenLoaded('level')),
            'badges_count' => $this->whenCounted('badges'),
            'certificates_count' => $this->whenCounted('certificates'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
