<?php

namespace App\Http\Controllers\Api\V1\LeaveType;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveType\StoreLeaveTypeRequest;
use App\Http\Requests\LeaveType\UpdateLeaveTypeRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class LeaveTypeController extends Controller
{
    /**
     * Display a listing of leave types.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        // HR/Owner can see all, regular users see active types
        $query = LeaveType::query();
        if (! $user || ! $user->isHrOrAbove()) {
            $query->active();
        }

        return LeaveTypeResource::collection($query->get());
    }

    /**
     * Store a newly created leave type.
     */
    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        Gate::authorize('create', LeaveType::class);

        $leaveType = LeaveType::create($request->validated());

        return (new LeaveTypeResource($leaveType))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified leave type.
     */
    public function show(LeaveType $leaveType): LeaveTypeResource
    {
        return new LeaveTypeResource($leaveType);
    }

    /**
     * Update the specified leave type.
     */
    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): LeaveTypeResource
    {
        Gate::authorize('update', $leaveType);

        $leaveType->update($request->validated());

        return new LeaveTypeResource($leaveType);
    }

    /**
     * Remove the specified leave type.
     */
    public function destroy(LeaveType $leaveType): JsonResponse
    {
        Gate::authorize('delete', $leaveType);

        $leaveType->delete();

        return response()->json(['message' => 'Leave type deleted successfully']);
    }
}
