<?php

namespace App\Modules\Content\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Subject::examTypes() is the belongsToMany side, so pivot data lives on
        // each related ExamType ($this->examTypes->first()->pivot) — not on $this
        // directly, which was always null and silently swallowed by the ?? fallback.
        $pivot = $this->relationLoaded('examTypes') ? $this->examTypes->first()?->pivot : null;

        return [
            'id' => $this->id,
            'name' => $pivot->title ?? $this->name,
            'name_bn' => $pivot->title_bn ?? $this->name_bn,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'color_hex' => $this->color_hex,
            'total_marks' => $pivot->total_marks ?? null,
            'syllabus_note' => $pivot->syllabus_note ?? null,
            'user_mastery_percentage' => (float) ($this->user_mastery_percentage ?? 0.0),
            'total_lessons' => (int) ($this->total_lessons ?? 0),
            'completed_lessons' => (int) ($this->completed_lessons ?? 0),
            'sort_order' => $pivot->sort_order ?? 0,
        ];
    }
}
