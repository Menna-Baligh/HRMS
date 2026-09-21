<?php

namespace App\Http\Requests\LeaveBalances;

use Illuminate\Foundation\Http\FormRequest;

class IndexLeaveBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => [
                'nullable',
                'integer',
                'min:2000',
                'max:2100',
            ],

            'leave_type_id' => [
                'nullable',
                'integer',
                'exists:leave_types,id',
            ],
        ];
    }
}
