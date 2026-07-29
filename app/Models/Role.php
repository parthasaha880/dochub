<?php

namespace App\Models;

use App\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUuid;

    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'hierarchy_level',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'hierarchy_level' => 'integer',
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return parent::permissions();
    }
}
