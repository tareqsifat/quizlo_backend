<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
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
            'phone' => ['required', 'string', 'regex:/^(?:\+88|88)?(01[3-9]\d{8})$/'],
            'purpose' => ['sometimes', 'string', 'in:login,register'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Please provide a valid Bangladeshi phone number.',
        ];
    }
}
