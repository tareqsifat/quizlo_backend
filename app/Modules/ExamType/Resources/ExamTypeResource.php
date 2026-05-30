<?php

namespace App\Modules\ExamType\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'] ?? $this->id,
            'name' => $this['name'] ?? $this->name,
            'name_bn' => $this['name_bn'] ?? $this->name_bn,
            'code' => $this['code'] ?? $this->code,
            'slug' => $this['slug'] ?? $this->slug,
            'description' => $this['description'] ?? $this->description,
            'icon' => $this['icon'] ?? $this->icon,
            'sort_order' => $this['sort_order'] ?? $this->sort_order,
        ];
    }
}
