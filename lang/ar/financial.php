<?php

return [
    'advances' => [
        'retrieved' => 'تم جلب طلبات السلف بنجاح.',
        'created' => 'تم تقديم طلب السلفة بنجاح.',
        'status_updated' => 'تم تحديث حالة طلب السلفة بنجاح.',
    ],
    'deductions' => [
        'retrieved' => 'تم جلب الخصومات والجزاءات بنجاح.',
        'created' => 'تم تسجيل الخصم بنجاح.',
    ],
    'validation' => [
        'user_id' => [
            'required' => 'حقل الموظف مطلوب.',
            'exists' => 'الموظف المختار غير موجود.',
        ],
        'requested_amount' => [
            'required' => 'مبلغ السلفة مطلوب.',
            'numeric' => 'يجب أن يكون مبلغ السلفة رقماً.',
            'gt' => 'يجب أن يكون مبلغ السلفة أكبر من الصفر.',
        ],
        'repayment_months' => [
            'required' => 'عدد شهور السداد مطلوب.',
            'integer' => 'يجب أن يكون عدد شهور السداد رقماً صحيحاً.',
            'min' => 'يجب ألا تقل فترة السداد عن شهر واحد.',
        ],
        'amount' => [
            'required' => 'مبلغ الخصم مطلوب.',
            'numeric' => 'يجب أن يكون مبلغ الخصم رقماً.',
            'gt' => 'يجب أن يكون مبلغ الخصم أكبر من الصفر.',
        ],
        'reason' => [
            'required' => 'سبب الطلب أو الخصم مطلوب.',
            'string' => 'يجب أن يكون السبب نصاً صحيحاً.',
            'max' => 'يجب ألا يتجاوز النص 255 حرفاً.',
        ],
        'date' => [
            'required' => 'حقل التاريخ مطلوب.',
            'date' => 'صيغة التاريخ غير صحيحة.',
        ],
        'status' => [
            'required' => 'حقل الحالة مطلوب.',
            'in' => 'يجب أن تكون الحالة إما مقبول (approved) أو مرفوض (rejected).',
        ],
        'type' => [
            'in' => 'نوع الخصم يجب أن يكون إما يدوي (manual) أو تأخير (delay).',
        ],
        'incentive_type' => [
            'required' => 'نوع الحافز مطلوب.',
            'string' => 'يجب أن يكون نوع الحافز نصاً صحيحاً.',
        ],
        'target_month' => [
            'required' => 'الشهر المستهدف مطلوب.',
            'string' => 'صيغة الشهر المستهدف غير صحيحة.',
        ],
    ],
    'attributes' => [
        'user_id' => 'الموظف',
        'requested_amount' => 'مبلغ السلفة',
        'repayment_months' => 'شهور السداد',
        'monthly_deduction' => 'القسط الشهري',
        'amount' => 'مبلغ الخصم',
        'reason' => 'السبب',
        'date' => 'التاريخ',
        'type' => 'نوع الخصم',
        'status' => 'الحالة',
    ],
    'notifications' => [
        'advance_requested' => [
            'title' => 'طلب سلفة جديد',
            'body' => 'قام الموظف :employee بتقديم طلب سلفة بمبلغ :amount$.',
        ],
        'advance_status_updated' => [
            'title' => 'تحديث حالة طلب السلفة',
            'body' => 'تمت :status طلب السلفة الخاص بك بمبلغ :amount$.',
        ],
        'deduction_recorded' => [
            'title' => 'تسجيل خصم جديد',
            'body' => 'تم تسجيل خصم بقيمة :amount$ على حسابك. السبب: :reason.',
        ],
        'bonus_issued' => [
            'title' => 'تم منحك حافز جديد',
            'body' => 'تم إدراج حافز جديد (:type) بقيمة :amount$ لشهر :month.',
        ],
        'bonus_status_updated' => [
            'title' => 'تحديث حالة الحافز',
            'body' => 'تمت :status الحافز الخاص بك لشهر :month بمبلغ :amount$.',
        ],
    ],
    'bonuses' => [
        'retrieved' => 'تم جلب المكافآت والحوافز بنجاح.',
        'created' => 'تم منح الحافز للموظف بنجاح.',
        'status_updated' => 'تم تحديث حالة الحافز بنجاح.',
    ],
];
