<?php

namespace App\Modules\Authentication\Enums;

enum LoginStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Locked = 'locked';
    case Logout = 'logout';
}
