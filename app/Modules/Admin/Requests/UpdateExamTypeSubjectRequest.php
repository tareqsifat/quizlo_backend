<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\ExamTypeSubject;

class UpdateExamTypeSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type_id' => ['sometimes', 'integer', 'exists:exam_types,id'],
            'subject_id'   => ['sometimes', 'integer', 'exists:subjects,id'],
            'title'        => ['sometimes', 'string', 'max:150'],
            'title_bn'     => ['nullable', 'string', 'max:150'],
            'is_active'    => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var ExamTypeSubject $examTypeSubject */
            $examTypeSubject = $this->route('examTypeSubject');
            if (!$examTypeSubject) {
                return;
            }

            $examTypeId = $this->input('exam_type_id', $examTypeSubject->exam_type_id);
            $subjectId  = $this->input('subject_id',  $examTypeSubject->subject_id);
            $title      = $this->input('title',        $examTypeSubject->title);

            // Check (subject_id, exam_type_id) uniqueness — ignore current record
            $mappingExists = ExamTypeSubject::where('exam_type_id', $examTypeId)
                ->where('subject_id', $subjectId)
                ->where('id', '!=', $examTypeSubject->id)
                ->exists();

            if ($mappingExists) {
                $validator->errors()->add(
                    'subject_id',
                    'This subject is already mapped under the selected Exam Type.'
                );
            }

            // Check (exam_type_id, title) uniqueness — ignore current record
            $titleExists = ExamTypeSubject::where('exam_type_id', $examTypeId)
                ->whereRaw('LOWER(TRIM(title)) = ?', [mb_strtolower(trim($title))])
                ->where('id', '!=', $examTypeSubject->id)
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
