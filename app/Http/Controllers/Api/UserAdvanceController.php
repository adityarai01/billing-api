<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserAdvanceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAdvanceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private UserAdvanceService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): int { return $r->attributes->get('user_id'); }

    public function search(Request $request): JsonResponse
    {
        try {
            return $this->successResponse($this->service->search($this->orgId($request), $request->all()), 'Advances fetched.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $advance = $this->service->create($this->orgId($request), $request->all(), $this->userId($request));
            return $this->successResponse($advance, 'Advance request created.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function updateStatus(Request $request): JsonResponse
    {
        try {
            $advance = $this->service->updateStatus(
                $this->orgId($request),
                $request->input('id'),
                $request->input('status'),
                $request->input('rejection_reason'),
                $this->userId($request)
            );
            return $this->successResponse($advance, 'Status updated.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $this->service->delete($this->orgId($request), $request->input('id'));
            return $this->successResponse(null, 'Advance deleted.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }
}
