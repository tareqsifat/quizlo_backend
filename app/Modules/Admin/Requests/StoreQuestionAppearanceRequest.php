<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionAppearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'exam_type_id' => ['required', 'integer', 'exists:exam_types,id'],
            'year'         => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'source_batch' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Trim and title-case source_batch to reduce near-duplicate labels
     * (e.g. "bcs-60" / "BCS-60 " both normalise to "Bcs-60"... actually
     * keep as-is casing but trim whitespace; exact casing is preserved
     * so admins can still write "BCS-60" naturally).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('source_batch') && is_string($this->input('source_batch'))) {
            $this->merge([
                'source_batch' => trim($this->input('source_batch')) ?: null,
            ]);
        }
    }
}
