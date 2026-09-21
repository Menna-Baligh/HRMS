<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AISkillGapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'exists:users,employee_id'],
            'period' => ['nullable', 'string'],
            'target_role' => ['nullable', 'string', 'max:255'],
            'target_skills' => ['nullable', 'array', 'max:10'],
            'target_skills.*' => ['string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => __('ai.attributes.employee_id'),
            'period' => __('ai.attributes.period'),
            'target_role' => __('ai.attributes.target_role'),
            'target_skills' => __('ai.attributes.target_skills'),
        ];
    }
}
