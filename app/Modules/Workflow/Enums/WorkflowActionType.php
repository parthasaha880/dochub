<?php

namespace App\Modules\Workflow\Enums;

enum WorkflowActionType: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
    case Resubmitted = 'resubmitted';
    case Commented = 'commented';
}
