<?php

namespace App\Http\Requests;

use App\Enums\AuditAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'actor_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'action' => [
                'nullable',
                Rule::enum(AuditAction::class),
            ],

            'entity_type' => [
                'nullable',
                'string',
                'max:255',
            ],

            'entity_id' => [
                'nullable',
                'integer',
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
        ];
    }
}
