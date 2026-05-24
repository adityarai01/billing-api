<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HrShiftService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HrShiftController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private HrShiftService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): int { return $r->attributes->get('user_id'); }

    public function search(Request $request): JsonResponse
    {
        try {
            return $this->successResponse($this->service->searchShifts($this->orgId($request), $request->all()), 'Shifts fetched.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $shift = $this->service->createShift($this->orgId($request), $request->all(), $this->userId($request));
            return $this->successResponse($shift, 'Shift created.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $shift = $this->service->updateShift($this->orgId($request), $request->input('id'), $request->all(), $this->userId($request));
            return $this->successResponse($shift, 'Shift updated.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $this->service->deleteShift($this->orgId($request), $request->input('id'));
            return $this->successResponse(null, 'Shift deleted.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function assignShift(Request $request): JsonResponse
    {
        try {
            $userShift = $this->service->assignShift(
                $this->orgId($request),
                $request->input('user_id'),
                $request->input('shift_id'),
                $request->input('effective_from'),
                $request->input('effective_to'),
                $this->userId($request)
            );
            return $this->successResponse($userShift, 'Shift assigned.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function getUserShifts(Request $request): JsonResponse
    {
        try {
            return $this->successResponse($this->service->getUserShifts($this->orgId($request), $request->all()), 'User shifts fetched.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }
}
