<?php

namespace App\Modules\Organization\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends OrganizationEntity
{
    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'grade',
        'level',
        'description',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'level' => 'integer',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
