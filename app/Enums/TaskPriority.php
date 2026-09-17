<?php

namespace App\Enums;

enum TaskPriority: string
{
    case LOW = 'Low';
    case MEDIUM = 'Medium';
    case HIGH = 'High';
    case URGENT = 'Urgent';
}
