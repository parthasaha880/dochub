<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUuid;

    protected $fillable = [
        'name',
        'guard_name',
        'group',
        'description',
    ];
}
