<?php

namespace App\Modules\Content\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type_id'      => ['required', 'integer', 'exists:exam_types,id'],
            'question_id'       => ['required', 'integer', 'exists:questions,id'],
            'selected_option_id'=> ['required', 'integer', 'exists:question_options,id'],
            'session_id'        => ['nullable', 'integer', 'exists:exam_sessions,id'],
            'session_type'      => ['required', 'in:lesson,model_test,practice,exam_mode'],
            'time_taken_ms'     => ['nullable', 'integer', 'min:0', 'max:300000'],
        ];
    }

    public function messages(): array
    {
        return [
            'exam_type_id.exists'       => 'Invalid exam type.',
            'question_id.exists'        => 'Invalid question.',
            'selected_option_id.exists' => 'Invalid answer option.',
            'session_type.in'           => 'Invalid session type.',
        ];
    }
}
