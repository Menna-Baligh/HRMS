<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case PENDING_REVIEW = 'Pending Review';
    case APPROVED = 'Approved';
    case REJECTED = 'Rejected';
    case CHANGES_REQUESTED = 'Changes Requested';
}
