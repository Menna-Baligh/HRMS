<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'target_value' => ['sometimes', 'required', 'numeric', 'min:1'],
            'target_date' => ['sometimes', 'required', 'date', 'after_or_equal:today'],
        ];
    }
}
