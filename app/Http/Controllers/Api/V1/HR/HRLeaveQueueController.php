<?php

namespace App\Http\Controllers\Api\V1\HR;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HRLeaveQueueController extends Controller
{
    /**
     * Display all leave requests across the company with extensive filtering for HR.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = LeaveRequest::with(['user', 'leaveType', 'manager', 'hr'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->query('leave_type_id'));
        }

        if ($request->filled('manager_id')) {
            $query->whereHas('user', function ($q) use ($request): void {
                $q->where('manager_id', $request->query('manager_id'));
            });
        }

        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->query('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->query('end_date'));
        }

        $perPage = (int) $request->query('per_page', 15);

        return LeaveRequestResource::collection($query->paginate($perPage));
    }
}
