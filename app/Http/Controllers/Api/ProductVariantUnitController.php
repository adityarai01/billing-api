<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductVariantUnitRequest;
use App\Services\ProductVariantUnitService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariantUnitController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private ProductVariantUnitService $service,
    ) {}

    private function orgId(Request $r): int
    {
        return (int) $r->attributes->get('organization_id');
    }

    private function userId(Request $r): ?int
    {
        return $r->attributes->get('user_id');
    }

    public function create(ProductVariantUnitRequest $request): JsonResponse
    {
        $data = array_merge($request->validated(), [
            'organization_id' => $this->orgId($request),
            'created_by'      => $this->userId($request),
        ]);

        $variantUnit = $this->service->createUnit($data);
        return $this->successResponse($variantUnit, 'Unit added successfully', 201);
    }

    public function update(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $data = array_merge($request->all(), [
            'organization_id' => $this->orgId($request),
            'updated_by'      => $this->userId($request),
        ]);

        $variantUnit = $this->service->updateUnit($id, $data);
        return $this->successResponse($variantUnit, 'Unit updated successfully');
    }

    public function delete(Request $request): JsonResponse
    {
        $id = (int) $request->input('id');
        $this->service->deleteUnit($id, $this->orgId($request));
        return $this->successResponse(null, 'Unit deleted successfully');
    }

    public function listByVariant(Request $request, int $productVariantId): JsonResponse
    {
        $units = $this->service->listUnitsByVariant($productVariantId, $this->orgId($request));
        return $this->successResponse($units, 'Units fetched successfully');
    }

    public function setBaseUnit(Request $request): JsonResponse
    {
        $this->service->setBaseUnit(
            (int) $request->input('product_variant_id'),
            (int) $request->input('unit_id'),
            $this->orgId($request)
        );
        return $this->successResponse(null, 'Base unit set successfully');
    }

    public function setDefaultSaleUnit(Request $request): JsonResponse
    {
        $this->service->setDefaultSaleUnit(
            (int) $request->input('product_variant_id'),
            (int) $request->input('unit_id'),
            $this->orgId($request)
        );
        return $this->successResponse(null, 'Default sale unit set successfully');
    }

    public function setDefaultPurchaseUnit(Request $request): JsonResponse
    {
        $this->service->setDefaultPurchaseUnit(
            (int) $request->input('product_variant_id'),
            (int) $request->input('unit_id'),
            $this->orgId($request)
        );
        return $this->successResponse(null, 'Default purchase unit set successfully');
    }

    // ─── POS helpers ─────────────────────────────────────────────────────────

    public function posVariantUnits(Request $request, int $productVariantId): JsonResponse
    {
        $units = $this->service->getVariantUnitsForPos($productVariantId, $this->orgId($request));
        return $this->successResponse($units, 'Variant units for POS fetched');
    }

    public function posUnitPrice(Request $request, int $productVariantId, int $unitId): JsonResponse
    {
        $price = $this->service->getSalePriceByUnit($productVariantId, $unitId);
        return $this->successResponse(['selling_price' => $price], 'Price fetched');
    }

    public function posUnitStock(Request $request, int $productVariantId, int $unitId): JsonResponse
    {
        $stock = $this->service->getAvailableStockInUnit($productVariantId, $unitId);
        return $this->successResponse(['available_stock' => $stock], 'Stock fetched');
    }
}
