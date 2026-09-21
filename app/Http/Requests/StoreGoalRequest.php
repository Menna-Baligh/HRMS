<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'target_value' => ['required', 'numeric', 'min:1'],
            'target_date'  => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title'        => 'عنوان الهدف',
            'description'  => 'وصف الهدف',
            'target_value' => 'القيمة المستهدفة',
            'target_date'  => 'تاريخ الاستهداف',
        ];
    }
}
