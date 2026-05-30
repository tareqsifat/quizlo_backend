<?php

namespace App\Modules\Content\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_type_id' => $this->exam_type_id,
            'subject_id' => $this->subject_id,
            'topic_id' => $this->topic_id,
            'title' => $this->title,
            'title_bn' => $this->title_bn,
            'description' => $this->description,
            'xp_reward' => $this->xp_reward,
            'coin_reward' => $this->coin_reward,
            'difficulty' => $this->difficulty,
            'question_count' => $this->question_count,
            'sort_order' => $this->sort_order,
        ];
    }
}
