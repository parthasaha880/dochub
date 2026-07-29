<?php

namespace App\Modules\Retention\Enums;

enum RetentionAction: string
{
    case Archive = 'archive';
    case SoftDelete = 'soft_delete';
    case Flag = 'flag';
}
