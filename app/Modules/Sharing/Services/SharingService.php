<?php

namespace App\Modules\Sharing\Services;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentService;
use App\Modules\Sharing\Models\DocumentShare;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SharingService
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly AuditLogger $audit,
    ) {}

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return DocumentShare::query()
            ->with(['document', 'creator'])
            ->when($filters['organization_id'] ?? null, fn ($q, $id) => $q->where('organization_id', $id))
            ->when($filters['document_id'] ?? null, fn ($q, $id) => $q->where('document_id', $id))
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data, User $actor): DocumentShare
    {
        $document = Document::query()->findOrFail($data['document_id']);

        $share = DocumentShare::query()->create([
            'organization_id' => $document->organization_id,
            'document_id' => $document->id,
            'created_by' => $actor->id,
            'share_type' => $data['share_type'] ?? 'external',
            'label' => $data['label'] ?? null,
            'password_hash' => ! empty($data['password']) ? Hash::make($data['password']) : null,
            'expires_at' => $data['expires_at'] ?? null,
            'max_downloads' => $data['max_downloads'] ?? null,
            'allow_download' => $data['allow_download'] ?? true,
            'is_active' => true,
        ]);

        $this->audit->log('sharing', 'share.created', 'Share link created', $share, null, [
            'document_id' => $document->id,
            'expires_at' => $share->expires_at?->toIso8601String(),
        ], null, $document->organization_id, $actor);

        return $share->load(['document', 'creator']);
    }

    public function revoke(string $id, User $actor): DocumentShare
    {
        $share = DocumentShare::query()->findOrFail($id);
        $share->update(['is_active' => false, 'updated_by' => $actor->id]);

        $this->audit->log('sharing', 'share.revoked', 'Share link revoked', $share, null, null, null, $share->organization_id, $actor);

        return $share->fresh(['document', 'creator']);
    }

    public function resolvePublic(string $token, ?string $password = null): DocumentShare
    {
        $share = DocumentShare::query()->with('document')->where('token', $token)->firstOrFail();

        if (! $share->isAccessible()) {
            throw ValidationException::withMessages([
                'token' => ['This share link is inactive, expired, or exhausted.'],
            ]);
        }

        if ($share->password_hash && (! $password || ! Hash::check($password, $share->password_hash))) {
            throw ValidationException::withMessages([
                'password' => ['Password required or invalid.'],
            ]);
        }

        return $share;
    }

    public function downloadPublic(string $token, ?string $password = null): StreamedResponse
    {
        $share = $this->resolvePublic($token, $password);

        if (! $share->allow_download) {
            throw ValidationException::withMessages([
                'token' => ['Downloads are disabled for this share.'],
            ]);
        }

        $share->increment('download_count');
        $share->update(['last_accessed_at' => now()]);

        $this->audit->log('sharing', 'share.downloaded', 'Shared document downloaded', $share, null, null, [
            'download_count' => $share->download_count,
        ], $share->organization_id);

        $document = $share->document;

        return $this->documents->download($document->id);
    }

    public function previewPublic(string $token, ?string $password = null): \Symfony\Component\HttpFoundation\Response
    {
        $share = $this->resolvePublic($token, $password);
        $share->update(['last_accessed_at' => now()]);

        $this->audit->log('sharing', 'share.previewed', 'Shared document previewed', $share, null, null, null, $share->organization_id);

        return $this->documents->preview($share->document->id);
    }
}
