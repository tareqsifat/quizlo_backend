<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subjectId = $this->route('subject') instanceof \App\Models\Subject
            ? $this->route('subject')->id
            : $this->route('subject');

        return [
            'name'      => ['sometimes', 'string', 'max:150'],
            'name_bn'   => ['sometimes', 'string', 'max:150'],
            'slug'      => [
                'sometimes', 'string', 'max:150',
                Rule::unique('subjects', 'slug')->ignore($subjectId),
            ],
            'icon'      => ['nullable', 'string', 'max:50'],
            'color_hex' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique'     => 'This slug is already taken. Please choose a different one.',
            'color_hex.regex' => 'Color must be a valid hex code (e.g. #3498DB).',
        ];
    }
}
