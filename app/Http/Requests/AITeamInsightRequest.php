<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AITeamInsightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department' => ['required', 'string', 'min:1'],
            'period' => ['required', 'string', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'department' => __('ai.attributes.department'),
            'period' => __('ai.attributes.period'),
        ];
    }
}
