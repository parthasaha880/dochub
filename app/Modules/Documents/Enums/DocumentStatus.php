<?php

namespace App\Modules\Documents\Enums;

enum DocumentStatus: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Expired = 'expired';
    case Quarantined = 'quarantined';
}
