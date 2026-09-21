<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AIEvaluationDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'exists:users,employee_id'],
            'period' => ['required', 'string', 'min:1'],
            'evaluation_scores' => ['required', 'array'],
            'evaluation_scores.overall_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'manager_notes' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => __('ai.attributes.employee_id'),
            'period' => __('ai.attributes.period'),
            'evaluation_scores' => __('ai.attributes.evaluation_scores'),
            'manager_notes' => __('ai.attributes.manager_notes'),
        ];
    }
}
