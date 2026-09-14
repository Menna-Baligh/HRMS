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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_value' => ['required', 'numeric', 'min:1'], 
            'target_date' => ['required', 'date', 'after_or_equal:today'], 
        ];
    }
    public function messages(): array
    {
        return [
            'target_value.min' => 'Target value must be greater than zero.',
            'target_date.after_or_equal' => 'Target date must be today or a future date.',
        ];
    }
}
