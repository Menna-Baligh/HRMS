<?php

namespace App\Http\Controllers\Api\V1\LeaveRequest;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequest\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveRequestService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class LeaveRequestController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveRequestService,
    ) {}

    /**
     * Display a listing of the user's leave requests.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = LeaveRequest::with(['leaveType', 'manager', 'hr'])
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->query('leave_type_id'));
        }

        $perPage = (int) $request->query('per_page', 15);

        return LeaveRequestResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created leave request.
     */
    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $leaveType = LeaveType::findOrFail($request->integer('leave_type_id'));

        try {
            $leaveRequest = $this->leaveRequestService->create(
                $request->user(),
                $leaveType,
                $request->validated(),
                $request->file('attachment'),
            );

            $leaveRequest->load(['leaveType', 'user']);

            return (new LeaveRequestResource($leaveRequest))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Display the specified leave request.
     */
    public function show(LeaveRequest $leaveRequest): LeaveRequestResource
    {
        $leaveRequest->loadMissing(['user', 'leaveType', 'manager', 'hr', 'decisionHistories.reviewer']);

        Gate::authorize('view', $leaveRequest);

        return new LeaveRequestResource($leaveRequest);
    }

    /**
     * Cancel a leave request.
     */
    public function cancel(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest->loadMissing('user');

        Gate::authorize('cancel', $leaveRequest);

        try {
            $updated = $this->leaveRequestService->cancel($leaveRequest, $request->user());

            return response()->json([
                'message' => 'Leave request cancelled successfully.',
                'data' => new LeaveRequestResource($updated->load(['leaveType', 'user'])),
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
