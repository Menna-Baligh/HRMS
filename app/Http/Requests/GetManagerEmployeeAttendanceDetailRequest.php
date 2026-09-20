<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetManagerEmployeeAttendanceDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
