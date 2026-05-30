<?php

namespace App\Modules\Exam\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'answers.*.selected_option_id' => ['required', 'integer', 'exists:question_options,id'],
            'answers.*.time_taken_ms' => ['nullable', 'integer'],
        ];
    }
}
