<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'type' => ['nullable', 'in:manual,delay'],
        ];
    }
    public function messages(): array
    {
        return [
            'user_id.required' => __('financial.validation.user_id.required'),
            'user_id.exists' => __('financial.validation.user_id.exists'),
            'amount.required' => __('financial.validation.amount.required'),
            'amount.numeric' => __('financial.validation.amount.numeric'),
            'amount.gt' => __('financial.validation.amount.gt'),
            'reason.required' => __('financial.validation.reason.required'),
            'reason.string' => __('financial.validation.reason.string'),
            'reason.max' => __('financial.validation.reason.max'),
            'date.required' => __('financial.validation.date.required'),
            'date.date' => __('financial.validation.date.date'),
            'type.in' => __('financial.validation.type.in'),
        ];
    }
}
