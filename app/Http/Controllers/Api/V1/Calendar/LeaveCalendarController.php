<?php

namespace App\Http\Controllers\Api\V1\Calendar;

use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaveCalendarController extends Controller
{
    /**
     * Display approved leaves for calendar visualization.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        $query = LeaveRequest::with(['user', 'leaveType'])
            ->where('status', LeaveStatus::Approved)
            ->where(function ($q) use ($startDate, $endDate): void {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($sub) use ($startDate, $endDate): void {
                        $sub->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            })
            ->orderBy('start_date');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        return LeaveRequestResource::collection($query->get());
    }
}
