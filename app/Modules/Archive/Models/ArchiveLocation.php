<?php

namespace App\Modules\Archive\Models;

use App\Core\Traits\Auditable;
use App\Core\Traits\HasUuid;
use App\Modules\Archive\Enums\LocationType;
use App\Modules\Documents\Models\Document;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArchiveLocation extends Model
{
    use Auditable;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'parent_id',
        'type',
        'code',
        'name',
        'barcode',
        'qr_code',
        'capacity',
        'sort_order',
        'description',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => LocationType::class,
            'capacity' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
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
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('code');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'location_id');
    }

    public function pathLabels(): array
    {
        $parts = [];
        $node = $this;
        $guard = 0;

        while ($node && $guard < 10) {
            array_unshift($parts, $node->code.' · '.$node->name);
            $node = $node->relationLoaded('parent') ? $node->parent : $node->parent()->first();
            $guard++;
        }

        return $parts;
    }
}
