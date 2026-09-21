<?php

namespace App\Http\Requests\LeaveTypes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $leaveTypeId = $this->route('leaveType')?->id
            ?? $this->route('leaveType');

        return [
            'name' => [
                'sometimes',
                'string',
                'min:2',
                'max:255',
                Rule::unique('leave_types', 'name')
                    ->ignore($leaveTypeId),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
            'default_days' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'requires_balance' => [
                'sometimes',
                'boolean',
            ],

            'requires_attachment' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}