<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\ExamTypeSubject;

class StoreExamTypeSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
            'subject_id'   => ['required', 'integer', 'exists:subjects,id'],
            'title'        => ['required', 'string', 'max:150'],
            'title_bn'     => ['nullable', 'string', 'max:150'],
            'is_active'    => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $examTypeId = $this->input('exam_type_id');
            $subjectId  = $this->input('subject_id');
            $title      = $this->input('title');

            if (!$examTypeId || !$subjectId || !$title) {
                return; // base rules handle required errors
            }

            // Check (subject_id, exam_type_id) uniqueness
            $mappingExists = ExamTypeSubject::where('exam_type_id', $examTypeId)
                ->where('subject_id', $subjectId)
                ->exists();

            if ($mappingExists) {
                $validator->errors()->add(
                    'subject_id',
                    'This subject is already mapped under the selected Exam Type.'
                );
            }

            // Check (exam_type_id, title) uniqueness
            $titleExists = ExamTypeSubject::where('exam_type_id', $examTypeId)
                ->whereRaw('LOWER(TRIM(title)) = ?', [mb_strtolower(trim($title))])
                ->exists();

            if ($titleExists) {
                $validator->errors()->add(
                    'title',
                    "Another Exam Subject already uses the title \"{$title}\" under this Exam Type."
                );
            }
        });
    }
}
