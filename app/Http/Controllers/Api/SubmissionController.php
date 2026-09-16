<?php

namespace App\Http\Controllers\Api;

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
    public function store(
        StoreSubmissionRequest $request,
        Task $task
    ): JsonResponse {
        $submission = $this->submissionService->create(
            $task,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Submission created successfully.',
            'data' => $submission,
        ], 201);
    }

    /**
 * Attach a file to a submission.
 */
public function attachFile(
    AttachSubmissionFileRequest $request,
    Submission $submission
): JsonResponse {
    $attachment = $this->submissionService->attachFile(
        $submission,
        $request->file('file')
    );

    return response()->json([
        'success' => true,
        'message' => 'File attached successfully.',
        'data' => $attachment,
    ], 201);
}

/**
 * Display submission details.
 */
public function show(Submission $submission): JsonResponse
{
    $submission = $this->submissionService->show($submission);

    return response()->json([
        'success' => true,
        'data' => $submission,
    ]);
}

/**
 * Display submissions waiting for review.
 */
public function reviewQueue(): JsonResponse
{
    $submissions = $this->submissionService->reviewQueue();

    return response()->json([
        'success' => true,
        'data' => $submissions,
    ]);
}

/**
 * Approve submission.
 */
public function approve(
    Submission $submission
): JsonResponse {
    $submission = $this->submissionService->approve(
        $submission
    );

    return response()->json([
        'success' => true,
        'message' => 'Submission approved successfully.',
        'data' => $submission,
    ]);
}


/**
 * Reject submission.
 */
public function reject(
    RejectSubmissionRequest $request,
    Submission $submission
): JsonResponse {
    $submission = $this->submissionService->reject(
        $submission,
        $request->validated('feedback')
    );

    return response()->json([
        'success' => true,
        'message' => 'Submission rejected successfully.',
        'data' => $submission,
    ]);
}


/**
 * Request changes from employee.
 */
public function requestChanges(
    RequestChangesRequest $request,
    Submission $submission
): JsonResponse {
    $submission = $this->submissionService->requestChanges(
        $submission,
        $request->validated('feedback')
    );

    return response()->json([
        'success' => true,
        'message' => 'Changes requested successfully.',
        'data' => $submission,
    ]);
}


/**
 * Resubmit a submission after requested changes.
 */
public function resubmit(
    ResubmitSubmissionRequest $request,
    Submission $submission
): JsonResponse {
    $submission = $this->submissionService->resubmit(
        $submission,
        $request->validated('note')
    );

    return response()->json([
        'success' => true,
        'message' => 'Submission resubmitted successfully.',
        'data' => $submission,
    ]);
}
}