<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HrDepartmentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HrDepartmentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private HrDepartmentService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): int { return $r->attributes->get('user_id'); }

    // Departments
    public function searchDepartments(Request $request): JsonResponse
    {
        try {
            return $this->successResponse($this->service->searchDepartments($this->orgId($request), $request->all()), 'Departments fetched.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function createDepartment(Request $request): JsonResponse
    {
        try {
            $dept = $this->service->createDepartment($this->orgId($request), $request->all(), $this->userId($request));
            return $this->successResponse($dept, 'Department created.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function updateDepartment(Request $request): JsonResponse
    {
        try {
            $dept = $this->service->updateDepartment($this->orgId($request), $request->input('id'), $request->all(), $this->userId($request));
            return $this->successResponse($dept, 'Department updated.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function deleteDepartment(Request $request): JsonResponse
    {
        try {
            $this->service->deleteDepartment($this->orgId($request), $request->input('id'));
            return $this->successResponse(null, 'Department deleted.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    // Designations
    public function searchDesignations(Request $request): JsonResponse
    {
        try {
            return $this->successResponse($this->service->searchDesignations($this->orgId($request), $request->all()), 'Designations fetched.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function createDesignation(Request $request): JsonResponse
    {
        try {
            $desig = $this->service->createDesignation($this->orgId($request), $request->all(), $this->userId($request));
            return $this->successResponse($desig, 'Designation created.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function updateDesignation(Request $request): JsonResponse
    {
        try {
            $desig = $this->service->updateDesignation($this->orgId($request), $request->input('id'), $request->all(), $this->userId($request));
            return $this->successResponse($desig, 'Designation updated.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function deleteDesignation(Request $request): JsonResponse
    {
        try {
            $this->service->deleteDesignation($this->orgId($request), $request->input('id'));
            return $this->successResponse(null, 'Designation deleted.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }
}
