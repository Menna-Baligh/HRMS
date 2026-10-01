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
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    public function __construct(protected PayrollService $payrollService) {}

    public function index(Request $request): JsonResponse
    {
        $monthYear = $request->input('month_year', now()->format('Y-m'));
        $perPage = (int) $request->input('per_page', 10);

        $payrollsPaginator = $this->payrollService->calculateCompanyPayroll($monthYear, $perPage);

        $isFinalized = Payroll::where('month_year', $monthYear)->exists();

        $paginatedData = PayrollResource::collection($payrollsPaginator)->response()->getData(true);

        return ResponseHelper::success(
            data: array_merge($paginatedData, [
                'is_month_finalized' => $isFinalized,
                'status_message' => $isFinalized 
                    ? __('financial.payroll.finalized_status') 
                    : __('financial.payroll.draft_status'),
            ]),
            message: __('financial.payroll.retrieved')
        );
    }

    public function finalize(FinalizePayrollRequest $request): JsonResponse
    {
        $monthYear = $request->validated('month_year');

        $finalizedPayrolls = $this->payrollService->finalizeCompanyPayroll($monthYear);

        if (empty($finalizedPayrolls)) {
            return ResponseHelper::success(
                data: [],
                message: __('financial.payroll.already_finalized_all')
            );
        }

        return ResponseHelper::success(
            data: PayrollResource::collection($finalizedPayrolls),
            message: __('financial.payroll.finalized', ['month' => $monthYear]),
            statusCode: Response::HTTP_CREATED
        );
    }

    public function payslip(Payroll $payroll)
    {
        $payroll->load(['user.department', 'user.roles']);

        $pdf = Pdf::loadView('pdf.payslip', [
            'payroll' => $payroll
        ]);

        $fileName = "payslip_{$payroll->user?->name}_{$payroll->month_year}.pdf";

        return $pdf->download($fileName);
    }

    public function mySalaries(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 10);

        $salariesPaginator = $this->payrollService->getEmployeeSalaryHistory(auth()->user(), $perPage);

        return ResponseHelper::success(
            data: PayrollResource::collection($salariesPaginator)->response()->getData(true),
            message: __('financial.payroll.retrieved')
        );
    }
}