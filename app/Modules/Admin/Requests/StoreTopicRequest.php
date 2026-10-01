<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Topic;

class StoreTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type_subject_id' => ['required', 'integer', 'exists:exam_type_subjects,id'],
            'name'                 => ['required', 'string', 'max:150'],
            'name_bn'              => ['nullable', 'string', 'max:150'],
            'sort_order'           => ['nullable', 'integer'],
            'is_active'            => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $examTypeSubjectId = $this->input('exam_type_subject_id');
            $name               = $this->input('name');

            if (!$examTypeSubjectId || !$name) {
                return; // base rules handle required errors
            }

            $nameExists = Topic::where('exam_type_subject_id', $examTypeSubjectId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])
                ->exists();

            if ($nameExists) {
                $validator->errors()->add(
                    'name',
                    "Another Topic already uses the name \"{$name}\" under this Exam Subject."
                );
            }
        });
    }
}
