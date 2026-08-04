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
            'exam_type_id' => ['sometimes', 'integer', 'exists:exam_types,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['sometimes', 'string', 'max:150'],
            'title_bn' => ['sometimes', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'total_marks' => ['nullable', 'integer'],
            'syllabus_note' => ['nullable', 'string'],
        ];
    }
}
