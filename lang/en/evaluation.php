<?php

return [
    'overview_retrieved'         => 'Company evaluations overview retrieved successfully.',
    'periods_retrieved'          => 'Evaluation periods retrieved successfully.',
    'period_created'             => 'Evaluation period created successfully.',
    'period_not_found'           => 'Evaluation period not found.',
    'period_status_changed'      => 'Evaluation period status changed to :status.',
    'categories_retrieved'       => 'Evaluation categories retrieved successfully.',
    'category_created'           => 'Evaluation category created successfully.',
    'failed_overview'            => 'Failed to retrieve company evaluations overview.',
    'failed_periods'             => 'Failed to retrieve periods.',
    'failed_create_period'       => 'Failed to create period.',
    'failed_update_status'       => 'Failed to update period status.',
    'failed_categories'          => 'Failed to retrieve categories.',
    'failed_create_category'     => 'Failed to create category.',

    'errors' => [
        'direct_reports_only'    => 'You can only evaluate employees within your direct team.',
        'completed_immutable'    => 'Completed evaluations cannot be modified as draft.',
        'closed_period'          => 'Cannot create evaluation for a closed period.',
        'already_completed'      => 'Evaluation is already completed.',
        'no_scores'              => 'Cannot complete evaluation without category scores.',
    ],

    'statuses' => [
        'active'    => 'Active',
        'closed'    => 'Closed',
        'draft'     => 'Draft',
        'completed' => 'Completed',
    ],
];
