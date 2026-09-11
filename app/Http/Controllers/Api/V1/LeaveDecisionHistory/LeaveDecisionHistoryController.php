<?php

namespace App\Http\Controllers\Api\V1\LeaveDecisionHistory;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveDecisionHistoryResource;
use App\Models\LeaveRequest;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class LeaveDecisionHistoryController extends Controller
{
    /**
     * Display decision history for a given leave request.
     */
    public function index(LeaveRequest $leaveRequest): AnonymousResourceCollection
    {
        Gate::authorize('view', $leaveRequest);

        $histories = $leaveRequest->decisionHistories()
            ->with('reviewer')
            ->orderBy('decided_at')
            ->get();

        return LeaveDecisionHistoryResource::collection($histories);
    }
}
