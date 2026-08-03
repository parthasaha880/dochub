<?php

namespace App\Modules\Documents\Models;

use App\Core\Traits\Auditable;
use App\Core\Traits\HasUuid;
use App\Models\User;
use App\Modules\Documents\Enums\ApprovalStatus;
use App\Modules\Documents\Enums\ConfidentialityLevel;
use App\Modules\Documents\Enums\DocumentStatus;
use App\Modules\Organization\Models\Department;
use App\Modules\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use Auditable;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'folder_id',
        'location_id',
        'department_id',
        'category_id',
        'subcategory_id',
        'owner_id',
        'uploader_id',
        'title',
        'reference_no',
        'archive_no',
        'physical_reference',
        'barcode',
        'qr_code',
        'description',
        'keywords',
        'confidentiality_level',
        'document_type',
        'version',
        'retention_until',
        'archive_date',
        'expiry_date',
        'approval_status',
        'status',
        'media_type',
        'remarks',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'extension',
        'size',
        'checksum',
        'is_locked',
        'is_hidden',
        'locked_by',
        'locked_at',
        'checked_out_by',
        'checked_out_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'confidentiality_level' => ConfidentialityLevel::class,
            'approval_status' => ApprovalStatus::class,
            'status' => DocumentStatus::class,
            'version' => 'integer',
            'size' => 'integer',
            'is_locked' => 'boolean',
            'is_hidden' => 'boolean',
            'retention_until' => 'date',
            'archive_date' => 'date',
            'expiry_date' => 'date',
            'locked_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Archive\Models\ArchiveLocation::class, 'location_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'subcategory_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function checkedOutByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_number');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(DocumentTag::class, 'document_tag');
    }

    public function workflowInstances(): HasMany
    {
        return $this->hasMany(\App\Modules\Workflow\Models\WorkflowInstance::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', DocumentStatus::Active);
    }

    public function isCheckedOut(): bool
    {
        return $this->checked_out_by !== null;
    }

    public function isCheckedOutBy(User $user): bool
    {
        return $this->checked_out_by === $user->id;
    }
}
