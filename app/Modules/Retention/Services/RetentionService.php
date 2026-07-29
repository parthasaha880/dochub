<?php

namespace App\Modules\Retention\Services;

use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\Documents\Enums\DocumentStatus;
use App\Modules\Documents\Models\Document;
use App\Modules\Retention\Enums\RetentionAction;
use App\Modules\Retention\Models\RetentionPolicy;
use App\Modules\Retention\Models\RetentionRun;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RetentionService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return RetentionPolicy::query()
            ->with('category')
            ->when($filters['organization_id'] ?? null, fn ($q, $id) => $q->where('organization_id', $id))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data, User $actor): RetentionPolicy
    {
        if (! empty($data['is_default'])) {
            RetentionPolicy::query()
                ->where('organization_id', $data['organization_id'])
                ->update(['is_default' => false]);
        }

        $policy = RetentionPolicy::query()->create([
            ...$data,
            'created_by' => $actor->id,
        ]);

        $this->audit->log('retention', 'policy.created', 'Retention policy created', $policy, null, $data, null, $policy->organization_id, $actor);

        return $policy->load('category');
    }

    public function update(string $id, array $data, User $actor): RetentionPolicy
    {
        $policy = RetentionPolicy::query()->findOrFail($id);

        if (! empty($data['is_default'])) {
            RetentionPolicy::query()
                ->where('organization_id', $policy->organization_id)
                ->where('id', '!=', $policy->id)
                ->update(['is_default' => false]);
        }

        $policy->update([...$data, 'updated_by' => $actor->id]);

        $this->audit->log('retention', 'policy.updated', 'Retention policy updated', $policy, null, $data, null, $policy->organization_id, $actor);

        return $policy->fresh('category');
    }

    public function delete(string $id, User $actor): void
    {
        $policy = RetentionPolicy::query()->findOrFail($id);
        $policy->delete();
        $this->audit->log('retention', 'policy.deleted', 'Retention policy deleted', $policy, null, null, null, $policy->organization_id, $actor);
    }

    public function process(?string $organizationId = null): RetentionRun
    {
        $run = RetentionRun::query()->create([
            'organization_id' => $organizationId,
            'started_at' => now(),
            'status' => 'running',
        ]);

        $policies = RetentionPolicy::query()
            ->where('is_active', true)
            ->when($organizationId, fn ($q, $id) => $q->where('organization_id', $id))
            ->get();

        $stats = ['processed' => 0, 'archived' => 0, 'soft_deleted' => 0, 'flagged' => 0];

        foreach ($policies as $policy) {
            $cutoff = now()->subDays($policy->retention_days);

            $documents = Document::query()
                ->where('organization_id', $policy->organization_id)
                ->where('created_at', '<=', $cutoff)
                ->where('status', '!=', DocumentStatus::Archived->value)
                ->when($policy->category_id, fn ($q, $id) => $q->where('category_id', $id))
                ->limit(500)
                ->get();

            foreach ($documents as $document) {
                $stats['processed']++;

                match ($policy->action_on_expiry) {
                    RetentionAction::Archive => tap($document, function (Document $doc) use (&$stats, $policy) {
                        $doc->update([
                            'status' => DocumentStatus::Archived->value,
                            'archive_date' => now()->toDateString(),
                        ]);
                        $stats['archived']++;
                        $this->audit->log('retention', 'document.archived', 'Archived by retention policy', $doc, null, [
                            'policy_id' => $policy->id,
                        ], null, $doc->organization_id);
                    }),
                    RetentionAction::SoftDelete => tap($document, function (Document $doc) use (&$stats, $policy) {
                        $doc->delete();
                        $stats['soft_deleted']++;
                        $this->audit->log('retention', 'document.soft_deleted', 'Soft-deleted by retention policy', $doc, null, [
                            'policy_id' => $policy->id,
                        ], null, $doc->organization_id);
                    }),
                    RetentionAction::Flag => tap($document, function (Document $doc) use (&$stats, $policy) {
                        $doc->update([
                            'remarks' => trim(($doc->remarks ? $doc->remarks."\n" : '').'[RETENTION] Due for disposition (policy '.$policy->code.')'),
                        ]);
                        $stats['flagged']++;
                        $this->audit->log('retention', 'document.flagged', 'Flagged by retention policy', $doc, null, [
                            'policy_id' => $policy->id,
                        ], null, $doc->organization_id);
                    }),
                    default => null,
                };
            }
        }

        $run->update([
            ...$stats,
            'status' => 'completed',
            'finished_at' => now(),
            'notes' => 'Processed '.$policies->count().' policies',
        ]);

        return $run->fresh();
    }

    public function recentRuns(?string $organizationId = null, int $limit = 10)
    {
        return RetentionRun::query()
            ->when($organizationId, fn ($q, $id) => $q->where('organization_id', $id))
            ->latest('started_at')
            ->limit($limit)
            ->get();
    }
}
