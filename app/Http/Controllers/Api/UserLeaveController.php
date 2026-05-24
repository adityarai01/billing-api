<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserLeaveService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserLeaveController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private UserLeaveService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): int { return $r->attributes->get('user_id'); }

    public function searchLeaveTypes(Request $request): JsonResponse
    {
        try {
            return $this->successResponse($this->service->searchLeaveTypes($this->orgId($request), $request->all()), 'Leave types fetched.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function createLeaveType(Request $request): JsonResponse
    {
        try {
            $lt = $this->service->createLeaveType($this->orgId($request), $request->all(), $this->userId($request));
            return $this->successResponse($lt, 'Leave type created.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function updateLeaveType(Request $request): JsonResponse
    {
        try {
            $lt = $this->service->updateLeaveType($this->orgId($request), $request->input('id'), $request->all());
            return $this->successResponse($lt, 'Leave type updated.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function deleteLeaveType(Request $request): JsonResponse
    {
        try {
            $this->service->deleteLeaveType($this->orgId($request), $request->input('id'));
            return $this->successResponse(null, 'Leave type deleted.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function searchLeaves(Request $request): JsonResponse
    {
        try {
            return $this->successResponse($this->service->searchLeaves($this->orgId($request), $request->all()), 'Leaves fetched.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function applyLeave(Request $request): JsonResponse
    {
        try {
            $leave = $this->service->applyLeave($this->orgId($request), $request->all(), $this->userId($request));
            return $this->successResponse($leave, 'Leave applied.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function updateLeaveStatus(Request $request): JsonResponse
    {
        try {
            $leave = $this->service->updateLeaveStatus(
                $this->orgId($request),
                $request->input('id'),
                $request->input('status'),
                $request->input('rejection_reason'),
                $this->userId($request)
            );
            return $this->successResponse($leave, 'Leave status updated.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function deleteLeave(Request $request): JsonResponse
    {
        try {
            $this->service->deleteLeave($this->orgId($request), $request->input('id'));
            return $this->successResponse(null, 'Leave deleted.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }
}
