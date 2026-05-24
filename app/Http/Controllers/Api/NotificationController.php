<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private NotificationService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): int { return $r->attributes->get('user_id'); }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->service->unreadCount($this->orgId($request), $this->userId($request));
        return $this->successResponse(['count' => $count]);
    }

    public function unread(Request $request): JsonResponse
    {
        $notifications = $this->service->unreadList($this->orgId($request), $this->userId($request));
        return $this->successResponse($notifications);
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->searchNotifications($this->orgId($request), $request->all(), $this->userId($request));
        return $this->successResponse($result);
    }

    public function markRead(Request $request): JsonResponse
    {
        $id = $request->input('id');
        if (!$id) return $this->errorResponse('Notification ID required', 422);
        $this->service->markAsRead($this->orgId($request), $id, $this->userId($request));
        return $this->successResponse(null, 'Marked as read');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->service->markAllAsRead($this->orgId($request), $this->userId($request));
        return $this->successResponse(null, 'All marked as read');
    }

    public function delete(Request $request): JsonResponse
    {
        $id = $request->input('id');
        if (!$id) return $this->errorResponse('Notification ID required', 422);
        $this->service->deleteNotification($this->orgId($request), $id);
        return $this->successResponse(null, 'Notification deleted');
    }
}
