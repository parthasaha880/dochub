<?php

namespace App\Modules\Documents\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Documents\Http\Requests\BulkUploadDocumentRequest;
use App\Modules\Documents\Http\Requests\StoreDocumentRequest;
use App\Modules\Documents\Http\Requests\UpdateDocumentRequest;
use App\Modules\Documents\Http\Resources\DocumentResource;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Document::class);

        $paginator = $this->service->paginate(
            $request->only([
                'organization_id', 'folder_id', 'department_id', 'category_id',
                'status', 'approval_status', 'document_type', 'extension', 'search',
            ]),
            (int) $request->integer('per_page', 15)
        );

        return ApiResponse::success(
            DocumentResource::collection($paginator)->response()->getData(true)
        );
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $this->authorize('create', Document::class);

        $document = $this->service->upload(
            $request->safe()->except('file'),
            $request->file('file'),
            $request->user()
        );

        return ApiResponse::success(new DocumentResource($document), 'Document uploaded successfully', 201);
    }

    public function bulkUpload(BulkUploadDocumentRequest $request): JsonResponse
    {
        $this->authorize('create', Document::class);

        $documents = $this->service->bulkUpload(
            $request->safe()->except('files'),
            $request->file('files', []),
            $request->user()
        );

        return ApiResponse::success(
            DocumentResource::collection($documents),
            'Documents uploaded successfully',
            201
        );
    }

    public function show(string $document): JsonResponse
    {
        $model = $this->service->show($document);
        $this->authorize('view', $model);

        return ApiResponse::success(new DocumentResource($model));
    }

    public function update(UpdateDocumentRequest $request, string $document): JsonResponse
    {
        $model = $this->service->show($document);
        $this->authorize('update', $model);

        $updated = $this->service->updateMetadata($document, $request->validated(), $request->user());

        return ApiResponse::success(new DocumentResource($updated), 'Document updated successfully');
    }

    public function destroy(Request $request, string $document): JsonResponse
    {
        $model = $this->service->show($document);
        $this->authorize('delete', $model);

        $this->service->softDelete($document, $request->user());

        return ApiResponse::success(null, 'Document moved to recycle bin');
    }

    public function replace(Request $request, string $document): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400'],
            'change_summary' => ['nullable', 'string', 'max:500'],
        ]);

        $model = $this->service->show($document);
        $this->authorize('update', $model);

        $updated = $this->service->replaceFile(
            $document,
            $request->file('file'),
            $request->user(),
            $request->input('change_summary')
        );

        return ApiResponse::success(new DocumentResource($updated), 'File replaced with new version');
    }

    public function rename(Request $request, string $document): JsonResponse
    {
        $request->validate(['title' => ['required', 'string', 'max:255']]);

        $model = $this->service->show($document);
        $this->authorize('update', $model);

        $updated = $this->service->rename($document, $request->input('title'), $request->user());

        return ApiResponse::success(new DocumentResource($updated), 'Document renamed');
    }

    public function move(Request $request, string $document): JsonResponse
    {
        $request->validate(['folder_id' => ['nullable', 'uuid', 'exists:folders,id']]);

        $model = $this->service->show($document);
        $this->authorize('update', $model);

        $updated = $this->service->move($document, $request->input('folder_id'), $request->user());

        return ApiResponse::success(new DocumentResource($updated), 'Document moved');
    }

    public function copy(Request $request, string $document): JsonResponse
    {
        $request->validate(['folder_id' => ['nullable', 'uuid', 'exists:folders,id']]);

        $model = $this->service->show($document);
        $this->authorize('create', Document::class);

        $copy = $this->service->copy($document, $request->input('folder_id'), $request->user());

        return ApiResponse::success(new DocumentResource($copy), 'Document copied', 201);
    }

    public function checkOut(Request $request, string $document): JsonResponse
    {
        $model = $this->service->show($document);
        $this->authorize('checkOut', $model);

        $updated = $this->service->checkOut($document, $request->user());

        return ApiResponse::success(new DocumentResource($updated), 'Document checked out');
    }

    public function checkIn(Request $request, string $document): JsonResponse
    {
        $request->validate([
            'file' => ['nullable', 'file', 'max:102400'],
            'change_summary' => ['nullable', 'string', 'max:500'],
        ]);

        $model = $this->service->show($document);
        $this->authorize('checkIn', $model);

        $updated = $this->service->checkIn(
            $document,
            $request->user(),
            $request->file('file'),
            $request->input('change_summary')
        );

        return ApiResponse::success(new DocumentResource($updated), 'Document checked in');
    }

    public function download(string $document): StreamedResponse
    {
        $model = $this->service->show($document);
        $this->authorize('download', $model);

        return $this->service->download($document);
    }

    public function trash(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Document::class);

        $paginator = $this->service->trash(
            $request->only(['organization_id', 'search']),
            (int) $request->integer('per_page', 15)
        );

        return ApiResponse::success(
            DocumentResource::collection($paginator)->response()->getData(true)
        );
    }

    public function restore(string $document): JsonResponse
    {
        $model = Document::withTrashed()->findOrFail($document);
        $this->authorize('restore', $model);

        $restored = $this->service->restore($document);

        return ApiResponse::success(new DocumentResource($restored), 'Document restored');
    }

    public function forceDestroy(string $document): JsonResponse
    {
        $model = Document::withTrashed()->findOrFail($document);
        $this->authorize('forceDelete', $model);

        $this->service->forceDelete($document);

        return ApiResponse::success(null, 'Document permanently deleted');
    }
}
