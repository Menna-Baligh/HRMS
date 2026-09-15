<?php

namespace App\Enums;

enum EvaluationStatus: string
{
    case DRAFT = 'draft';
    case COMPLETED = 'completed';
}
