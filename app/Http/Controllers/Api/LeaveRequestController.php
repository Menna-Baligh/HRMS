<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeaveRequest\RejectLeaveRequestRequest;
use App\Http\Requests\LeaveRequest\StoreLeaveAttachmentRequest;
use App\Http\Requests\LeaveRequest\StoreLeaveRequest;
use App\Models\LeaveRequest;
use App\Services\FileService;
use Illuminate\Support\Facades\Log;
use App\Services\LeaveRequests\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveRequestService,
        protected FileService $fileService

    ) {
    }

    public function store(StoreLeaveRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $leaveRequest = $this->leaveRequestService->create(
                user: $user,
                data: $request->validated()
            );

            return ResponseHelper::success(
                data: $leaveRequest->load([
                    'user',
                    'leaveType',
                ]),
                message: __('leave_requests.created_successfully'),
                statusCode: 201
            );

        } catch (RuntimeException $exception) {
            return ResponseHelper::error(
                errors: null,
                message: $exception->getMessage(),
                statusCode: 422
            );
        }
    }

    public function approve(
        Request $request,
        int $leaveRequest
    ): JsonResponse {
        try {
            $leaveRequestModel = LeaveRequest::findOrFail($leaveRequest);

            $leaveRequest = $this->leaveRequestService->approve(
                leaveRequest: $leaveRequestModel,
                reviewerId: $request->user()->id
            );

            return ResponseHelper::success(
                data: $leaveRequest,
                message: __('leave_requests.approved_successfully')
            );

        } catch (RuntimeException $exception) {
            return ResponseHelper::error(
                errors: null,
                message: $exception->getMessage(),
                statusCode: 422
            );
        }
    }

    public function reject(
        RejectLeaveRequestRequest $request,
        int $leaveRequest
    ): JsonResponse {
        try {
            $leaveRequestModel = LeaveRequest::findOrFail($leaveRequest);

            $leaveRequest = $this->leaveRequestService->reject(
                leaveRequest: $leaveRequestModel,
                reviewerId: $request->user()->id,
                rejectionReason: $request->validated('rejection_reason')
            );

            return ResponseHelper::success(
                data: $leaveRequest,
                message: __('leave_requests.rejected_successfully')
            );

        } catch (RuntimeException $exception) {
            return ResponseHelper::error(
                errors: null,
                message: $exception->getMessage(),
                statusCode: 422
            );
        }
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $leaveRequests = $this->leaveRequestService->getUserHistory(
            user: $user,
            status: $request->query('status'),
            leaveTypeId: $request->query('leave_type_id')
                ? (int) $request->query('leave_type_id')
                : null,
            year: $request->query('year')
                ? (int) $request->query('year')
                : null
        );

        return ResponseHelper::success(
            data: $leaveRequests,
            message: __('leave_requests.retrieved_successfully')
        );
    }

    public function show(int $leaveRequest): JsonResponse
{
    $leaveRequestModel = LeaveRequest::findOrFail($leaveRequest);

    $leaveRequest = $this->leaveRequestService->getDetails(
        leaveRequest: $leaveRequestModel
    );

    return ResponseHelper::success(
        data: $leaveRequest,
        message: __('leave_requests.details_retrieved_successfully')
    );
}

// Manager Pending Leave Queue 

public function managerPendingQueue(Request $request): JsonResponse
{
    $manager = $request->user();

    $leaveRequests = $this->leaveRequestService->getManagerPendingQueue(
        manager: $manager
    );

    return ResponseHelper::success(
        data: $leaveRequests,
        message: __('leave_requests.manager_queue_retrieved_successfully')
    );
}
 // hr Pending Queue

public function hrPendingQueue(): JsonResponse
{
    $leaveRequests = $this->leaveRequestService->getHrPendingQueue();

    return ResponseHelper::success(
        data: $leaveRequests,
        message: __('leave_requests.hr_queue_retrieved_successfully')
    );
}


    public function storeAttachment(StoreLeaveAttachmentRequest $request, int $leaveRequest): JsonResponse {
        try {
            $leaveRequestModel = LeaveRequest::findOrFail($leaveRequest);
    
            $user = $request->user();
    
            if ($leaveRequestModel->user_id !== $user->id) {
                return ResponseHelper::error(
                    errors: null,
                    message: __('leave_requests.unauthorized_attachment'),
                    statusCode: 403
                );
            }
    
            $file = $this->fileService->uploadFile(
                file: $request->file('file'),
                user: $user,
                fileable: $leaveRequestModel
            );
    
            return ResponseHelper::success(
                data: $file,
                message: __('leave_requests.attachment_uploaded_successfully'),
                statusCode: 201
            );
        } catch (\Throwable $exception) {
            Log::error(
                'Leave attachment upload error: '.$exception->getMessage()
            );
    
            return ResponseHelper::error(
                errors: null,
                message: __('leave_requests.attachment_upload_failed'),
                statusCode: 500
            );
        }
    }
}