<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetDailyGoalRequest extends FormRequest
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
            'daily_goal' => ['required', 'integer', 'in:10,20,30'],
        ];
    }
}
