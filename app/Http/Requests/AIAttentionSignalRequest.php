<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AIAttentionSignalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'exists:users,employee_id'],
            'target_period' => ['required', 'string', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => __('ai.attributes.employee_id'),
            'target_period' => __('ai.attributes.target_period'),
        ];
    }
}
