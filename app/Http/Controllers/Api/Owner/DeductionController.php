<?php

namespace App\Http\Controllers\Api\Owner;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreDeductionRequest;
use App\Http\Resources\DeductionResource;
use App\Jobs\SendNotificationJob;
use App\Models\Deduction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeductionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);

        $deductions = Deduction::with('user')
            ->latest()
            ->paginate($perPage);

        return ResponseHelper::success(
            data: DeductionResource::collection($deductions)->response()->getData(true),
            message: __('financial.deductions.retrieved')
        );
    }

    public function store(StoreDeductionRequest $request): JsonResponse
    {
        $deduction = Deduction::create([
            ...$request->validated(),
            'type' => $request->validated('type', 'manual'),
            'status' => 'queued',
        ]);

        $deduction->load('user');

        if ($deduction->user) {
            SendNotificationJob::dispatch(
                $deduction->user,
                'deduction_recorded',
                'financial.notifications.deduction_recorded.title',
                'financial.notifications.deduction_recorded.body',
                [
                    'amount' => $deduction->amount,
                    'reason' => $deduction->reason,
                ],
                [
                    'deduction_id' => $deduction->id,
                    'screen' => 'deduction_details', 
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            );
        }

        return ResponseHelper::success(
            data: new DeductionResource($deduction),
            message: __('financial.deductions.created'),
            statusCode: Response::HTTP_CREATED
        );
    }
}
