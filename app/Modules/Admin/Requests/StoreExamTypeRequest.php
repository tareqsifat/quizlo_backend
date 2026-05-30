<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'name_bn' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'unique:exam_types,code'],
            'slug' => ['required', 'string', 'unique:exam_types,slug'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ];
    }
}
