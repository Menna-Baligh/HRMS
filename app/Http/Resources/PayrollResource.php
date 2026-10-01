<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'user_id' => $this->user_id,
            'employee_name' => $this->user?->name,
            'month_year' => $this->month_year,
            'basic_salary' => (float) $this->basic_salary,
            'total_bonuses' => (float) $this->total_bonuses,
            'total_deductions' => (float) $this->total_deductions,
            'loan_installment' => (float) $this->loan_installment,
            'net_salary' => (float) $this->net_salary,
            'status' => $this->status ?? 'draft',
        ];
    }
}