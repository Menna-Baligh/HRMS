<?php

namespace App\Http\Requests\Calendar;

use Illuminate\Foundation\Http\FormRequest;

class CalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => [
                'required',
                'date',
            ],

            'to' => [
                'required',
                'date',
                'after_or_equal:from',
            ],
        ];
    }

    /**
     * Prepare normalized date values for the service.
     */
    protected function passedValidation(): void
    {
        $this->merge([
            'from' => $this->date('from')->startOfDay()->toDateString(),
            'to' => $this->date('to')->endOfDay()->toDateString(),
        ]);
    }
}