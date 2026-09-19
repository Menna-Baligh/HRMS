<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeHrFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_title'       => ['sometimes', 'string', 'max:255'],
            'employment_type' => ['sometimes', 'in:Full-time,Part-time,Contract'],
            'status'          => ['sometimes', 'in:active,inactive'],
            'department_id'   => ['nullable', 'exists:departments,id'],
            'manager_id'      => [
                'nullable',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $manager = User::find($value);
                        $roleValue = $manager?->role instanceof \BackedEnum ? $manager->role->value : $manager?->role;
                        
                        if ($manager && $roleValue !== 'Manager' && ! $manager->hasRole('Manager')) {
                            $fail(__('employees.manager_must_be_manager_role'));
                        }
                    }
                },
            ],
        ];
    }


}