<?php

return [
    'retrieved' => 'Employee goals retrieved successfully.',
    'details_retrieved' => 'Goal details retrieved successfully.',
    'created' => 'Goal created successfully.',
    'updated' => 'Goal details updated successfully.',
    'progress_updated' => 'Goal progress updated successfully.',
    'completed' => 'Goal marked as completed successfully.',
    'not_found' => 'Goal not found or access denied.',
    'user_not_found' => 'Employee profile not found.',
    'cannot_modify_closed' => 'Completed or cancelled goals cannot be modified.',
    'cannot_update_cancelled' => 'Cannot update progress for a cancelled goal.',
    'already_completed' => 'Goal is already marked as completed.',
    'failed_retrieve' => 'Failed to retrieve goals.',
    'failed_details' => 'Failed to retrieve goal details.',
    'failed_create' => 'Failed to create goal.',
    'failed_update' => 'Failed to update goal details.',
    'failed_progress' => 'Failed to update goal progress.',
    'failed_complete' => 'Failed to mark goal as completed.',
    'team_goals_retrieved' => 'Team goals retrieved successfully.',
    'company_overview' => 'Company goals overview retrieved successfully.',
    'manager_not_found' => 'Manager profile not found.',
    'failed_team_goals' => 'Failed to retrieve team goals.',
    'failed_company_overview' => 'Failed to retrieve company goals overview.',

    'errors' => [
        'value_exceeds_target' => 'The current value cannot exceed the goal target value of (:target).',
    ],
    'attributes' => [
        'current_value' => 'Current Value',
        'note' => 'Note',
    ],

    'statuses' => [
        'active' => 'Active',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],
    'validation' => [
        'title_required' => 'Goal title is required.',
        'title_string' => 'Goal title must be a string.',
        'title_max' => 'Goal title must not exceed 255 characters.',
        'description_string' => 'Goal description must be a string.',
        'target_date_required' => 'Target date is required.',
        'target_date_date' => 'Target date must be a valid date.',
        'target_date_after_or_equal' => 'Target date must be today or a future date.',
        'employee_id_required' => 'Employee selection is required.',
        'employee_id_integer' => 'Employee ID must be an integer.',
        'employee_id_exists' => 'The selected employee does not exist.',
        'status_required' => 'Goal status is required.',
        'status_enum' => 'Selected goal status is invalid.',
    ],
];
