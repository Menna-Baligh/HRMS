<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AICareerCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'exists:users,employee_id'],
            'period'      => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => __('ai.attributes.employee_id'),
            'period'      => __('ai.attributes.period'),
        ];
    }
}
