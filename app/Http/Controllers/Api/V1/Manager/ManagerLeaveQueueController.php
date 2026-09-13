<?php

namespace App\Http\Controllers\Api\V1\Manager;

use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ManagerLeaveQueueController extends Controller
{
    /**
     * Display team's leave requests pending manager review.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $manager = $request->user();

        // Team members who report to this manager
        $query = LeaveRequest::with(['user', 'leaveType'])
            ->whereHas('user', function ($q) use ($manager): void {
                $q->where('manager_id', $manager->id);
            })
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        } else {
            // Default to Pending requests awaiting manager action
            $query->where('status', LeaveStatus::Pending);
        }

        $perPage = (int) $request->query('per_page', 15);

        return LeaveRequestResource::collection($query->paginate($perPage));
    }
}
