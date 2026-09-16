<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvaluationCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:100'],
            'weight' => ['required', 'numeric', 'min:0.1', 'max:10'],
        ];
    }
}
