<?php

namespace App\Modules\Retention\Models;

use App\Core\Traits\Auditable;
use App\Core\Traits\HasUuid;
use App\Modules\Documents\Models\DocumentCategory;
use App\Modules\Organization\Models\Organization;
use App\Modules\Retention\Enums\RetentionAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RetentionPolicy extends Model
{
    use Auditable;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'description',
        'retention_days',
        'action_on_expiry',
        'category_id',
        'is_active',
        'is_default',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'retention_days' => 'integer',
            'action_on_expiry' => RetentionAction::class,
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }
}
