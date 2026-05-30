<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150', 'unique:users,email,' . $this->user()->id],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'district' => ['sometimes', 'nullable', 'string', 'max:100'],
            'division' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    protected function passedValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => strip_tags($this->input('name')),
            ]);
        }
    }
}
