<?php

namespace App\Modules\Dashboard\Repositories;

use App\Models\User;
use App\Modules\Dashboard\Repositories\Contracts\DashboardRepositoryInterface;
use App\Modules\Documents\Enums\ApprovalStatus;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\Folder;
use App\Modules\Organization\Models\Employee;
use App\Modules\Workflow\Enums\WorkflowInstanceStatus;
use App\Modules\Workflow\Models\Workflow;
use App\Modules\Workflow\Models\WorkflowAction;
use App\Modules\Workflow\Models\WorkflowInstance;
use App\Modules\Workflow\Repositories\Contracts\WorkflowRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function __construct(
        private readonly WorkflowRepositoryInterface $workflows
    ) {}

    public function summary(string $organizationId, User $user, int $days = 30): array
    {
        $days = max(7, min(90, $days));
        $from = now()->subDays($days - 1)->startOfDay();

        $documents = Document::query()->where('organization_id', $organizationId);
        $folders = Folder::query()->where('organization_id', $organizationId);

        $storageBytes = (clone $documents)->sum('size');
        $trashCount = Document::onlyTrashed()->where('organization_id', $organizationId)->count();

        $uploadsTrend = $this->dailyCounts(
            Document::query()
                ->where('organization_id', $organizationId)
                ->where('created_at', '>=', $from)
                ->selectRaw($this->dateSelect('created_at').' as day')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
                ->all(),
            $from,
            $days
        );

        $approvalsTrend = $this->dailyCounts(
            WorkflowAction::query()
                ->whereHas('instance', fn ($q) => $q->where('organization_id', $organizationId))
                ->where('action', 'approved')
                ->where('acted_at', '>=', $from)
                ->selectRaw($this->dateSelect('acted_at').' as day')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
                ->all(),
            $from,
            $days
        );

        $submissionsTrend = $this->dailyCounts(
            WorkflowInstance::query()
                ->where('organization_id', $organizationId)
                ->where('submitted_at', '>=', $from)
                ->selectRaw($this->dateSelect('submitted_at').' as day')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
                ->all(),
            $from,
            $days
        );

        return [
            'range_days' => $days,
            'generated_at' => now()->toIso8601String(),
            'kpis' => [
                'documents_total' => (clone $documents)->count(),
                'folders_total' => $folders->count(),
                'storage_bytes' => (int) $storageBytes,
                'trash_count' => $trashCount,
                'employees_total' => Employee::query()->where('organization_id', $organizationId)->where('is_active', true)->count(),
                'workflows_active' => Workflow::query()->where('organization_id', $organizationId)->where('is_active', true)->count(),
                'pending_my_approvals' => $this->workflows->paginateInbox($user, ['organization_id' => $organizationId], 1)->total(),
                'instances_in_progress' => WorkflowInstance::query()
                    ->where('organization_id', $organizationId)
                    ->where('status', WorkflowInstanceStatus::InProgress->value)
                    ->count(),
                'documents_draft' => (clone $documents)->where('approval_status', ApprovalStatus::Draft->value)->count(),
                'documents_under_review' => (clone $documents)->where('approval_status', ApprovalStatus::UnderReview->value)->count(),
                'documents_approved' => (clone $documents)->where('approval_status', ApprovalStatus::Approved->value)->count(),
                'documents_rejected' => (clone $documents)->where('approval_status', ApprovalStatus::Rejected->value)->count(),
            ],
            'trends' => [
                'labels' => array_column($uploadsTrend, 'date'),
                'uploads' => array_column($uploadsTrend, 'count'),
                'submissions' => array_column($submissionsTrend, 'count'),
                'approvals' => array_column($approvalsTrend, 'count'),
            ],
            'breakdowns' => [
                'approval_status' => $this->groupedBreakdown($organizationId, 'approval_status'),
                'status' => $this->groupedBreakdown($organizationId, 'status'),
                'confidentiality' => $this->groupedBreakdown($organizationId, 'confidentiality_level'),
                'extension' => $this->topExtensions($organizationId),
                'department' => $this->byDepartment($organizationId),
                'workflow_status' => $this->workflowStatusBreakdown($organizationId),
            ],
            'storage_reports' => $this->storageReports($organizationId, (int) $storageBytes),
            'recent_documents' => Document::query()
                ->where('organization_id', $organizationId)
                ->latest()
                ->limit(8)
                ->get(['id', 'title', 'extension', 'approval_status', 'size', 'created_at'])
                ->map(fn (Document $doc) => [
                    'id' => $doc->id,
                    'title' => $doc->title,
                    'extension' => $doc->extension,
                    'approval_status' => $doc->approval_status?->value ?? $doc->approval_status,
                    'size' => $doc->size,
                    'created_at' => $doc->created_at?->toIso8601String(),
                ])
                ->all(),
            'recent_actions' => $this->workflows->recentActions($organizationId, 10)->map(fn (WorkflowAction $action) => [
                'id' => $action->id,
                'action' => $action->action?->value ?? $action->action,
                'comments' => $action->comments,
                'acted_at' => $action->acted_at?->toIso8601String(),
                'actor' => $action->actor ? [
                    'id' => $action->actor->id,
                    'name' => $action->actor->name,
                ] : null,
                'step' => $action->step ? [
                    'id' => $action->step->id,
                    'name' => $action->step->name,
                ] : null,
                'document' => $action->instance?->document ? [
                    'id' => $action->instance->document->id,
                    'title' => $action->instance->document->title,
                ] : null,
            ])->values()->all(),
        ];
    }

    private function dateSelect(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'sqlite'
            ? "strftime('%Y-%m-%d', {$column})"
            : "DATE({$column})";
    }

    /**
     * @param  array<string, int|string>  $raw
     * @return list<array{date: string, count: int}>
     */
    private function dailyCounts(array $raw, Carbon $from, int $days): array
    {
        $normalized = [];
        foreach ($raw as $day => $total) {
            $normalized[Carbon::parse((string) $day)->toDateString()] = (int) $total;
        }

        $series = [];
        foreach (CarbonPeriod::create($from, now()->endOfDay()) as $date) {
            $key = $date->toDateString();
            $series[] = [
                'date' => $key,
                'count' => $normalized[$key] ?? 0,
            ];
            if (count($series) >= $days) {
                break;
            }
        }

        return $series;
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function groupedBreakdown(string $organizationId, string $column): array
    {
        return Document::query()
            ->where('organization_id', $organizationId)
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

    /**
     * @return list<array{label: string, value: int}>
     */
    private function topExtensions(string $organizationId): array
    {
        return Document::query()
            ->where('organization_id', $organizationId)
            ->selectRaw("COALESCE(NULLIF(extension, ''), 'unknown') as label")
            ->selectRaw('COUNT(*) as value')
            ->groupByRaw("COALESCE(NULLIF(extension, ''), 'unknown')")
            ->orderByDesc('value')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'value' => (int) $row->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function byDepartment(string $organizationId): array
    {
        return Document::query()
            ->leftJoin('departments', 'departments.id', '=', 'documents.department_id')
            ->where('documents.organization_id', $organizationId)
            ->selectRaw("COALESCE(departments.name, 'Unassigned') as label")
            ->selectRaw('COUNT(*) as value')
            ->groupByRaw("COALESCE(departments.name, 'Unassigned')")
            ->orderByDesc('value')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'value' => (int) $row->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, value: int}>
     */
    private function workflowStatusBreakdown(string $organizationId): array
    {
        return WorkflowInstance::query()
            ->where('organization_id', $organizationId)
            ->select('status as label')
            ->selectRaw('COUNT(*) as value')
            ->groupBy('status')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'value' => (int) $row->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Screenshot-style storage analytics: counts + sizes for donut report cards.
     *
     * @return array<string, mixed>
     */
    private function storageReports(string $organizationId, int $usedBytes): array
    {
        $quotaGb = (float) config('edams.storage_quota_gb', 10);
        $quotaBytes = (int) round($quotaGb * 1024 * 1024 * 1024);
        $freeBytes = max(0, $quotaBytes - $usedBytes);
        $overBytes = max(0, $usedBytes - $quotaBytes);

        return [
            'by_document_type' => $this->sizeBreakdown(
                $organizationId,
                "COALESCE(NULLIF(document_type, ''), COALESCE(NULLIF(extension, ''), 'Unknown'))"
            ),
            'by_file_category' => $this->byFileCategory($organizationId),
            'by_department' => $this->sizeBreakdown(
                $organizationId,
                "COALESCE(departments.name, 'Unassigned')",
                true
            ),
            'by_user' => $this->byUploader($organizationId),
            'disk' => [
                'quota_bytes' => $quotaBytes,
                'quota_gb' => $quotaGb,
                'used_bytes' => $usedBytes,
                'free_bytes' => $freeBytes,
                'over_bytes' => $overBytes,
                'rows' => array_values(array_filter([
                    [
                        'label' => 'Free Space',
                        'count' => null,
                        'size_bytes' => $freeBytes,
                        'color' => '#22c55e',
                    ],
                    [
                        'label' => 'Used Space',
                        'count' => null,
                        'size_bytes' => $usedBytes,
                        'color' => '#ef4444',
                    ],
                    $overBytes > 0 ? [
                        'label' => 'Over Usage',
                        'count' => null,
                        'size_bytes' => $overBytes,
                        'color' => '#f9a8d4',
                    ] : null,
                ])),
            ],
        ];
    }

    /**
     * @return list<array{label: string, count: int, size_bytes: int}>
     */
    private function sizeBreakdown(string $organizationId, string $labelExpression, bool $joinDepartments = false): array
    {
        $query = Document::query()->where('documents.organization_id', $organizationId);

        if ($joinDepartments) {
            $query->leftJoin('departments', 'departments.id', '=', 'documents.department_id');
        }

        return $query
            ->selectRaw("{$labelExpression} as label")
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(documents.size), 0) as size_bytes')
            ->groupByRaw($labelExpression)
            ->orderByDesc('size_bytes')
            ->limit(25)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) ($row->label ?: 'Unknown'),
                'count' => (int) $row->count,
                'size_bytes' => (int) $row->size_bytes,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, count: int, size_bytes: int}>
     */
    private function byFileCategory(string $organizationId): array
    {
        $extensionExpr = "LOWER(COALESCE(NULLIF(extension, ''), ''))";
        $mimeExpr = "LOWER(COALESCE(NULLIF(mime_type, ''), ''))";

        $categoryCase = "
            CASE
                WHEN {$extensionExpr} IN ('zip','rar','7z','tar','gz','bz2')
                    OR {$mimeExpr} LIKE 'application/zip%'
                    OR {$mimeExpr} LIKE 'application/x-rar%'
                    OR {$mimeExpr} LIKE 'application/x-7z%'
                    THEN 'Archives'
                WHEN {$extensionExpr} IN ('sql','db','sqlite','mdb')
                    OR {$mimeExpr} LIKE '%sqlite%'
                    THEN 'Database'
                WHEN {$extensionExpr} IN ('bak','backup')
                    OR {$mimeExpr} LIKE '%backup%'
                    THEN 'Backup'
                WHEN {$mimeExpr} LIKE 'audio/%'
                    OR {$mimeExpr} LIKE 'video/%'
                    OR {$extensionExpr} IN ('mp3','mp4','wav','avi','mov','mkv','webm')
                    THEN 'Audio/Video Files'
                WHEN {$mimeExpr} LIKE 'image/%'
                    OR {$mimeExpr} LIKE 'application/pdf%'
                    OR {$mimeExpr} LIKE 'text/%'
                    OR {$mimeExpr} LIKE 'application/msword%'
                    OR {$mimeExpr} LIKE 'application/vnd.%'
                    OR {$extensionExpr} IN ('pdf','doc','docx','xls','xlsx','ppt','pptx','txt','rtf','odt','csv','png','jpg','jpeg','gif','webp')
                    THEN 'Documents'
                ELSE 'Others'
            END
        ";

        return Document::query()
            ->where('organization_id', $organizationId)
            ->selectRaw("{$categoryCase} as label")
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(size), 0) as size_bytes')
            ->groupByRaw($categoryCase)
            ->orderByDesc('size_bytes')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'count' => (int) $row->count,
                'size_bytes' => (int) $row->size_bytes,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, count: int, size_bytes: int}>
     */
    private function byUploader(string $organizationId): array
    {
        return Document::query()
            ->leftJoin('users', 'users.id', '=', 'documents.uploader_id')
            ->where('documents.organization_id', $organizationId)
            ->selectRaw("COALESCE(users.name, 'Unknown user') as label")
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(documents.size), 0) as size_bytes')
            ->groupByRaw("COALESCE(users.name, 'Unknown user')")
            ->orderByDesc('size_bytes')
            ->limit(25)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'count' => (int) $row->count,
                'size_bytes' => (int) $row->size_bytes,
            ])
            ->values()
            ->all();
    }
}
