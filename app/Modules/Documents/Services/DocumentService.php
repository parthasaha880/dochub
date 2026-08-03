<?php

namespace App\Modules\Documents\Services;

use App\Modules\Archive\Enums\MediaType;
use App\Modules\Archive\Services\DocumentNumberingService;
use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Documents\Enums\ApprovalStatus;
use App\Modules\Documents\Enums\ConfidentialityLevel;
use App\Modules\Documents\Enums\DocumentStatus;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentTag;
use App\Modules\Documents\Models\DocumentVersion;
use App\Modules\Documents\Models\Folder;
use App\Modules\Documents\Repositories\Contracts\DocumentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentService
{
    public function __construct(
        private readonly DocumentRepositoryInterface $repository,
        private readonly DocumentStorageService $storage,
        private readonly AuditLogger $audit,
        private readonly DocumentNumberingService $numbering,
    ) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateDocuments($filters, $perPage);
    }

    public function show(string $id): Document
    {
        return $this->repository->findDocument($id);
    }

    public function upload(array $data, UploadedFile $file, User $actor): Document
    {
        return DB::transaction(function () use ($data, $file, $actor) {
            $this->assertFolderWritable($data['folder_id'] ?? null);

            $stored = $this->storage->store($file, $data['organization_id']);

            $document = $this->repository->createDocument([
                ...$this->normalizeMetadata($data),
                ...$stored,
                'version' => 1,
                'owner_id' => $data['owner_id'] ?? $actor->id,
                'uploader_id' => $actor->id,
                'approval_status' => $data['approval_status'] ?? ApprovalStatus::Draft->value,
                'status' => $data['status'] ?? DocumentStatus::Active->value,
                'media_type' => $data['media_type'] ?? MediaType::Digital->value,
                'confidentiality_level' => $data['confidentiality_level'] ?? ConfidentialityLevel::Internal->value,
                'archive_no' => $data['archive_no'] ?? $this->numbering->next($data['organization_id'], 'ARC'),
                'reference_no' => $data['reference_no'] ?? $this->numbering->next($data['organization_id'], 'REF'),
                'barcode' => $data['barcode'] ?? $this->generateCode('BC'),
                'qr_code' => $data['qr_code'] ?? $this->generateCode('QR'),
                'location_id' => $data['location_id'] ?? null,
                'physical_reference' => $data['physical_reference'] ?? null,
            ]);

            $this->createVersion($document, $stored, $actor->id, 'Initial upload');
            $this->syncTags($document, $data['tags'] ?? []);

            $document = $document->load(['folder', 'tags', 'versions']);

            $this->audit->log(
                'documents',
                'document.uploaded',
                'Document uploaded: '.$document->title,
                $document,
                null,
                ['title' => $document->title, 'size' => $document->size],
                null,
                $document->organization_id,
                $actor
            );

            return $document;
        });
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, Document>
     */
    public function bulkUpload(array $data, array $files, User $actor): Collection
    {
        return collect($files)->map(function (UploadedFile $file) use ($data, $actor) {
            $payload = [
                ...$data,
                'title' => $data['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            ];

            return $this->upload($payload, $file, $actor);
        });
    }

    public function updateMetadata(string $id, array $data, User $actor): Document
    {
        return DB::transaction(function () use ($id, $data, $actor) {
            $document = $this->repository->findDocument($id);
            $this->assertWritable($document, $actor);

            $tags = $data['tags'] ?? null;
            unset($data['tags']);

            $document = $this->repository->updateDocument($document, $this->normalizeMetadata($data));

            if (is_array($tags)) {
                $this->syncTags($document, $tags);
            }

            return $document->load(['folder', 'tags', 'versions']);
        });
    }

    public function replaceFile(string $id, UploadedFile $file, User $actor, ?string $changeSummary = null): Document
    {
        return DB::transaction(function () use ($id, $file, $actor, $changeSummary) {
            $document = $this->repository->findDocument($id);
            $this->assertWritable($document, $actor);

            $stored = $this->storage->store($file, $document->organization_id);
            $nextVersion = $document->version + 1;
            $document->version = $nextVersion;

            $this->createVersion($document, $stored, $actor->id, $changeSummary ?: "Version {$nextVersion}");

            return $this->repository->updateDocument($document, [
                ...$stored,
                'version' => $nextVersion,
                'uploader_id' => $actor->id,
            ])->load(['versions', 'tags']);
        });
    }

    public function rename(string $id, string $title, User $actor): Document
    {
        $document = $this->repository->findDocument($id);
        $this->assertWritable($document, $actor);

        return $this->repository->updateDocument($document, ['title' => $title]);
    }

    public function move(string $id, ?string $folderId, User $actor): Document
    {
        $document = $this->repository->findDocument($id);
        $this->assertWritable($document, $actor);
        $this->assertFolderWritable($document->folder_id);
        $this->assertFolderWritable($folderId);

        if ($folderId) {
            $folder = $this->repository->findFolder($folderId);
            if ($folder->organization_id !== $document->organization_id) {
                throw ValidationException::withMessages([
                    'folder_id' => ['Folder must belong to the same organization.'],
                ]);
            }
        }

        return $this->repository->updateDocument($document, ['folder_id' => $folderId]);
    }

    public function copy(string $id, ?string $folderId, User $actor): Document
    {
        return DB::transaction(function () use ($id, $folderId, $actor) {
            $source = $this->repository->findDocument($id);
            $this->assertFolderWritable($folderId ?? $source->folder_id);

            $extension = $source->extension ?: 'bin';
            $newPath = 'documents/'.$source->organization_id.'/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;

            if ($source->path && Storage::disk($source->disk)->exists($source->path)) {
                Storage::disk($source->disk)->copy($source->path, $newPath);
            }

            $copy = $source->replicate([
                'barcode',
                'qr_code',
                'checked_out_by',
                'checked_out_at',
                'is_locked',
                'locked_by',
                'locked_at',
                'deleted_at',
                'deleted_by',
            ]);

            $copy->title = $source->title.' (Copy)';
            $copy->folder_id = $folderId ?? $source->folder_id;
            $copy->uploader_id = $actor->id;
            $copy->owner_id = $actor->id;
            $copy->barcode = $this->generateCode('BC');
            $copy->qr_code = $this->generateCode('QR');
            $copy->version = 1;
            $copy->path = $newPath;
            $copy->approval_status = ApprovalStatus::Draft;
            $copy->save();

            $this->createVersion($copy, [
                'disk' => $source->disk,
                'path' => $newPath,
                'original_name' => $source->original_name,
                'mime_type' => $source->mime_type,
                'extension' => $source->extension,
                'size' => $source->size,
                'checksum' => $source->checksum,
            ], $actor->id, 'Copied document');

            $copy->tags()->sync($source->tags()->pluck('document_tags.id'));

            return $copy->load(['folder', 'tags', 'versions']);
        });
    }

    public function checkOut(string $id, User $actor): Document
    {
        $document = $this->repository->findDocument($id);
        $this->assertFolderWritable($document->folder_id);

        if ($document->is_locked && ! $document->isCheckedOut()) {
            throw ValidationException::withMessages([
                'document' => ['Document is locked by its folder. Unlock the folder first.'],
            ]);
        }

        if ($document->isCheckedOut() && ! $document->isCheckedOutBy($actor)) {
            throw ValidationException::withMessages([
                'document' => ['Document is checked out by another user.'],
            ]);
        }

        return $this->repository->updateDocument($document, [
            'is_locked' => true,
            'locked_by' => $actor->id,
            'locked_at' => now(),
            'checked_out_by' => $actor->id,
            'checked_out_at' => now(),
        ]);
    }

    public function checkIn(string $id, User $actor, ?UploadedFile $file = null, ?string $changeSummary = null): Document
    {
        return DB::transaction(function () use ($id, $actor, $file, $changeSummary) {
            $document = $this->repository->findDocument($id);

            if (! $document->isCheckedOutBy($actor) && ! $actor->hasRole('super_admin')) {
                throw ValidationException::withMessages([
                    'document' => ['Only the user who checked out this document can check it in.'],
                ]);
            }

            if ($file) {
                $document = $this->replaceFile($id, $file, $actor, $changeSummary);
            }

            return $this->repository->updateDocument($document, [
                'is_locked' => false,
                'locked_by' => null,
                'locked_at' => null,
                'checked_out_by' => null,
                'checked_out_at' => null,
            ])->load(['versions', 'tags']);
        });
    }

    public function softDelete(string $id, User $actor): void
    {
        $document = $this->repository->findDocument($id);
        $this->assertWritable($document, $actor);
        $this->repository->softDeleteDocument($document);
        $this->audit->log(
            'documents',
            'document.trashed',
            'Document moved to recycle bin: '.$document->title,
            $document,
            null,
            null,
            null,
            $document->organization_id,
            $actor
        );
    }

    public function restore(string $id): Document
    {
        $document = $this->repository->findDocument($id, true);
        $this->repository->restoreDocument($document);

        return $document->fresh(['folder', 'tags']);
    }

    public function forceDelete(string $id): void
    {
        DB::transaction(function () use ($id): void {
            $document = $this->repository->findDocument($id, true);

            foreach ($document->versions as $version) {
                // Keep physical files if shared by copy-with-same-path; only delete when unique path.
                $shared = DocumentVersion::query()
                    ->where('path', $version->path)
                    ->where('id', '!=', $version->id)
                    ->exists();

                if (! $shared) {
                    $this->storage->delete($version->disk, $version->path);
                }
            }

            $this->repository->forceDeleteDocument($document);
        });
    }

    public function trash(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateTrashed($filters, $perPage);
    }

    public function download(string $id): StreamedResponse
    {
        $document = $this->repository->findDocument($id);

        if (! $document->path || ! $this->storage->exists($document->disk, $document->path)) {
            abort(404, 'File not found.');
        }

        return response()->streamDownload(function () use ($document): void {
            $stream = $this->storage->stream($document->disk, $document->path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $document->original_name ?: ($document->title.'.'.$document->extension), [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
        ]);
    }

    /**
     * Stream file for in-app viewing (inline disposition, range-friendly).
     */
    public function preview(string $id): \Symfony\Component\HttpFoundation\Response
    {
        $document = $this->repository->findDocument($id);

        if (! $document->path || ! $this->storage->exists($document->disk, $document->path)) {
            abort(404, 'File not found.');
        }

        $filename = $document->original_name ?: ($document->title.'.'.$document->extension);

        return Storage::disk($document->disk)->response(
            $document->path,
            $filename,
            [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=120',
            ],
            'inline'
        );
    }

    public function folderTree(string $organizationId, bool $includeHidden = false): Collection
    {
        return $this->repository->folderTree($organizationId, $includeHidden);
    }

    public function createFolder(array $data, ?User $actor = null): Folder
    {
        $this->assertFolderWritable($data['parent_id'] ?? null);

        if ($actor) {
            $data['created_by'] = $actor->id;
        }

        return $this->repository->createFolder($data);
    }

    public function updateFolder(string $id, array $data, ?User $actor = null): Folder
    {
        $folder = $this->repository->findFolder($id);

        $unlocking = array_key_exists('is_locked', $data) && ! $data['is_locked'];
        if ($folder->is_locked && ! $unlocking) {
            // Allow hide/unhide and unlock while locked; block rename and other edits.
            $mutatingKeys = collect($data)->except(['is_locked', 'is_hidden', 'locked_by', 'locked_at', 'updated_by'])->keys();
            if ($mutatingKeys->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'folder' => ['Folder is locked. Unlock it before making changes.'],
                ]);
            }
        }

        if (array_key_exists('is_locked', $data)) {
            if ($data['is_locked']) {
                $data['locked_by'] = $actor?->id;
                $data['locked_at'] = now();
            } else {
                $data['locked_by'] = null;
                $data['locked_at'] = null;
            }
        }

        if ($actor) {
            $data['updated_by'] = $actor->id;
        }

        $updated = $this->repository->updateFolder($folder, $data);

        if (array_key_exists('is_locked', $data)) {
            $this->cascadeFolderLock($updated, (bool) $data['is_locked'], $actor);
        }

        if (array_key_exists('is_hidden', $data)) {
            $this->cascadeFolderHide($updated, (bool) $data['is_hidden'], $actor);
        }

        return $updated->fresh();
    }

    public function renameFolder(string $id, string $name, User $actor): Folder
    {
        return $this->updateFolder($id, ['name' => $name], $actor);
    }

    public function lockFolder(string $id, User $actor): Folder
    {
        return $this->updateFolder($id, ['is_locked' => true], $actor);
    }

    public function unlockFolder(string $id, User $actor): Folder
    {
        return $this->updateFolder($id, ['is_locked' => false], $actor);
    }

    public function hideFolder(string $id, User $actor): Folder
    {
        return $this->updateFolder($id, ['is_hidden' => true], $actor);
    }

    public function unhideFolder(string $id, User $actor): Folder
    {
        return $this->updateFolder($id, ['is_hidden' => false], $actor);
    }

    public function deleteFolder(string $id): void
    {
        $folder = $this->repository->findFolder($id);

        if ($folder->is_locked) {
            throw ValidationException::withMessages([
                'folder' => ['Folder is locked. Unlock it before deleting.'],
            ]);
        }

        if ($folder->documents()->exists() || $folder->children()->exists()) {
            throw ValidationException::withMessages([
                'folder' => ['Folder must be empty before deletion.'],
            ]);
        }

        $this->repository->deleteFolder($folder);
    }

    /**
     * @return list<string>
     */
    private function descendantFolderIds(string $rootId): array
    {
        $ids = [$rootId];
        $frontier = [$rootId];

        while ($frontier !== []) {
            $children = Folder::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();
            $frontier = $children;
            $ids = array_merge($ids, $children);
        }

        return array_values(array_unique($ids));
    }

    private function cascadeFolderLock(Folder $folder, bool $locked, ?User $actor): void
    {
        $folderIds = $this->descendantFolderIds($folder->id);

        Folder::query()
            ->whereIn('id', $folderIds)
            ->where('id', '!=', $folder->id)
            ->update([
                'is_locked' => $locked,
                'locked_by' => $locked ? $actor?->id : null,
                'locked_at' => $locked ? now() : null,
                'updated_by' => $actor?->id,
                'updated_at' => now(),
            ]);

        if ($locked) {
            Document::query()
                ->whereIn('folder_id', $folderIds)
                ->update([
                    'is_locked' => true,
                    'locked_by' => $actor?->id,
                    'locked_at' => now(),
                ]);

            return;
        }

        // Preserve personal check-outs; clear folder locks only.
        Document::query()
            ->whereIn('folder_id', $folderIds)
            ->whereNull('checked_out_by')
            ->update([
                'is_locked' => false,
                'locked_by' => null,
                'locked_at' => null,
            ]);
    }

    private function cascadeFolderHide(Folder $folder, bool $hidden, ?User $actor): void
    {
        $folderIds = $this->descendantFolderIds($folder->id);

        Folder::query()
            ->whereIn('id', $folderIds)
            ->where('id', '!=', $folder->id)
            ->update([
                'is_hidden' => $hidden,
                'updated_by' => $actor?->id,
                'updated_at' => now(),
            ]);

        Document::query()
            ->whereIn('folder_id', $folderIds)
            ->update([
                'is_hidden' => $hidden,
            ]);
    }

    private function assertFolderWritable(?string $folderId): void
    {
        if (! $folderId) {
            return;
        }

        $folder = $this->repository->findFolder($folderId);
        if ($folder->is_locked) {
            throw ValidationException::withMessages([
                'folder_id' => ['Folder is locked. Unlock it before adding or moving documents.'],
            ]);
        }
    }

    private function createVersion(Document $document, array $stored, string $uploaderId, string $summary): DocumentVersion
    {
        return $document->versions()->create([
            'version_number' => $document->version,
            'disk' => $stored['disk'],
            'path' => $stored['path'],
            'original_name' => $stored['original_name'],
            'mime_type' => $stored['mime_type'],
            'extension' => $stored['extension'],
            'size' => $stored['size'],
            'checksum' => $stored['checksum'],
            'change_summary' => $summary,
            'uploaded_by' => $uploaderId,
        ]);
    }

    /**
     * @param  array<int, string>  $tags
     */
    private function syncTags(Document $document, array $tags): void
    {
        $ids = collect($tags)
            ->filter()
            ->map(function (string $tag) use ($document) {
                $model = DocumentTag::query()->firstOrCreate(
                    [
                        'organization_id' => $document->organization_id,
                        'slug' => Str::slug($tag),
                    ],
                    ['name' => $tag]
                );

                return $model->id;
            })
            ->all();

        $document->tags()->sync($ids);
    }

    private function normalizeMetadata(array $data): array
    {
        return collect($data)
            ->only([
                'organization_id',
                'folder_id',
                'department_id',
                'category_id',
                'subcategory_id',
                'title',
                'reference_no',
                'archive_no',
                'physical_reference',
                'description',
                'keywords',
                'confidentiality_level',
                'document_type',
                'retention_until',
                'archive_date',
                'expiry_date',
                'status',
                'media_type',
                'location_id',
                'remarks',
                'owner_id',
            ])
            ->all();
    }

    private function assertWritable(Document $document, User $actor): void
    {
        $this->assertFolderWritable($document->folder_id);

        if ($document->is_locked && ! $document->isCheckedOut()) {
            throw ValidationException::withMessages([
                'document' => ['Document is locked by its folder. Unlock the folder to edit.'],
            ]);
        }

        if ($document->isCheckedOut() && ! $document->isCheckedOutBy($actor) && ! $actor->hasRole('super_admin')) {
            throw ValidationException::withMessages([
                'document' => ['Document is checked out and locked for editing.'],
            ]);
        }
    }

    private function generateCode(string $prefix): string
    {
        return $prefix.'-'.strtoupper(Str::random(10));
    }
}
