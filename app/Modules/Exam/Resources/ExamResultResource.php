<?php

namespace App\Modules\Exam\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'exam_type_id' => $this->exam_type_id,
            'model_test_id' => $this->model_test_id,
            'session_type' => $this->session_type,
            'status' => $this->status,
            'total_questions' => $this->total_questions,
            'answered_count' => $this->answered_count,
            'correct_count' => $this->correct_count,
            'score_percent' => $this->score_percent,
            'xp_earned' => $this->xp_earned,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'model_test' => $this->whenLoaded('modelTest'),
        ];
    }
}
