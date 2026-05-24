<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpenCashRegisterRequest;
use App\Http\Requests\CloseCashRegisterRequest;
use App\Http\Requests\CashInRequest;
use App\Http\Requests\CashOutRequest;
use App\Services\CashRegisterService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    use ApiResponseTrait;
    public function __construct(private CashRegisterService $service) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): int { return $r->attributes->get('user_id'); }

    public function open(OpenCashRegisterRequest $request): JsonResponse
    {
        try {
            $register = $this->service->openRegister($this->orgId($request), $this->userId($request), $request->validated());
            return $this->successResponse($register, 'Register opened successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function close(CloseCashRegisterRequest $request): JsonResponse
    {
        try {
            $register = $this->service->closeRegister($this->orgId($request), $this->userId($request), $request->validated());
            return $this->successResponse($register, 'Register closed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function current(Request $request): JsonResponse
    {
        $register = $this->service->getOpenRegister($this->orgId($request), $this->userId($request));
        return $this->successResponse($register, $register ? 'Open register found' : 'No open register');
    }

    public function search(Request $request): JsonResponse
    {
        $result = $this->service->searchRegisters($this->orgId($request), $request->all());
        return $this->successResponse($result);
    }

    public function details(Request $request, int $id): JsonResponse
    {
        $register = $this->service->registerDetails($this->orgId($request), $id);
        return $this->successResponse($register);
    }

    public function cashIn(CashInRequest $request): JsonResponse
    {
        try {
            $txn = $this->service->cashIn($this->orgId($request), $this->userId($request), $request->validated());
            return $this->successResponse($txn, 'Cash added successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function cashOut(CashOutRequest $request): JsonResponse
    {
        try {
            $txn = $this->service->cashOut($this->orgId($request), $this->userId($request), $request->validated());
            return $this->successResponse($txn, 'Cash removed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
