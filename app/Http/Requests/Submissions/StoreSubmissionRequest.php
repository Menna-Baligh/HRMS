<?php

namespace App\Http\Requests\Submissions;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'files'   => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:pdf,png,jpg,jpeg,zip,docx', 'max:10240'],
        ];
    }
}
