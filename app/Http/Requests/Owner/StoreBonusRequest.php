<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreBonusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'incentive_type' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'target_month' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => __('financial.validation.user_id.required'),
            'user_id.exists' => __('financial.validation.user_id.exists'),
            'incentive_type.required' => __('financial.validation.incentive_type.required'),
            'amount.required' => __('financial.validation.amount.required'),
            'amount.numeric' => __('financial.validation.amount.numeric'),
            'amount.gt' => __('financial.validation.amount.gt'),
            'target_month.required' => __('financial.validation.target_month.required'),
        ];
    }
}
