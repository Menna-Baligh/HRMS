<?php

namespace App\Http\Controllers\Api\Owner;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\FinalizePayrollRequest;
use App\Http\Resources\PayrollResource;
use App\Http\Resources\PayslipResource;
use App\Models\Payroll;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PayrollController extends Controller
{
    public function __construct(protected PayrollService $payrollService) {}

    
    public function index(Request $request): JsonResponse
    {
        $monthYear = $request->input('month_year', now()->format('Y-m'));

        $payrolls = $this->payrollService->calculateCompanyPayroll($monthYear);

        return ResponseHelper::success(
            data: PayrollResource::collection($payrolls),
            message: __('financial.payroll.retrieved')
        );
    }


    public function finalize(FinalizePayrollRequest $request): JsonResponse
    {
        $monthYear = $request->validated('month_year');

        if (Payroll::where('month_year', $monthYear)->exists()) {
            return ResponseHelper::error(
                message: __('financial.payroll.already_finalized'),
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $finalizedPayrolls = $this->payrollService->finalizeCompanyPayroll($monthYear);

        return ResponseHelper::success(
            data: PayrollResource::collection($finalizedPayrolls),
            message: __('financial.payroll.finalized', ['month' => $monthYear]),
            statusCode: Response::HTTP_CREATED
        );
    }


    public function payslip(Payroll $payroll): JsonResponse
    {
        return ResponseHelper::success(
            data: new PayslipResource($payroll->load('user.roles')),
            message: __('financial.payroll.payslip_retrieved')
        );
    }


    public function mySalaries(): JsonResponse
    {
        $salariesHistory = $this->payrollService->getEmployeeSalaryHistory(auth()->user());

        return ResponseHelper::success(
            data: PayrollResource::collection($salariesHistory),
            message: __('financial.payroll.retrieved')
        );
    }
}