<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalaryAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'requested_amount' => ['required', 'numeric', 'gt:0'],
            'repayment_months' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => __('financial.validation.user_id.required'),
            'user_id.exists' => __('financial.validation.user_id.exists'),
            'requested_amount.required' => __('financial.validation.requested_amount.required'),
            'requested_amount.numeric' => __('financial.validation.requested_amount.numeric'),
            'requested_amount.gt' => __('financial.validation.requested_amount.gt'),
            'repayment_months.required' => __('financial.validation.repayment_months.required'),
            'repayment_months.integer' => __('financial.validation.repayment_months.integer'),
            'repayment_months.min' => __('financial.validation.repayment_months.min'),
            'reason.required' => __('financial.validation.reason.required'),
        ];
    }
}
