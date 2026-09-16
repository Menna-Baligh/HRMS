<?php

namespace App\Http\Requests\Submissions;

use Illuminate\Foundation\Http\FormRequest;

class AttachSubmissionFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
            ],
        ];
    }
}