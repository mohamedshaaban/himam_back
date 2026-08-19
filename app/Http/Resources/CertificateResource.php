<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'serial' => $this->serial,
            'title' => $this->t('title'),
            'issued_at' => $this->issued_at?->toDateString(),
            'level' => new LevelResource($this->whenLoaded('level')),
            'book' => new BookResource($this->whenLoaded('book')),
            'holder' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),

            // Encoded into the QR on the certificate card.
            'verification_url' => url("/api/certificates/verify/{$this->verification_code}"),
        ];
    }
}
