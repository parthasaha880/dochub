<?php

namespace App\Modules\Documents\Enums;

enum ConfidentialityLevel: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Confidential = 'confidential';
    case Restricted = 'restricted';
    case TopSecret = 'top_secret';
}
