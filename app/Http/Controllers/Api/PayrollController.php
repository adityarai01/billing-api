<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PayrollService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private PayrollService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): int { return $r->attributes->get('user_id'); }

    public function search(Request $request): JsonResponse
    {
        try {
            return $this->successResponse($this->service->search($this->orgId($request), $request->all()), 'Payrolls fetched.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function generate(Request $request): JsonResponse
    {
        try {
            $ids = $this->service->generate(
                $this->orgId($request),
                $request->input('year', date('Y')),
                $request->input('month', date('n')),
                $this->userId($request)
            );
            return $this->successResponse(['generated_ids' => $ids], count($ids) . ' payrolls generated.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function details(Request $request, int $id): JsonResponse
    {
        try {
            $payroll = $this->service->details($this->orgId($request), $id);
            if (!$payroll) return $this->notFoundResponse('Payroll not found.');
            return $this->successResponse($payroll, 'Payroll details fetched.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function changeStatus(Request $request): JsonResponse
    {
        try {
            $payroll = $this->service->changeStatus(
                $this->orgId($request),
                $request->input('id'),
                $request->input('status'),
                $this->userId($request)
            );
            return $this->successResponse($payroll, 'Status updated.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }
}
