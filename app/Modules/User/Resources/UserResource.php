<?php

namespace App\Modules\User\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'district' => $this->district,
            'division' => $this->division,
            'daily_goal' => $this->daily_goal,
            'first_session_completed' => $this->first_session_completed,
            'is_active' => $this->is_active,
            'exam_types' => $this->whenLoaded('examTypes', function () {
                return $this->examTypes->map(function ($examType) {
                    return [
                        'id' => $examType->id,
                        'name' => $examType->name,
                        'name_bn' => $examType->name_bn,
                        'code' => $examType->code,
                        'slug' => $examType->slug,
                        'is_primary' => (bool) $examType->pivot->is_primary,
                        'target_year' => $examType->pivot->target_year,
                    ];
                });
            }),
        ];
    }
}
