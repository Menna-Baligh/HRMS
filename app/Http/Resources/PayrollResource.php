<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isFinalized = (bool) ($this->is_finalized ?? ($this->status === 'finalized'));

        return [
            'id' => $this->when($isFinalized, $this->id),
            'user_id' => $this->user_id,
            'employee_name' => $this->user?->name ?? $this->employee_name ?? 'N/A',
            'month_year' => $this->month_year,
            'basic_salary' => (float) ($this->basic_salary ?? 0),
            'total_bonuses' => (float) ($this->total_bonuses ?? 0),
            'total_deductions' => (float) ($this->total_deductions ?? 0),
            'loan_installment' => (float) ($this->loan_installment ?? 0),
            'net_salary' => (float) ($this->net_salary ?? 0),
            'status' => $this->status ?? 'draft',
            'is_finalized' => $isFinalized,
        ];
    }
}