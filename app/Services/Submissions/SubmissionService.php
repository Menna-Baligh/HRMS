<?php

namespace App\Services\Submissions;

use App\Enums\SubmissionStatus;
use App\Enums\TaskStatus;
use App\Jobs\SendNotificationJob;
use App\Models\File;
use App\Models\Submission;
use App\Models\SubmissionAttachment;
use App\Models\SubmissionReview;
use App\Models\Task;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmissionService
{
    public function __construct(
        protected FileService $fileService
    ) {}

    /**
     * Create a new submission for a task.
     */
    public function create(Task $task, array $data): Submission
    {
        $submission = DB::transaction(function () use ($task, $data) {

            $user = $this->getAuthenticatedEmployee();

            $this->ensureTaskCanBeSubmitted($task);

            $this->ensureUserAssignedToTask(
                task: $task,
                user: $user
            );

            $existingSubmission = Submission::where('task_id', $task->id)
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    SubmissionStatus::PENDING_REVIEW->value,
                    SubmissionStatus::APPROVED->value,
                ])
                ->exists();

            if ($existingSubmission) {
                throw ValidationException::withMessages([
                    'submission' => 'You already have an active submission for this task.',
                ]);
            }

            return Submission::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'note' => $data['note'] ?? null,
                'status' => SubmissionStatus::PENDING_REVIEW,
                'submitted_at' => now(),
            ]);
        });

        if ($task->creator) {
            SendNotificationJob::dispatch(
                user: $task->creator,
                type: 'submission_created',
                titleKey: 'notifications.submission_created_title',
                bodyKey: 'notifications.submission_created_body',
                parameters: [
                    'title' => $task->title,
                ],
                metadata: [
                    'submission_id' => $submission->id,
                    'task_id' => $task->id,
                ]
            );
        }

        return $submission;
    }

    /**
     * Attach a file to a submission.
     */
    public function attachFile(
        Submission $submission,
        UploadedFile $file
    ): SubmissionAttachment {
        return DB::transaction(function () use (
            $submission,
            $file
        ) {
            $user = $this->getAuthenticatedEmployee();

            if ((int) $submission->user_id !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'submission' => 'You are not the owner of this submission.',
                ]);
            }

            if ($submission->status === SubmissionStatus::APPROVED) {
                throw ValidationException::withMessages([
                    'submission' => 'Approved submissions cannot be modified.',
                ]);
            }

            return $this->fileService->uploadSubmissionAttachment(
                file: $file,
                user: $user,
                submission: $submission
            );
        });
    }

    /**
     * Show a submission.
     */
    public function show(Submission $submission): Submission
    {
        $user = Auth::user();

        if (! $user) {
            throw ValidationException::withMessages([
                'user' => __('submissions.unauthenticated'),
            ]);
        }

        /*
         * The submission owner can always view their submission.
         */
        if ((int) $submission->user_id === (int) $user->id) {
            return $this->loadSubmission($submission);
        }

        /*
         * Owner and HR can view all submissions.
         */
        if ($user->isOwner() || $user->isHR()) {
            return $this->loadSubmission($submission);
        }

        /*
         * Managers can only view submissions related
         * to tasks they created or assigned.
         */
        if ($user->isManager()) {
            $this->ensureReviewerCanAccess($submission);

            return $this->loadSubmission($submission);
        }

        throw ValidationException::withMessages([
            'submission' => __('submissions.unauthorized_view'),
        ]);
    }

    /**
     * Get pending submissions available for review.
     */
    public function reviewQueue()
    {
        $user = Auth::user();

        $query = Submission::with([
            'task',
            'user',
            'attachments',
        ])->where(
            'status',
            SubmissionStatus::PENDING_REVIEW
        );

        /*
         * Owner and HR can review all pending submissions.
         */
        if ($user->isOwner() || $user->isHR()) {
            return $query
                ->latest('submitted_at')
                ->paginate(15);
        }

        /*
         * Managers can review submissions related
         * to tasks they created or assigned.
         */
        if ($user->isManager()) {
            return $query
                ->whereHas('task', function ($taskQuery) use ($user) {
                    $taskQuery
                        ->where('created_by', $user->id)
                        ->orWhereHas(
                            'assignments',
                            function ($assignmentQuery) use ($user) {
                                $assignmentQuery->where(
                                    'assigned_by',
                                    $user->id
                                );
                            }
                        );
                })
                ->latest('submitted_at')
                ->paginate(15);
        }

        throw ValidationException::withMessages([
            'reviewer' => __('submissions.unauthorized_review'),
        ]);
    }

    /**
     * Approve a submission.
     */
    public function approve(Submission $submission): Submission
    {
        $submission = DB::transaction(function () use ($submission) {

            $this->ensureReviewerCanAccess($submission);

            $this->ensureSubmissionCanBeReviewed($submission);

            $submission->update([
                'status' => SubmissionStatus::APPROVED,
            ]);

            $this->createReview(
                submission: $submission,
                action: 'approved',
                feedback: null
            );

            return $submission->refresh();
        });

        if ($submission->user) {
            SendNotificationJob::dispatch(
                user: $submission->user,
                type: 'submission_approved',
                titleKey: 'notifications.submission_approved_title',
                bodyKey: 'notifications.submission_approved_body',
                parameters: [
                    'title' => $submission->task?->title,
                ],
                metadata: [
                    'submission_id' => $submission->id,
                ]
            );
        }

        return $submission;
    }

    /**
     * Reject a submission.
     */
    public function reject(Submission $submission, string $feedback): Submission
    {
        $submission = DB::transaction(function () use (
            $submission,
            $feedback
        ) {
            $this->ensureReviewerCanAccess($submission);

            $this->ensureSubmissionCanBeReviewed($submission);

            $submission->update([
                'status' => SubmissionStatus::REJECTED,
            ]);

            $this->createReview(
                submission: $submission,
                action: 'rejected',
                feedback: $feedback
            );

            return $submission->refresh();
        });

        if ($submission->user) {
            SendNotificationJob::dispatch(
                user: $submission->user,
                type: 'submission_rejected',
                titleKey: 'notifications.submission_rejected_title',
                bodyKey: 'notifications.submission_rejected_body',
                parameters: [
                    'title' => $submission->task?->title,
                ],
                metadata: [
                    'submission_id' => $submission->id,
                    'feedback' => $feedback,
                ]
            );
        }

        return $submission;
    }

    /**
     * Request changes on a submission.
     */
    public function requestChanges(Submission $submission, string $feedback): Submission
    {
        $submission = DB::transaction(function () use (
            $submission,
            $feedback
        ) {
            $this->ensureReviewerCanAccess($submission);

            $this->ensureSubmissionCanBeReviewed($submission);

            $submission->update([
                'status' => SubmissionStatus::CHANGES_REQUESTED,
            ]);

            $this->createReview(
                submission: $submission,
                action: 'changes_requested',
                feedback: $feedback
            );

            return $submission->refresh();
        });

        if ($submission->user) {
            SendNotificationJob::dispatch(
                user: $submission->user,
                type: 'submission_changes_requested',
                titleKey: 'notifications.submission_changes_title',
                bodyKey: 'notifications.submission_changes_body',
                parameters: [
                    'title' => $submission->task?->title,
                ],
                metadata: [
                    'submission_id' => $submission->id,
                ]
            );
        }

        return $submission;
    }

    /**
     * Resubmit a submission after changes were requested.
     */
    public function resubmit(Submission $submission, ?string $note = null): Submission
    {
        return DB::transaction(function () use (
            $submission,
            $note
        ) {
            $user = $this->getAuthenticatedEmployee();

            if ((int) $submission->user_id !== (int) $user->id) {
                throw ValidationException::withMessages([
                    'submission' => __('submissions.not_submission_owner'),
                ]);
            }

            if (
                $submission->status !==
                SubmissionStatus::CHANGES_REQUESTED
            ) {
                throw ValidationException::withMessages([
                    'submissions.only_changes_requested_can_be_resubmitted',
                ]);
            }

            $submission->update([
                'status' => SubmissionStatus::PENDING_REVIEW,
                'note' => $note ?? $submission->note,
                'submitted_at' => now(),
            ]);

            return $submission->refresh();
        });
    }

    /**
     * Get the authenticated user and make sure
     * the user has the Employee role.
     */
    private function getAuthenticatedEmployee(): User
    {
        $user = Auth::user();

        if (! $user) {
            throw ValidationException::withMessages([
                'user' => 'Unauthenticated user.',
            ]);
        }

        if (! $user->isEmployee()) {
            throw ValidationException::withMessages([
                'user' => 'Only employees can submit tasks.',
            ]);
        }

        return $user;
    }

    /**
     * Make sure the task can receive submissions.
     */
    private function ensureTaskCanBeSubmitted(
        Task $task
    ): void {
        if ($task->status === TaskStatus::CLOSED) {
            throw ValidationException::withMessages([
                'task' => 'Closed tasks cannot receive submissions.',
            ]);
        }
    }

    /**
     * Make sure the user is assigned to the task.
     */
    private function ensureUserAssignedToTask(
        Task $task,
        User $user
    ): void {
        $assigned = $task->assignments()
            ->where('user_id', $user->id)
            ->exists();

        if (! $assigned) {
            throw ValidationException::withMessages([
                'task' => 'You are not assigned to this task.',
            ]);
        }
    }

    /**
     * Make sure the submission is waiting for review.
     */
    private function ensureSubmissionCanBeReviewed(Submission $submission): void
    {
        if (
            $submission->status !== SubmissionStatus::PENDING_REVIEW
        ) {
            throw ValidationException::withMessages([
                'submission' => __('submissions.not_waiting_for_review'),
            ]);
        }
    }

    /**
     * Make sure the authenticated reviewer
     * can access the submission.
     */
    private function ensureReviewerCanAccess(Submission $submission): void
    {
        $user = Auth::user();

        if ($user->isOwner() || $user->isHR()) {
            return;
        }

        if (! $user->isManager()) {
            throw ValidationException::withMessages([
                'reviewer' => __('submissions.unauthorized_review'),
            ]);
        }

        if (
            (int) $submission->task->created_by ===
            (int) $user->id
        ) {
            return;
        }

        $assignedByManager = $submission->task
            ->assignments()
            ->where('assigned_by', $user->id)
            ->exists();

        if ($assignedByManager) {
            return;
        }

        throw ValidationException::withMessages([
            'submission' => __('submissions.unauthorized_review'),
        ]);
    }

    /**
     * Create a review record.
     */
    private function createReview(
        Submission $submission,
        string $action,
        ?string $feedback
    ): SubmissionReview {
        return SubmissionReview::create([
            'submission_id' => $submission->id,
            'reviewer_id' => Auth::id(),
            'action' => $action,
            'feedback' => $feedback,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Load submission relationships.
     */
    private function loadSubmission(Submission $submission): Submission
    {
        return $submission->load([
            'task',
            'user',
            'attachments',
            'reviews.reviewer',
        ]);
    }
}
