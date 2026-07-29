<?php

namespace App\Modules\Notifications\Http\Controllers\Api\V1;

use App\Core\Support\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('notifications.view'), 403);

        $paginator = $request->user()
            ->notifications()
            ->latest()
            ->paginate((int) $request->integer('per_page', 20));

        return ApiResponse::success([
            'data' => collect($paginator->items())->map(fn (DatabaseNotification $n) => $this->map($n)),
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('notifications.view'), 403);

        return ApiResponse::success([
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        abort_unless($request->user()?->can('notifications.view'), 403);

        $model = $request->user()->notifications()->where('id', $notification)->firstOrFail();
        $model->markAsRead();

        return ApiResponse::success($this->map($model), 'Marked as read');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('notifications.view'), 403);

        $request->user()->unreadNotifications->markAsRead();

        return ApiResponse::success(['unread_count' => 0], 'All notifications marked as read');
    }

    private function map(DatabaseNotification $n): array
    {
        return [
            'id' => $n->id,
            'type' => $n->type,
            'data' => $n->data,
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at?->toIso8601String(),
        ];
    }
}
