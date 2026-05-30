<?php

namespace App\Modules\Exam\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
