<?php

namespace App\Modules\ExamType\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamTypeSubjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_bn' => $this->name_bn,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'color_hex' => $this->color_hex,
            'total_marks' => $this->pivot->total_marks ?? null,
            'syllabus_note' => $this->pivot->syllabus_note ?? null,
            'sort_order' => $this->pivot->sort_order ?? 0,
        ];
    }
}
