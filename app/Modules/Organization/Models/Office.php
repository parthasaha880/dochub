<?php

namespace App\Modules\Organization\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends OrganizationEntity
{
    protected $fillable = [
        'organization_id',
        'branch_id',
        'code',
        'name',
        'email',
        'phone',
        'address_line1',
        'city',
        'state',
        'postal_code',
        'country',
        'is_active',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
