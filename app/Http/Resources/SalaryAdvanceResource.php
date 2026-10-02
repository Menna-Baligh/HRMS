<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryAdvanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_name' => $this->user?->name,
            'requested_amount' => (float) $this->requested_amount,
            'repayment_months' => (int) $this->repayment_months,
            'monthly_deduction' => (float) $this->monthly_deduction,
            'reason' => $this->reason,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
