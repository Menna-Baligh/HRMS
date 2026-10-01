<?php

namespace App\Http\Controllers\Api\Owner;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreSalaryAdvanceRequest;
use App\Http\Requests\Owner\UpdateSalaryAdvanceStatusRequest;
use App\Http\Resources\SalaryAdvanceResource;
use App\Jobs\SendNotificationJob;
use App\Models\SalaryAdvance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SalaryAdvanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);
        $user = auth()->user();

        $query = SalaryAdvance::with('user')->latest();

        if (! $user->hasAnyRole(['Owner', 'HR'])) {
            $query->where('user_id', $user->id);
        }

        $advances = $query->paginate($perPage);

        return ResponseHelper::success(
            data: SalaryAdvanceResource::collection($advances)->response()->getData(true),
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
        $advance->load('user');
        $admins = User::role(['Owner', 'HR'])->where('id', '!=', auth()->id())->get();

        foreach ($admins as $admin) {
            SendNotificationJob::dispatch(
                $admin,
                'salary_advance_request',
                'financial.notifications.advance_requested.title',
                'financial.notifications.advance_requested.body',
                [
                    'employee' => $advance->user?->name,
                    'amount' => $advance->requested_amount,
                ],
                [
                    'advance_id' => $advance->id ,
                    'screen' => 'salary_advance_details',
                    'click_action' => 'FRONTEND_NOTIFICATION_CLICK',
                ]
            );
        }

        return ResponseHelper::success(
            data: new SalaryAdvanceResource($advance),
            message: __('financial.advances.created'),
            statusCode: Response::HTTP_CREATED
        );
    }

    public function updateStatus(UpdateSalaryAdvanceStatusRequest $request, SalaryAdvance $advance): JsonResponse
    {
        $status = $request->validated('status');
        $advance->update([
            'status' => $status,
        ]);
        $advance->load('user');

        if ($advance->user) {
            SendNotificationJob::dispatch(
                $advance->user,
                'salary_advance_status_updated',
                'financial.notifications.advance_status_updated.title',
                'financial.notifications.advance_status_updated.body',
                [
                    'amount' => $advance->requested_amount,
                    'status' => $status,
                ],
                [
                    'advance_id' => $advance->id,
                    'status' => $status,
                    'screen' => 'salary_advance_details',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            );
        }

        return ResponseHelper::success(
            data: new SalaryAdvanceResource($advance),
            message: __('financial.advances.status_updated')
        );
    }
}
