<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255', 'unique:departments,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'manager_id'  => [
                'nullable',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $manager = User::find($value);
                        $roleValue = $manager?->role instanceof \BackedEnum ? $manager->role->value : $manager?->role;
                        if ($manager && ! in_array($roleValue, ['Manager', 'HR', 'Owner']) && ! $manager->hasAnyRole(['Manager', 'HR', 'Owner'])) {
                            $fail(__('validation.custom.manager_id.invalid_role'));
                        }
                    }
                }
            ],
        ];
    }
}
