<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Reader-facing shape of a quiz question.
 *
 * `is_correct` is deliberately absent — the answer key never leaves the server
 * on this endpoint. Grading happens in QuizController::submit, which returns
 * the correct option ids only after the attempt has been recorded.
 */
class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->t('text'),
            'position' => $this->position,
            'options' => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'text' => $option->t('text'),
                'position' => $option->position,
            ])->values(),
        ];
    }
}
