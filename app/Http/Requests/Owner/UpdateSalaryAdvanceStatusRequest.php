<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalaryAdvanceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:approved,rejected'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => __('financial.validation.status.required'),
            'status.in' => __('financial.validation.status.in'),
        ];
    }
}
