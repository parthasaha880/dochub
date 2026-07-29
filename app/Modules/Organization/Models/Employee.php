<?php

namespace App\Modules\Organization\Models;

use App\Models\User;
use App\Modules\Organization\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends OrganizationEntity
{
    protected $fillable = [
        'organization_id',
        'user_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'department_id',
        'section_id',
        'unit_id',
        'branch_id',
        'office_id',
        'designation_id',
        'reporting_to',
        'joining_date',
        'leaving_date',
        'employment_status',
        'is_active',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'leaving_date' => 'date',
            'is_active' => 'boolean',
            'employment_status' => EmploymentStatus::class,
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => trim($this->first_name.' '.($this->last_name ?? ''))
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reporting_to');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'reporting_to');
    }
}
