<?php

namespace App\Modules\Search\Services;

use App\Models\User;
use App\Modules\Search\Models\SavedSearch;
use App\Modules\Search\Repositories\Contracts\SearchRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SearchService
{
    public function __construct(
        private readonly SearchRepositoryInterface $repository
    ) {}

    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->searchDocuments($filters, $perPage);
    }

    public function facets(string $organizationId): array
    {
        return $this->repository->facets($organizationId);
    }

    public function listSaved(User $user, ?string $organizationId = null): Collection
    {
        return $this->repository->listSaved($user, $organizationId);
    }

    public function showSaved(string $id, User $user): SavedSearch
    {
        $saved = $this->repository->findSaved($id);
        $this->assertCanView($saved, $user);

        return $saved;
    }

    public function createSaved(array $data, User $user): SavedSearch
    {
        return $this->repository->createSaved([
            ...$data,
            'user_id' => $user->id,
            'criteria' => $this->normalizeCriteria($data['criteria'] ?? []),
            'is_shared' => (bool) ($data['is_shared'] ?? false),
            'created_by' => $user->id,
        ]);
    }

    public function updateSaved(string $id, array $data, User $user): SavedSearch
    {
        $saved = $this->repository->findSaved($id);
        $this->assertCanManage($saved, $user);

        if (array_key_exists('criteria', $data)) {
            $data['criteria'] = $this->normalizeCriteria($data['criteria'] ?? []);
        }

        return $this->repository->updateSaved($saved, [
            ...$data,
            'updated_by' => $user->id,
        ]);
    }

    public function deleteSaved(string $id, User $user): void
    {
        $saved = $this->repository->findSaved($id);
        $this->assertCanManage($saved, $user);
        $this->repository->deleteSaved($saved);
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    public function normalizeCriteria(array $criteria): array
    {
        return collect($criteria)
            ->only([
                'q',
                'organization_id',
                'folder_id',
                'department_id',
                'category_id',
                'status',
                'approval_status',
                'confidentiality_level',
                'document_type',
                'extension',
                'mime_type',
                'uploader_id',
                'owner_id',
                'created_from',
                'created_to',
                'tags',
            ])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }

    private function assertCanView(SavedSearch $saved, User $user): void
    {
        if ($saved->user_id === $user->id || $saved->is_shared || $user->hasRole('super_admin')) {
            return;
        }

        throw ValidationException::withMessages([
            'saved_search' => ['You cannot view this saved search.'],
        ]);
    }

    private function assertCanManage(SavedSearch $saved, User $user): void
    {
        if ($saved->user_id === $user->id || $user->hasRole('super_admin') || $user->can('search.saved.manage')) {
            return;
        }

        throw ValidationException::withMessages([
            'saved_search' => ['You cannot modify this saved search.'],
        ]);
    }
}
