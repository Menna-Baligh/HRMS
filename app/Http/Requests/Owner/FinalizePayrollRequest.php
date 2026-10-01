<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class FinalizePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month_year' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'month_year.required' => __('financial.validation.month_year.required'),
            'month_year.regex' => __('financial.validation.month_year.string'),
        ];
    }
}
