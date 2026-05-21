<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SalaryPaymentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalaryPaymentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private SalaryPaymentService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): int { return $r->attributes->get('user_id'); }

    public function search(Request $request): JsonResponse
    {
        try {
            return $this->successResponse($this->service->search($this->orgId($request), $request->all()), 'Payments fetched.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function record(Request $request): JsonResponse
    {
        try {
            $payment = $this->service->recordPayment($this->orgId($request), $request->all(), $this->userId($request));
            return $this->successResponse($payment, 'Payment recorded.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $this->service->delete($this->orgId($request), $request->input('id'));
            return $this->successResponse(null, 'Payment deleted.');
        } catch (\Exception $e) { return $this->errorResponse($e->getMessage()); }
    }
}
