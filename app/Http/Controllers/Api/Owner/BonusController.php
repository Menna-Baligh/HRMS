<?php

namespace App\Http\Controllers\Api\Owner;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreBonusRequest;
use App\Http\Requests\Owner\UpdateBonusStatusRequest;
use App\Http\Resources\BonusResource;
use App\Jobs\SendNotificationJob;
use App\Models\Bonus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BonusController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 10);

        $bonuses = Bonus::with(['user.roles', 'approver'])
            ->latest()
            ->paginate($perPage);

        $totalPoolDistributed = Bonus::where('status', 'approved')->sum('amount');

        $paginatedData = BonusResource::collection($bonuses)->response()->getData(true);

        return ResponseHelper::success(
            data: array_merge($paginatedData, [
                'stats' => [
                    'total_bonus_pool_distributed' => (float) $totalPoolDistributed,
                ],
            ]),
            message: __('financial.bonuses.retrieved')
        );
    }

    public function store(StoreBonusRequest $request): JsonResponse
    {
        $bonus = Bonus::create([
            ...$request->validated(),
            'approved_by_user_id' => auth()->id(),
            'status' => 'approved',
        ]);

        $bonus->load(['user.roles', 'approver']);

        if ($bonus->user) {
            SendNotificationJob::dispatch(
                $bonus->user,
                'bonus_issued',
                'financial.notifications.bonus_issued.title',
                'financial.notifications.bonus_issued.body',
                [
                    'type' => $bonus->incentive_type,
                    'amount' => $bonus->amount,
                    'month' => $bonus->target_month,
                ],
                [
                    'bonus_id' => $bonus->id,
                    'screen' => 'bonus_details',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]
            );
        }

        return ResponseHelper::success(
            data: new BonusResource($bonus),
            message: __('financial.bonuses.created'),
            statusCode: Response::HTTP_CREATED
        );
    }


}
