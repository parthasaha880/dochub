<?php

namespace App\Modules\Search\Repositories;

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Search\Models\SavedSearch;
use App\Modules\Search\Repositories\Contracts\SearchRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SearchRepository implements SearchRepositoryInterface
{
    private const FULLTEXT_COLUMNS = [
        'title',
        'reference_no',
        'archive_no',
        'description',
        'keywords',
    ];

    public function searchDocuments(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Document::query()
            ->with(['folder', 'department', 'owner', 'uploader', 'tags', 'checkedOutByUser']);

        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (array_key_exists('folder_id', $filters) && $filters['folder_id'] !== null && $filters['folder_id'] !== '') {
            if ($filters['folder_id'] === 'root') {
                $query->whereNull('folder_id');
            } else {
                $query->where('folder_id', $filters['folder_id']);
            }
        }

        foreach (['department_id', 'category_id', 'status', 'approval_status', 'confidentiality_level', 'document_type', 'extension', 'uploader_id', 'owner_id'] as $key) {
            if (! empty($filters[$key])) {
                $query->where($key, $filters[$key]);
            }
        }

        if (! empty($filters['mime_type'])) {
            $query->where('mime_type', 'like', $filters['mime_type'].'%');
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        if (! empty($filters['tags']) && is_array($filters['tags'])) {
            $tags = array_values(array_filter($filters['tags']));
            if ($tags !== []) {
                $query->whereHas('tags', function (Builder $builder) use ($tags): void {
                    $builder->whereIn('name', $tags)->orWhereIn('slug', $tags);
                });
            }
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $this->applyTextSearch($query, $q);
        } else {
            $query->latest();
        }

        return $query->paginate($perPage);
    }

    public function facets(string $organizationId): array
    {
        $base = Document::query()->where('organization_id', $organizationId);

        return [
            'approval_status' => $this->countBy($base, 'approval_status'),
            'status' => $this->countBy($base, 'status'),
            'confidentiality' => $this->countBy($base, 'confidentiality_level'),
            'extension' => Document::query()
                ->where('organization_id', $organizationId)
                ->selectRaw("COALESCE(NULLIF(extension, ''), 'unknown') as label")
                ->selectRaw('COUNT(*) as value')
                ->groupByRaw("COALESCE(NULLIF(extension, ''), 'unknown')")
                ->orderByDesc('value')
                ->limit(15)
                ->get()
                ->map(fn ($row) => ['label' => (string) $row->label, 'value' => (int) $row->value])
                ->values()
                ->all(),
        ];
    }

    public function listSaved(User $user, ?string $organizationId = null): Collection
    {
        return SavedSearch::query()
            ->with(['organization'])
            ->where(function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)
                    ->orWhere('is_shared', true);
            })
            ->when($organizationId, fn (Builder $q, string $id) => $q->where('organization_id', $id))
            ->orderBy('name')
            ->get();
    }

    public function findSaved(string $id): SavedSearch
    {
        return SavedSearch::query()->findOrFail($id);
    }

    public function createSaved(array $data): SavedSearch
    {
        return SavedSearch::query()->create($data);
    }

    public function updateSaved(SavedSearch $saved, array $data): SavedSearch
    {
        $saved->update($data);

        return $saved->fresh(['organization']);
    }

    public function deleteSaved(SavedSearch $saved): void
    {
        $saved->delete();
    }

    private function applyTextSearch(Builder $query, string $q): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $columns = implode(',', self::FULLTEXT_COLUMNS);
            $boolean = $this->toBooleanModeTerms($q);

            $query->whereRaw(
                "MATCH ({$columns}) AGAINST (? IN BOOLEAN MODE)",
                [$boolean]
            )->select('documents.*')
                ->selectRaw(
                    "MATCH ({$columns}) AGAINST (? IN BOOLEAN MODE) as relevance",
                    [$boolean]
                )
                ->orderByDesc('relevance');

            return;
        }

        // SQLite / other drivers: LIKE fallback (tests)
        $like = '%'.$q.'%';
        $query->where(function (Builder $builder) use ($like): void {
            foreach ([...self::FULLTEXT_COLUMNS, 'original_name'] as $column) {
                $builder->orWhere($column, 'like', $like);
            }
        })->latest();
    }

    private function toBooleanModeTerms(string $q): string
    {
        $terms = preg_split('/\s+/', trim($q)) ?: [];
        $parts = [];

        foreach ($terms as $term) {
            $term = preg_replace('/[^\pL\pN\-_+@.]+/u', '', $term) ?? '';
            if ($term === '' || Str::length($term) < 2) {
                continue;
            }
            // Prefix match helps partial archive/reference lookups
            $parts[] = '+'.$term.'*';
        }

        return $parts !== [] ? implode(' ', $parts) : $q;
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function countBy(Builder $base, string $column): array
    {
        return (clone $base)
            ->select($column.' as label')
            ->selectRaw('COUNT(*) as value')
            ->groupBy($column)
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) ($row->label ?? 'unknown'),
                'value' => (int) $row->value,
            ])
            ->values()
            ->all();
    }
}
