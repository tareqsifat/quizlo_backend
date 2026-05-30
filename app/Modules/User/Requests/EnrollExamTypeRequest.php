<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrollExamTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type_id'  => ['required', 'integer', 'exists:exam_types,id'],
            'is_primary'    => ['sometimes', 'boolean'],
            'target_year'   => ['nullable', 'integer', 'min:2024', 'max:2035'],
        ];
    }
}
