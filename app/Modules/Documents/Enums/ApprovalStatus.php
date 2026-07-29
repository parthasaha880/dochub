<?php

namespace App\Modules\Documents\Enums;

enum ApprovalStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Returned = 'returned';
    case Archived = 'archived';
    case Published = 'published';
}
