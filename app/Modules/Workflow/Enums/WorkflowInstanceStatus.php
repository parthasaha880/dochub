<?php

namespace App\Modules\Workflow\Enums;

enum WorkflowInstanceStatus: string
{
    case InProgress = 'in_progress';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
}
