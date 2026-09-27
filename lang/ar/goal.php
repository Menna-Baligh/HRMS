<?php

return [
    'retrieved' => 'تم جلب الأهداف بنجاح.',
    'details_retrieved' => 'تم جلب تفاصيل الهدف بنجاح.',
    'created' => 'تم إنشاء الهدف بنجاح.',
    'updated' => 'تم تحديث تفاصيل الهدف بنجاح.',
    'progress_updated' => 'تم تحديث التقدم في الهدف بنجاح.',
    'completed' => 'تم تعليم الهدف كمكتمل بنجاح.',
    'not_found' => 'الهدف غير موجود أو غير مصرح بالوصول إليه.',
    'user_not_found' => 'حساب الموظف غير موجود.',
    'cannot_modify_closed' => 'لا يمكن تعديل الأهداف المكتملة أو الملغاة.',
    'cannot_update_cancelled' => 'لا يمكن تحديث التقدم لهدف ملغى.',
    'already_completed' => 'تم تعليم هذا الهدف كمكتمل بالفعل.',
    'failed_retrieve' => 'فشل في جلب الأهداف.',
    'failed_details' => 'فشل في جلب تفاصيل الهدف.',
    'failed_create' => 'فشل في إنشاء الهدف.',
    'failed_update' => 'فشل في تحديث الهدف.',
    'failed_progress' => 'فشل في تحديث تقدم الهدف.',
    'failed_complete' => 'فشل في تعليم الهدف كمكتمل.',
    'team_goals_retrieved' => 'تم جلب أهداف الفريق بنجاح.',
    'company_overview' => 'تم جلب نظرة عامة على أهداف الشركة بنجاح.',
    'manager_not_found' => 'حساب المدير غير موجود.',
    'failed_team_goals' => 'فشل في جلب أهداف الفريق.',

    'errors' => [
        'value_exceeds_target' => 'القيمة الحالية لا يمكن أن تتجاوز القيمة المستهدفة للهدف (:target).',
    ],
    'attributes' => [
        'current_value' => 'القيمة الحالية',
        'note' => 'الملاحظة',
    ],

    'statuses' => [
        'active' => 'نشط',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغى',
    ],
    'validation' => [
        'title_required' => 'عنوان الهدف مطلوب.',
        'title_string' => 'يجب أن يكون عنوان الهدف نصاً.',
        'title_max' => 'يجب ألا يتجاوز عنوان الهدف 255 حرفاً.',
        'description_string' => 'يجب أن يكون وصف الهدف نصاً.',
        'target_date_required' => 'تاريخ الاستهداف مطلوب.',
        'target_date_date' => 'صيغة تاريخ الاستهداف غير صالحة.',
        'target_date_after_or_equal' => 'يجب أن يكون تاريخ الاستهداف اليوم أو تاريخاً مستقبلياً.',
        'employee_id_required' => 'حقل الموظف مطلوب.',
        'employee_id_integer' => 'معرف الموظف يجب أن يكون رقماً صحيحاً.',
        'employee_id_exists' => 'الموظف المحدّد غير موجود بالنظام.',
        'status_required' => 'حالة الهدف مطلوبة.',
        'status_enum' => 'حالة الهدف غير صالحة.',
    ],
];
