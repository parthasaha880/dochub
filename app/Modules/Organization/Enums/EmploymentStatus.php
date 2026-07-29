<?php

namespace App\Modules\Organization\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Resigned = 'resigned';
    case Terminated = 'terminated';
    case Suspended = 'suspended';
}
