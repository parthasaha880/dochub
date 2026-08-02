<?php

namespace App\Modules\Documents\Repositories;

use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\Folder;
use App\Modules\Documents\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DocumentRepository implements DocumentRepositoryInterface
{
    public function paginateDocuments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Document::query()
            ->with(['folder', 'department', 'owner', 'uploader', 'tags', 'checkedOutByUser'])
            ->latest();

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (array_key_exists('folder_id', $filters)) {
            if ($filters['folder_id'] === null || $filters['folder_id'] === 'root') {
                $query->whereNull('folder_id');
                // Root listing: hide cascaded-hidden documents unless explicitly requested.
                if (empty($filters['include_hidden'])) {
                    $query->where('is_hidden', false);
                }
            } elseif ($filters['folder_id'] !== '') {
                $query->where('folder_id', $filters['folder_id']);
            }
        } elseif (empty($filters['include_hidden'])) {
            $query->where('is_hidden', false);
        }

        foreach (['department_id', 'category_id', 'status', 'approval_status', 'document_type', 'extension'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('archive_no', 'like', "%{$search}%")
                    ->orWhere('keywords', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function findDocument(string $id, bool $withTrashed = false): Document
    {
        $query = Document::query()->with(['folder', 'versions', 'tags', 'owner', 'uploader', 'checkedOutByUser']);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->findOrFail($id);
    }

    public function createDocument(array $attributes): Document
    {
        return Document::query()->create($attributes);
    }

    public function updateDocument(Document $document, array $attributes): Document
    {
        $document->update($attributes);

        return $document->refresh();
    }

    public function softDeleteDocument(Document $document): void
    {
        $document->delete();
    }

    public function restoreDocument(Document $document): void
    {
        $document->restore();
    }

    public function forceDeleteDocument(Document $document): void
    {
        $document->forceDelete();
    }

    public function paginateTrashed(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Document::onlyTrashed()->with(['folder', 'owner'])->latest('deleted_at');

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function folderTree(string $organizationId, bool $includeHidden = false): Collection
    {
        $constrain = function ($query) use ($includeHidden): void {
            if (! $includeHidden) {
                $query->where('is_hidden', false);
            }
            $query->orderBy('sort_order')->orderBy('name');
        };

        return Folder::query()
            ->where('organization_id', $organizationId)
            ->whereNull('parent_id')
            ->when(! $includeHidden, fn ($q) => $q->where('is_hidden', false))
            ->with(['children' => function ($q) use ($constrain, $includeHidden): void {
                $constrain($q);
                $q->with(['children' => function ($q2) use ($constrain): void {
                    $constrain($q2);
                }]);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function findFolder(string $id): Folder
    {
        return Folder::query()->findOrFail($id);
    }

    public function createFolder(array $attributes): Folder
    {
        return Folder::query()->create($attributes);
    }

    public function updateFolder(Folder $folder, array $attributes): Folder
    {
        $folder->update($attributes);

        return $folder->refresh();
    }

    public function deleteFolder(Folder $folder): void
    {
        $folder->delete();
    }
}
