<?php

namespace App\Http\Requests;

use App\Enums\GoalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_date' => ['sometimes', 'required', 'date', 'after_or_equal:today'],
            'employee_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'status' => ['sometimes', 'required', Rule::enum(GoalStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('goal.validation.title_required'),
            'title.string' => __('goal.validation.title_string'),
            'title.max' => __('goal.validation.title_max'),
            'description.string' => __('goal.validation.description_string'),
            'target_date.required' => __('goal.validation.target_date_required'),
            'target_date.date' => __('goal.validation.target_date_date'),
            'target_date.after_or_equal' => __('goal.validation.target_date_after_or_equal'),
            'employee_id.required' => __('goal.validation.employee_id_required'),
            'employee_id.integer' => __('goal.validation.employee_id_integer'),
            'employee_id.exists' => __('goal.validation.employee_id_exists'),
            'status.required' => __('goal.validation.status_required'),
            'status.enum' => __('goal.validation.status_enum'),
        ];
    }
}
