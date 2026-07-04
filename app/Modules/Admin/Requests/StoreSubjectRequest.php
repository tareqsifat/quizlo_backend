<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:150'],
            'name_bn'   => ['required', 'string', 'max:150'],
            'slug'      => ['required', 'string', 'max:150', 'unique:subjects,slug'],
            'icon'      => ['nullable', 'string', 'max:50'],
            'color_hex' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.unique'         => 'This slug is already taken. Please choose a different one.',
            'color_hex.regex'     => 'Color must be a valid hex code (e.g. #3498DB).',
        ];
    }
}
