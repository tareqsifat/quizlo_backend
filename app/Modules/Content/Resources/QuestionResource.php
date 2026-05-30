<?php

namespace App\Modules\Content\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_id' => $this->subject_id,
            'topic_id' => $this->topic_id,
            'lesson_id' => $this->lesson_id,
            'question_text' => $this->question_text,
            'question_bn' => $this->question_bn,
            'explanation' => $this->explanation,
            'difficulty' => $this->difficulty,
            'xp_value' => $this->xp_value,
            'options' => $this->options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'option_text_bn' => $option->option_text_bn,
                    'sort_order' => $option->sort_order,
                ];
            }),
        ];
    }
}
