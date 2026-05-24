<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserActivityLogService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserActivityLogController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private UserActivityLogService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }

    public function search(Request $request): JsonResponse
    {
        try {
            return $this->successResponse($this->service->search($this->orgId($request), $request->all()), 'Logs fetched.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }
}
