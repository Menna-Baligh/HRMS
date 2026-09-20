<?php

return [
    'overview_retrieved'         => 'تم جلب نظرة عامة على تقييمات الشركة بنجاح.',
    'periods_retrieved'          => 'تم جلب فترات التقييم بنجاح.',
    'period_created'             => 'تم إنشاء فترة التقييم بنجاح.',
    'period_not_found'           => 'فترة التقييم غير موجودة.',
    'period_status_changed'      => 'تم تغيير حالة فترة التقييم إلى :status.',
    'categories_retrieved'       => 'تم جلب فئات التقييم بنجاح.',
    'category_created'           => 'تم إنشاء فئة التقييم بنجاح.',
    'failed_overview'            => 'فشل في جلب تقييمات الشركة.',
    'failed_periods'             => 'فشل في جلب فترات التقييم.',
    'failed_create_period'       => 'فشل في إنشاء فترة التقييم.',
    'failed_update_status'       => 'فشل في تحديث حالة فترة التقييم.',
    'failed_categories'          => 'فشل في جلب فئات التقييم.',
    'failed_create_category'     => 'فشل في إنشاء فئة التقييم.',

    'errors' => [
        'direct_reports_only'    => 'يمكنك تقييم الموظفين التابعين لفريقك المباشر فقط.',
        'completed_immutable'    => 'لا يمكن تعديل التقييم المكتمل كمسودة.',
        'closed_period'          => 'لا يمكن إنشاء تقييم لفترة مغلقة.',
        'already_completed'      => 'هذا التقييم مكتمل بالفعل.',
        'no_scores'              => 'لا يمكن إكمال التقييم بدون وضع درجات للفئات.',
    ],

    'statuses' => [
        'active'    => 'نشط',
        'closed'    => 'مغلق',
        'draft'     => 'مسودة',
        'completed' => 'مكتمل',
    ],
];
