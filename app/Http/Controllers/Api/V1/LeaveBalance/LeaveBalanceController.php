<?php

namespace App\Http\Controllers\Api\V1\LeaveBalance;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveBalanceResource;
use App\Models\LeaveBalance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaveBalanceController extends Controller
{
    /**
     * Display leave balances for the authenticated user (or specified user if HR/Owner).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $targetUserId = $user->id;

        // HR or Owner can view other users' balances
        if ($request->has('user_id') && $user->isHrOrAbove()) {
            $targetUserId = (int) $request->query('user_id');
        }

        $year = (int) $request->query('year', date('Y'));

        $balances = LeaveBalance::with('leaveType')
            ->where('user_id', $targetUserId)
            ->where('year', $year)
            ->get();

        return LeaveBalanceResource::collection($balances);
    }
}
