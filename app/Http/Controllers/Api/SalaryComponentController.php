<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SalaryComponentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryComponentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private SalaryComponentService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): int { return $r->attributes->get('user_id'); }

    public function search(Request $request): JsonResponse
    {
        try {
            return $this->successResponse($this->service->search($this->orgId($request), $request->all()), 'Components fetched.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $comp = $this->service->create($this->orgId($request), $request->all(), $this->userId($request));
            return $this->successResponse($comp, 'Component created.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $comp = $this->service->update($this->orgId($request), $request->input('id'), $request->all());
            return $this->successResponse($comp, 'Component updated.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $this->service->delete($this->orgId($request), $request->input('id'));
            return $this->successResponse(null, 'Component deleted.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function getUserSalary(Request $request, int $userId): JsonResponse
    {
        try {
            $structure = $this->service->getUserSalaryStructure($this->orgId($request), $userId);
            return $this->successResponse($structure, 'Salary structure fetched.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function saveUserSalary(Request $request): JsonResponse
    {
        try {
            $structure = $this->service->saveUserSalaryStructure(
                $this->orgId($request),
                $request->input('user_id'),
                $request->all(),
                $this->userId($request)
            );
            return $this->successResponse($structure, 'Salary structure saved.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }
}
