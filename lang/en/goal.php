<?php

return [
    'retrieved'             => 'Employee goals retrieved successfully.',
    'details_retrieved'     => 'Goal details retrieved successfully.',
    'created'               => 'Goal created successfully.',
    'updated'               => 'Goal details updated successfully.',
    'progress_updated'      => 'Goal progress updated successfully.',
    'completed'             => 'Goal marked as completed successfully.',
    'not_found'             => 'Goal not found or access denied.',
    'user_not_found'        => 'Employee profile not found.',
    'cannot_modify_closed'  => 'Completed or cancelled goals cannot be modified.',
    'cannot_update_cancelled' => 'Cannot update progress for a cancelled goal.',
    'already_completed'     => 'Goal is already marked as completed.',
    'failed_retrieve'       => 'Failed to retrieve goals.',
    'failed_details'        => 'Failed to retrieve goal details.',
    'failed_create'         => 'Failed to create goal.',
    'failed_update'         => 'Failed to update goal details.',
    'failed_progress'       => 'Failed to update goal progress.',
    'failed_complete'       => 'Failed to mark goal as completed.',
    'team_goals_retrieved'  => 'Team goals retrieved successfully.',
    'company_overview'      => 'Company goals overview retrieved successfully.',
    'manager_not_found'     => 'Manager profile not found.',
    'failed_team_goals'     => 'Failed to retrieve team goals.',
    'failed_company_overview' => 'Failed to retrieve company goals overview.',
    
    'errors' => [
        'value_exceeds_target' => 'The current value cannot exceed the goal target value of (:target).',
    ],
    'attributes' => [
        'current_value' => 'Current Value',
        'note'          => 'Note',
    ],

    'statuses' => [
        'active'    => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
];
