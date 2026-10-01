<?php

namespace App\Modules\Content\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Expects `status` ('locked' | 'current' | 'completed') to have been set
     * on the model instance by TopicService before this resource runs.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_type_subject_id' => $this->exam_type_subject_id,
            'name' => $this->name,
            'name_bn' => $this->name_bn,
            'sort_order' => $this->sort_order,
            'question_count' => (int) ($this->questions_count ?? 0),
            'status' => $this->status ?? 'locked',
        ];
    }
}
