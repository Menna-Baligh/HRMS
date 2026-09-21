<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AIPolicyAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'exists:users,employee_id'],
            'question' => ['required', 'string', 'min:3'],
            'session_id' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => __('ai.attributes.employee_id'),
            'question' => __('ai.attributes.question'),
            'session_id' => __('ai.attributes.session_id'),
        ];
    }
}
