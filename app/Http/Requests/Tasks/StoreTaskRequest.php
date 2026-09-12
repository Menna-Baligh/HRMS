<?php

namespace App\Http\Requests\Tasks;

use App\Enums\TaskPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'priority' => [
                'required',
                new Enum(TaskPriority::class),
            ],

            'deadline' => [
                'required',
                'date',
                'after:now',
            ],
        ];
    }
}