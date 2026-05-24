<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserAttendanceService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAttendanceController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private UserAttendanceService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): int { return $r->attributes->get('user_id'); }

    public function search(Request $request): JsonResponse
    {
        try {
            return $this->successResponse($this->service->search($this->orgId($request), $request->all()), 'Attendance fetched.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function mark(Request $request): JsonResponse
    {
        try {
            $record = $this->service->markAttendance($this->orgId($request), $request->all(), $this->userId($request));
            return $this->successResponse($record, 'Attendance marked.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function bulkMark(Request $request): JsonResponse
    {
        try {
            $count = $this->service->bulkMark($this->orgId($request), $request->input('records', []), $this->userId($request));
            return $this->successResponse(['count' => $count], "{$count} attendance records saved.");
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function monthlySummary(Request $request): JsonResponse
    {
        try {
            $userId = $request->input('user_id');
            $summary = $this->service->monthlySummary(
                $this->orgId($request),
                $userId !== null && $userId !== '' ? (int) $userId : null,
                (int) $request->input('year', date('Y')),
                (int) $request->input('month', date('n'))
            );
            return $this->successResponse($summary, 'Monthly summary fetched.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $this->service->delete($this->orgId($request), $request->input('id'));
            return $this->successResponse(null, 'Attendance deleted.');
        } catch (\Throwable $e) { return $this->errorResponse($e->getMessage()); }
    }
}
