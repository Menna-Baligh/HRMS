<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
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
                'mimes:pdf,png,jpg,jpeg,doc,docx,zip',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please provide a file to upload.',
            'file.mimes' => 'Invalid file type. Allowed formats: PDF, PNG, JPG, JPEG, DOC, DOCX, ZIP.',
            'file.max' => 'File size exceeds the maximum limit of 10MB.',
        ];
    }
}
