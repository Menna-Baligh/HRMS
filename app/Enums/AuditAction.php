<?php

namespace App\Enums;

enum AuditAction: string
{
    // Leave events
    case LEAVE_CREATED = 'leave_created';
    case LEAVE_APPROVED = 'leave_approved';
    case LEAVE_REJECTED = 'leave_rejected';
    case LEAVE_CANCELLED = 'leave_cancelled';
    case LEAVE_UPDATED = 'leave_updated';

    // Task events
    case TASK_CREATED = 'task_created';
    case TASK_UPDATED = 'task_updated';
    case TASK_ASSIGNED = 'task_assigned';
    case TASK_UNASSIGNED = 'task_unassigned';
    case TASK_STATUS_CHANGED = 'task_status_changed';
    case TASK_PROGRESS_UPDATED = 'task_progress_updated';

    // Submission events
    case SUBMISSION_CREATED = 'submission_created';
    case SUBMISSION_REVIEWED = 'submission_reviewed';
    case SUBMISSION_APPROVED = 'submission_approved';
    case SUBMISSION_REJECTED = 'submission_rejected';
    case SUBMISSION_CHANGES_REQUESTED = 'submission_changes_requested';

    // Evaluation events
    case EVALUATION_CREATED = 'evaluation_created';
    case EVALUATION_UPDATED = 'evaluation_updated';
    case EVALUATION_SUBMITTED = 'evaluation_submitted';
    case EVALUATION_APPROVED = 'evaluation_approved';

    // Policy events
    case POLICY_CREATED = 'policy_created';
    case POLICY_UPDATED = 'policy_updated';
    case POLICY_ACTIVATED = 'policy_activated';
    case POLICY_DEACTIVATED = 'policy_deactivated';

    // AI events
    case AI_CAREER_COACH_USED = 'ai_career_coach_used';
    case AI_POLICY_ASSISTANT_USED = 'ai_policy_assistant_used';
    case AI_PERFORMANCE_INSIGHT_GENERATED = 'ai_performance_insight_generated';
    case AI_EVALUATION_DRAFT_GENERATED = 'ai_evaluation_draft_generated';
}
