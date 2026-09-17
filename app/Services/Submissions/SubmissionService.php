<?php

namespace App\Services\Submissions;

use App\Enums\SubmissionStatus;
use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Submission;
use App\Models\SubmissionAttachment;
use App\Models\SubmissionReview;
use App\Models\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmissionService
{
    /**
     * Create a task submission.
     */
    public function create(Task $task, array $data): Submission
    {
        return DB::transaction(function () use ($task, $data) {

            $employee = $this->getAuthenticatedEmployee();

            $this->ensureTaskCanBeSubmitted($task);

            $this->ensureEmployeeAssignedToTask(
                $task,
                $employee
            );

            $existingSubmission = Submission::where('task_id', $task->id)
                ->where('employee_id', $employee->id)
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
                'employee_id' => $employee->id,
                'note' => $data['note'] ?? null,
                'status' => SubmissionStatus::PENDING_REVIEW,
                'submitted_at' => now(),
            ]);
        });
    }

    /**
     * Attach a file to a submission.
     */
    public function attachFile(
        Submission $submission,
        UploadedFile $file
    ): SubmissionAttachment {
        return DB::transaction(function () use ($submission, $file) {

            $employee = $this->getAuthenticatedEmployee();

            if ($submission->employee_id !== $employee->id) {
                throw ValidationException::withMessages([
                    'submission' => 'You are not the owner of this submission.',
                ]);
            }

            if ($submission->status === SubmissionStatus::APPROVED) {
                throw ValidationException::withMessages([
                    'submission' => 'Approved submissions cannot be modified.',
                ]);
            }

            $path = $file->store(
                'submissions/'.$submission->id,
                'private'
            );

            return SubmissionAttachment::create([
                'submission_id' => $submission->id,
                'uploaded_by' => Auth::id(),
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        });
    }

    /**
     * Get submission details.
     */
    public function show(Submission $submission): Submission
    {
        $user = Auth::user();

        $employee = Employee::where('user_id', $user->id)->first();

        if ($employee && $submission->employee_id === $employee->id) {
            return $submission->load([
                'task',
                'employee',
                'attachments',
                'reviews.reviewer',
            ]);
        }

        // Temporary manager/HR access.
        if (in_array($user->role, ['Owner', 'HR', 'Manager'], true)) {
            return $submission->load([
                'task',
                'employee',
                'attachments',
                'reviews.reviewer',
            ]);
        }

        throw ValidationException::withMessages([
            'submission' => 'You are not authorized to view this submission.',
        ]);
    }

    /**
     * Get submissions waiting for review.
     */
    /**
     * Get submissions waiting for review
     * within the authenticated user's scope.
     */
    public function reviewQueue()
    {
        $user = Auth::user();

        $query = Submission::with([
            'task',
            'employee',
            'attachments',
        ])
            ->where(
                'status',
                SubmissionStatus::PENDING_REVIEW
            );

        if (in_array($user->role, ['Owner', 'HR'], true)) {
            return $query
                ->latest('submitted_at')
                ->paginate(15);
        }

        $manager = Employee::where(
            'user_id',
            $user->id
        )->first();

        if (! $manager) {
            throw ValidationException::withMessages([
                'reviewer' => 'The authenticated user is not linked to an employee.',
            ]);
        }

        return $query
            ->whereHas('employee.department', function ($departmentQuery) use ($manager) {
                $departmentQuery->where('manager_id', $manager->id);
            })
            ->latest('submitted_at')
            ->paginate(15);
    }

    /**
     * Approve a submission.
     */
    public function approve(Submission $submission): Submission
    {
        return DB::transaction(function () use ($submission) {

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
    }

    /**
     * Reject a submission.
     */
    public function reject(
        Submission $submission,
        string $feedback
    ): Submission {
        return DB::transaction(function () use ($submission, $feedback) {

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
    }

    /**
     * Request changes from employee.
     */
    public function requestChanges(
        Submission $submission,
        string $feedback
    ): Submission {
        return DB::transaction(function () use ($submission, $feedback) {

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
    }

    /**
     * Make sure the submission can be reviewed.
     */
    private function ensureSubmissionCanBeReviewed(
        Submission $submission
    ): void {
        if (
            $submission->status !==
            SubmissionStatus::PENDING_REVIEW
        ) {
            throw ValidationException::withMessages([
                'submission' => 'This submission is not waiting for review.',
            ]);
        }
    }

    /**
     * Create a submission review history record.
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
     * Resubmit a submission after changes were requested.
     */
    public function resubmit(
        Submission $submission,
        ?string $note = null
    ): Submission {
        return DB::transaction(function () use ($submission, $note) {

            $employee = $this->getAuthenticatedEmployee();

            if ($submission->employee_id !== $employee->id) {
                throw ValidationException::withMessages([
                    'submission' => 'You are not the owner of this submission.',
                ]);
            }

            if (
                $submission->status !==
                SubmissionStatus::CHANGES_REQUESTED
            ) {
                throw ValidationException::withMessages([
                    'submission' => 'Only submissions with requested changes can be resubmitted.',
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
     * Get authenticated employee.
     */
    private function getAuthenticatedEmployee(): Employee
    {
        $employee = Employee::where('user_id', Auth::id())->first();

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee' => 'The authenticated user is not linked to an employee.',
            ]);
        }

        return $employee;
    }

    /**
     * Make sure the task accepts submissions.
     */
    private function ensureTaskCanBeSubmitted(Task $task): void
    {
        if ($task->status === TaskStatus::CLOSED) {
            throw ValidationException::withMessages([
                'task' => 'Closed tasks cannot receive submissions.',
            ]);
        }
    }

    /**
     * Make sure the employee is assigned to the task.
     */
    private function ensureEmployeeAssignedToTask(
        Task $task,
        Employee $employee
    ): void {
        $assigned = $task->assignments()
            ->where('employee_id', $employee->id)
            ->exists();

        if (! $assigned) {
            throw ValidationException::withMessages([
                'task' => 'You are not assigned to this task.',
            ]);
        }
    }

    /**
     * Make sure the authenticated user can review
     * the selected submission.
     */
    private function ensureReviewerCanAccess(
        Submission $submission
    ): void {
        $user = Auth::user();

        // Owner and HR can review submissions.
        if (in_array($user->role, ['Owner', 'HR'], true)) {
            return;
        }

        // Manager must be linked to an employee.
        $manager = Employee::where('user_id', $user->id)->first();

        if (! $manager) {
            throw ValidationException::withMessages([
                'reviewer' => 'The authenticated user is not linked to an employee.',
            ]);
        }

        $employee = $submission->employee()->first();

        if (! $employee) {
            throw ValidationException::withMessages([
                'submission' => 'Submission employee was not found.',
            ]);
        }

        $isManagerOfEmployee = $employee->department()
            ->where('manager_id', $manager->id)
            ->exists();

        if (! $isManagerOfEmployee) {
            throw ValidationException::withMessages([
                'submission' => 'You are not authorized to review this submission.',
            ]);
        }
    }
}
