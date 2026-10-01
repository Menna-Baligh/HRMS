<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'payroll_id' => $this->id,
            'month_year' => $this->month_year,
            'status' => $this->status,
            'employee' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'role' => $this->user?->roles?->first()?->name ?? 'Employee',
            ],
            'financial_summary' => [
                'basic_salary' => (float) $this->basic_salary,
                'total_bonuses' => (float) $this->total_bonuses,
                'total_deductions' => (float) $this->total_deductions,
                'loan_installment' => (float) $this->loan_installment,
                'net_salary' => (float) $this->net_salary,
            ],
            'issued_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
