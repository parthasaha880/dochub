<?php

namespace App\Modules\Sharing\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Sharing\Services\SharingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShareController extends Controller
{
    public function __construct(private readonly SharingService $service) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('sharing.view'), 403);

        $filters = $request->validate([
            'organization_id' => ['nullable', 'uuid'],
            'document_id' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->service->paginate($filters, (int) ($filters['per_page'] ?? 15));

        return ApiResponse::success([
            'data' => collect($paginator->items())->map(fn ($s) => $this->map($s, $request)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('sharing.manage'), 403);

        $data = $request->validate([
            'document_id' => ['required', 'uuid', 'exists:documents,id'],
            'share_type' => ['nullable', 'in:internal,external'],
            'label' => ['nullable', 'string', 'max:150'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'max_downloads' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'allow_download' => ['sometimes', 'boolean'],
        ]);

        $share = $this->service->create($data, $request->user());

        return ApiResponse::success($this->map($share, $request), 'Share link created', 201);
    }

    public function revoke(Request $request, string $share): JsonResponse
    {
        abort_unless($request->user()?->can('sharing.manage'), 403);

        $model = $this->service->revoke($share, $request->user());

        return ApiResponse::success($this->map($model, $request), 'Share revoked');
    }

    public function publicShow(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'password' => ['nullable', 'string'],
        ]);

        $share = $this->service->resolvePublic($token, $data['password'] ?? null);

        return ApiResponse::success([
            'label' => $share->label,
            'document_title' => $share->document?->title,
            'extension' => $share->document?->extension,
            'allow_download' => $share->allow_download,
            'expires_at' => $share->expires_at?->toIso8601String(),
            'requires_password' => (bool) $share->password_hash,
        ]);
    }

    public function publicDownload(Request $request, string $token): StreamedResponse
    {
        $data = $request->validate([
            'password' => ['nullable', 'string'],
        ]);

        return $this->service->downloadPublic($token, $data['password'] ?? null);
    }

    private function map($share, Request $request): array
    {
        return [
            'id' => $share->id,
            'organization_id' => $share->organization_id,
            'document_id' => $share->document_id,
            'share_type' => $share->share_type,
            'label' => $share->label,
            'token' => $share->token,
            'url' => url('/share/'.$share->token),
            'expires_at' => $share->expires_at?->toIso8601String(),
            'max_downloads' => $share->max_downloads,
            'download_count' => $share->download_count,
            'allow_download' => $share->allow_download,
            'is_active' => $share->is_active,
            'has_password' => (bool) $share->password_hash,
            'document' => $share->document ? [
                'id' => $share->document->id,
                'title' => $share->document->title,
            ] : null,
            'creator' => $share->creator ? [
                'id' => $share->creator->id,
                'name' => $share->creator->name,
            ] : null,
            'created_at' => $share->created_at?->toIso8601String(),
        ];
    }
}
