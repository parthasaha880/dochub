<?php

namespace App\Modules\Search\Repositories\Contracts;

use App\Models\User;
use App\Modules\Search\Models\SavedSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SearchRepositoryInterface
{
    public function searchDocuments(array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * @return array{approval_status: list<array{label: string, value: int}>, status: list<array{label: string, value: int}>, confidentiality: list<array{label: string, value: int}>, extension: list<array{label: string, value: int}>}
     */
    public function facets(string $organizationId): array;

    public function listSaved(User $user, ?string $organizationId = null): Collection;

    public function findSaved(string $id): SavedSearch;

    public function createSaved(array $data): SavedSearch;

    public function updateSaved(SavedSearch $saved, array $data): SavedSearch;

    public function deleteSaved(SavedSearch $saved): void;
}
