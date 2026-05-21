<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StaffUserService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffUserController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private StaffUserService $service) {}

    private function orgId(Request $r): int
    {
        return $r->attributes->get('organization_id');
    }

    private function userId(Request $r): int
    {
        return $r->attributes->get('user_id');
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $result = $this->service->search($this->orgId($request), $request->all());
            return $this->successResponse($result, 'Staff fetched.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function details(Request $request, int $id): JsonResponse
    {
        try {
            $user = $this->service->details($this->orgId($request), $id);
            if (!$user) return $this->notFoundResponse('Staff not found.');
            return $this->successResponse($user, 'Staff details fetched.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $user = $this->service->create($this->orgId($request), $request->all(), $this->userId($request));
            return $this->successResponse($user, 'Staff created successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $user = $this->service->update($this->orgId($request), $request->input('id'), $request->all(), $this->userId($request));
            return $this->successResponse($user, 'Staff updated successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $this->service->delete($this->orgId($request), $request->input('id'));
            return $this->successResponse(null, 'Staff deleted.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function changeStatus(Request $request): JsonResponse
    {
        try {
            $this->service->changeStatus($this->orgId($request), $request->input('id'), $request->input('status'));
            return $this->successResponse(null, 'Status updated.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function dashboardStats(Request $request): JsonResponse
    {
        try {
            $result = $this->service->dashboardStats($this->orgId($request));
            return $this->successResponse($result, 'Dashboard stats fetched.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
