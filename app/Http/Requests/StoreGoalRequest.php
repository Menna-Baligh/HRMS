<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id'  => __('validation.attributes.employee_id'),
            'title'        => __('validation.attributes.title'),
            'target_date'  => __('validation.attributes.target_date'),
        ];
    }
}
