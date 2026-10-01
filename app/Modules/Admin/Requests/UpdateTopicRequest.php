<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\Topic;

class UpdateTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type_subject_id' => ['sometimes', 'integer', 'exists:exam_type_subjects,id'],
            'name'                 => ['sometimes', 'string', 'max:150'],
            'name_bn'              => ['nullable', 'string', 'max:150'],
            'sort_order'           => ['nullable', 'integer'],
            'is_active'            => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Topic $topic */
            $topic = $this->route('topic');
            if (!$topic) {
                return;
            }

            $examTypeSubjectId = $this->input('exam_type_subject_id', $topic->exam_type_subject_id);
            $name               = $this->input('name', $topic->name);

            $nameExists = Topic::where('exam_type_subject_id', $examTypeSubjectId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($name))])
                ->where('id', '!=', $topic->id)
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
