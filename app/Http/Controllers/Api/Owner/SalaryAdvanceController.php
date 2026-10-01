<?php

namespace App\Http\Controllers\Api\Owner;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreSalaryAdvanceRequest;
use App\Http\Requests\Owner\UpdateSalaryAdvanceStatusRequest;
use App\Http\Resources\SalaryAdvanceResource;
use App\Models\SalaryAdvance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SalaryAdvanceController extends Controller
{
    public function index(): JsonResponse
    {
        $advances = SalaryAdvance::with('user')->latest()->get();

        return ResponseHelper::success(
            data: SalaryAdvanceResource::collection($advances),
            message: __('financial.advances.retrieved')
        );
    }

    public function store(StoreSalaryAdvanceRequest $request): JsonResponse
    {
        $monthlyDeduction = $request->validated('requested_amount') / $request->validated('repayment_months');

        $advance = SalaryAdvance::create([
            ...$request->validated(),
            'monthly_deduction' => round($monthlyDeduction, 2),
            'status' => 'pending',
        ]);

        return ResponseHelper::success(
            data: new SalaryAdvanceResource($advance->load('user')),
            message: __('financial.advances.created'),
            statusCode: Response::HTTP_CREATED
        );
    }

    public function updateStatus(UpdateSalaryAdvanceStatusRequest $request, SalaryAdvance $advance): JsonResponse
    {
        $advance->update([
            'status' => $request->validated('status'),
        ]);

        return ResponseHelper::success(
            data: new SalaryAdvanceResource($advance->load('user')),
            message: __('financial.advances.status_updated')
        );
    }
}
