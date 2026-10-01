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
    ],
];
