<?php

namespace App\Modules\Reports\Services;

use App\Modules\Audit\Models\AuditLog;
use App\Modules\Documents\Models\Document;
use App\Modules\Sharing\Models\DocumentShare;
use App\Modules\Workflow\Models\WorkflowInstance;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    public function inventory(string $organizationId): Collection
    {
        return Document::query()
            ->where('organization_id', $organizationId)
            ->with(['folder', 'department'])
            ->latest()
            ->limit(5000)
            ->get()
            ->map(fn (Document $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'reference_no' => $d->reference_no,
                'approval_status' => $d->approval_status?->value,
                'status' => $d->status?->value,
                'confidentiality_level' => $d->confidentiality_level?->value,
                'extension' => $d->extension,
                'size' => $d->size,
                'folder' => $d->folder?->name,
                'department' => $d->department?->name,
                'created_at' => $d->created_at?->toDateTimeString(),
            ]);
    }

    public function workflowSummary(string $organizationId): Collection
    {
        return WorkflowInstance::query()
            ->where('organization_id', $organizationId)
            ->with(['document', 'workflow', 'submitter'])
            ->latest('submitted_at')
            ->limit(5000)
            ->get()
            ->map(fn (WorkflowInstance $i) => [
                'id' => $i->id,
                'document' => $i->document?->title,
                'workflow' => $i->workflow?->name,
                'status' => $i->status?->value,
                'submitter' => $i->submitter?->name,
                'submitted_at' => $i->submitted_at?->toDateTimeString(),
                'completed_at' => $i->completed_at?->toDateTimeString(),
            ]);
    }

    public function auditTrail(string $organizationId, int $days = 30): Collection
    {
        return AuditLog::query()
            ->where('organization_id', $organizationId)
            ->where('created_at', '>=', now()->subDays($days))
            ->with('user')
            ->latest('created_at')
            ->limit(10000)
            ->get()
            ->map(fn (AuditLog $l) => [
                'created_at' => $l->created_at?->toDateTimeString(),
                'module' => $l->module,
                'action' => $l->action,
                'description' => $l->description,
                'user' => $l->user?->name,
                'ip_address' => $l->ip_address,
            ]);
    }

    public function shares(string $organizationId): Collection
    {
        return DocumentShare::query()
            ->where('organization_id', $organizationId)
            ->with(['document', 'creator'])
            ->latest()
            ->limit(5000)
            ->get()
            ->map(fn (DocumentShare $s) => [
                'id' => $s->id,
                'document' => $s->document?->title,
                'share_type' => $s->share_type,
                'is_active' => $s->is_active ? 'yes' : 'no',
                'download_count' => $s->download_count,
                'expires_at' => $s->expires_at?->toDateTimeString(),
                'created_by' => $s->creator?->name,
            ]);
    }

    public function csv(string $type, string $organizationId, int $days = 30): StreamedResponse
    {
        $rows = match ($type) {
            'inventory' => $this->inventory($organizationId),
            'workflow' => $this->workflowSummary($organizationId),
            'audit' => $this->auditTrail($organizationId, $days),
            'shares' => $this->shares($organizationId),
            default => collect(),
        };

        $filename = "edams-{$type}-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($rows->isEmpty()) {
                fputcsv($out, ['message']);
                fputcsv($out, ['No data']);
                fclose($out);

                return;
            }
            fputcsv($out, array_keys($rows->first()));
            foreach ($rows as $row) {
                fputcsv($out, array_values($row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
