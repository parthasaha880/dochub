<?php

namespace App\Modules\Documents\Models;

use App\Core\Traits\Auditable;
use App\Core\Traits\HasUuid;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    use Auditable;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'parent_id',
        'department_id',
        'name',
        'color',
        'icon',
        'is_favorite',
        'is_locked',
        'is_hidden',
        'locked_by',
        'locked_at',
        'sort_order',
        'description',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'is_favorite' => 'boolean',
            'is_locked' => 'boolean',
            'is_hidden' => 'boolean',
            'locked_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
