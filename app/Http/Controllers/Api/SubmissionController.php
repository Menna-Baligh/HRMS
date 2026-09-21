<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Submissions\AttachSubmissionFileRequest;
use App\Http\Requests\Submissions\RejectSubmissionRequest;
use App\Http\Requests\Submissions\RequestChangesRequest;
use App\Http\Requests\Submissions\ResubmitSubmissionRequest;
use App\Http\Requests\Submissions\StoreSubmissionRequest;
use App\Models\Submission;
use App\Models\Task;
use App\Services\Submissions\SubmissionService;
use Illuminate\Http\JsonResponse;

class SubmissionController extends Controller
{
    public function __construct(
        private SubmissionService $submissionService
    ) {}

    /**
     * Create a task submission.
     */
    public function store(StoreSubmissionRequest $request,Task $task): JsonResponse {
        $submission = $this->submissionService->create(
            $task,
            $request->validated()
        );

        return ResponseHelper::success(
            $submission,
            __('Submission created successfully.'),
            201
        );
    }

    /**
     * Attach a file to a submission.
     */
    public function attachFile(AttachSubmissionFileRequest $request,Submission $submission): JsonResponse {
        $file = $this->submissionService->attachFile(
            $submission,
            $request->file('file')
        );

        return ResponseHelper::success(
            $file,
            __('submissions.file_attached_successfully'),
            201
        );
    }

    /**
     * Display submission details.
     */
    public function show(Submission $submission): JsonResponse
    {
        $submission = $this->submissionService->show($submission);
    
        return ResponseHelper::success(
            $submission,
            __('submissions.retrieved_successfully')
        );
    }

    /**
     * Display submissions waiting for review.
     */
    public function reviewQueue(): JsonResponse
    {
        $submissions = $this->submissionService->reviewQueue();
    
        return ResponseHelper::success(
            $submissions,
            __('submissions.review_queue_retrieved_successfully')
        );
    }

    /**
     * Approve submission.
     */
    public function approve(Submission $submission): JsonResponse
{
    $submission = $this->submissionService->approve($submission);

    return ResponseHelper::success(
        $submission,
        __('submissions.approved_successfully')
    );
}

    /**
     * Reject submission.
     */
    public function reject( RejectSubmissionRequest $request, Submission $submission): JsonResponse 
    {
        $submission = $this->submissionService->reject(
            $submission,
            $request->validated('feedback')
        );

        return ResponseHelper::success(
            $submission,
            __('submissions.rejected_successfully')
        );
    }

    /**
     * Request changes from employee.
     */
    public function requestChanges(RequestChangesRequest $request,Submission $submission): JsonResponse {
        $submission = $this->submissionService->requestChanges(
            $submission,
            $request->validated('feedback')
        );

        return ResponseHelper::success(
            $submission,
            __('submissions.changes_requested_successfully')
        );
    }

    /**
     * Resubmit a submission after requested changes.
     */
    public function resubmit(ResubmitSubmissionRequest $request,Submission $submission ): JsonResponse {
        $submission = $this->submissionService->resubmit(
            $submission,
            $request->validated('note')
        );

        return ResponseHelper::success(
            $submission,
            __('submissions.resubmitted_successfully')
        );
    }
}
