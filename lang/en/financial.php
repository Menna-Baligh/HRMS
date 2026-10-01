<?php

return [
    'advances' => [
        'retrieved' => 'Salary advances retrieved successfully.',
        'created' => 'Salary advance request submitted successfully.',
        'status_updated' => 'Salary advance status updated successfully.',
    ],
    'deductions' => [
        'retrieved' => 'Deductions retrieved successfully.',
        'created' => 'Deduction recorded successfully.',
    ],
    'validation' => [
        'user_id' => [
            'required' => 'The employee field is required.',
            'exists' => 'The selected employee is invalid.',
        ],
        'requested_amount' => [
            'required' => 'The requested amount is required.',
            'numeric' => 'The requested amount must be a number.',
            'gt' => 'The requested amount must be greater than zero.',
        ],
        'repayment_months' => [
            'required' => 'The repayment months field is required.',
            'integer' => 'The repayment months must be an integer.',
            'min' => 'The repayment period must be at least 1 month.',
        ],
        'amount' => [
            'required' => 'The deduction amount is required.',
            'numeric' => 'The deduction amount must be a number.',
            'gt' => 'The deduction amount must be greater than zero.',
        ],
        'reason' => [
            'required' => 'The reason field is required.',
            'string' => 'The reason must be a valid string.',
            'max' => 'The reason may not be greater than 255 characters.',
        ],
        'date' => [
            'required' => 'The date field is required.',
            'date' => 'The date provided is not a valid date format.',
        ],
        'status' => [
            'required' => 'The status field is required.',
            'in' => 'The status must be either approved or rejected.',
        ],
        'type' => [
            'in' => 'The deduction type must be either manual or delay.',
        ],
        'incentive_type' => [
            'required' => 'The incentive type field is required.',
            'string' => 'The incentive type must be a valid string.',
        ],
        'target_month' => [
            'required' => 'The target month is required.',
            'string' => 'The target month format must be valid.',
        ],
        'month_year' => [
            'required' => 'The month and year field is required.',
            'string' => 'The month and year format must be valid (e.g. 2026-09).',
        ],
    ],
    'attributes' => [
        'user_id' => 'employee',
        'requested_amount' => 'requested amount',
        'repayment_months' => 'repayment months',
        'monthly_deduction' => 'monthly deduction',
        'amount' => 'amount',
        'reason' => 'reason',
        'date' => 'date',
        'type' => 'deduction type',
        'status' => 'status',
    ],
    'notifications' => [
        'advance_requested' => [
            'title' => 'New Salary Advance Request',
            'body' => ':employee has submitted a salary advance request of $:amount.',
        ],
        'advance_status_updated' => [
            'title' => 'Salary Advance Request :status',
            'body' => 'Your salary advance request of $:amount has been :status.',
        ],
        'deduction_recorded' => [
            'title' => 'New Deduction Recorded',
            'body' => 'A deduction of $:amount has been recorded on your account. Reason: :reason.',
        ],
        'bonus_issued' => [
        'title' => 'New Incentive Issued',
        'body' => 'A new incentive (:type) of $:amount has been assigned to you for :month.',
        ],
        'bonus_status_updated' => [
            'title' => 'Incentive Status :status',
            'body' => 'Your incentive for :month of $:amount has been :status.',
        ],
        'payroll_finalized' => [
            'title' => 'Monthly Salary Processed 💰',
            'body' => 'Your net salary of $:amount for :month has been finalized.',
        ],
    ],
    'bonuses' => [
        'retrieved' => 'Rewards and bonuses retrieved successfully.',
        'created' => 'Incentive issued successfully.',
        'status_updated' => 'Incentive status updated successfully.',
    ],
    'payroll' => [
    'retrieved' => 'Payroll calculations retrieved successfully.',
    'finalized' => 'Payroll for :month has been finalized successfully.',
    'payslip_retrieved' => 'Payslip retrieved successfully.',
    'already_finalized' => 'Payroll for this month has already been finalized.',
    'finalized_status' => 'Payroll for this month is finalized.',
    'draft_status' => 'Payroll for this month is still in draft (pending approval).',
    'already_finalized_all' => 'All employees payrolls for this month are already finalized.',
    ],
];
