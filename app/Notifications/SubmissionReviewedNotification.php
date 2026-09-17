<?php

namespace App\Notifications;

use App\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SubmissionReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Submission $submission,
        private string $action,
        private ?string $feedback = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'submission_review',
            'submission_id' => $this->submission->id,
            'task_id' => $this->submission->task_id,
            'action' => $this->action,
            'feedback' => $this->feedback,
            'message' => $this->buildMessage(),
        ];
    }

    private function buildMessage(): string
    {
        return match ($this->action) {
            'approved' => 'Your task submission has been approved.',

            'rejected' => 'Your task submission has been rejected.',

            'changes_requested' => 'Changes have been requested for your task submission.',

            default => 'Your task submission has been reviewed.',
        };
    }
}
