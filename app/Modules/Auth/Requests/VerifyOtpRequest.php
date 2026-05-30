<?php

namespace App\Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'otp_code' => ['required', 'string', 'size:6'],
            'purpose' => ['required', 'string', 'in:login,register'],
        ];
    }
}
