<?php

namespace App\Modules\Documents\Repositories\Contracts;

use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\Folder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DocumentRepositoryInterface
{
    public function paginateDocuments(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findDocument(string $id, bool $withTrashed = false): Document;

    public function createDocument(array $attributes): Document;

    public function updateDocument(Document $document, array $attributes): Document;

    public function softDeleteDocument(Document $document): void;

    public function restoreDocument(Document $document): void;

    public function forceDeleteDocument(Document $document): void;

    public function paginateTrashed(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function folderTree(string $organizationId): Collection;

    public function findFolder(string $id): Folder;

    public function createFolder(array $attributes): Folder;

    public function updateFolder(Folder $folder, array $attributes): Folder;

    public function deleteFolder(Folder $folder): void;
}
