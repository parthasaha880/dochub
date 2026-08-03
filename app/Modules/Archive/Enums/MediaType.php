<?php

namespace App\Modules\Archive\Enums;

enum MediaType: string
{
    case Digital = 'digital';
    case Physical = 'physical';
    case Hybrid = 'hybrid';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
