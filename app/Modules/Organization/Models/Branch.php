<?php

namespace App\Modules\Organization\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends OrganizationEntity
{
    protected $fillable = [
        'organization_id',
        'parent_id',
        'code',
        'name',
        'type',
        'email',
        'phone',
        'address_line1',
        'city',
        'state',
        'postal_code',
        'country',
        'is_head_office',
        'is_active',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_head_office' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function offices(): HasMany
    {
        return $this->hasMany(Office::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
