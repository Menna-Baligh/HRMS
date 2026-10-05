<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLandingSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => __('landing.validation.content_required'),
            'content.array' => __('landing.validation.content_array'),
        ];
    }
}
