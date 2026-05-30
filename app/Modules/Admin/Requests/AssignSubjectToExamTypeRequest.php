<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignSubjectToExamTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'total_marks' => ['nullable', 'integer'],
            'syllabus_note' => ['nullable', 'string'],
        ];
    }
}
