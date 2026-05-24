<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleCalculateRequest;
use App\Http\Requests\SaleRequest;
use App\Http\Requests\HeldBillRequest;
use App\Services\PosCalculationService;
use App\Services\SaleService;
use App\Services\HeldBillService;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private PosCalculationService $calcService,
        private SaleService           $saleService,
        private HeldBillService       $heldBillService,
    ) {}

    private function orgId(Request $r): int { return $r->attributes->get('organization_id'); }
    private function userId(Request $r): ?int { return $r->attributes->get('user_id'); }

    public function productSearch(Request $request): JsonResponse
    {
        $orgId   = $this->orgId($request);
        $keyword = $request->input('keyword', '');
        $limit   = (int) $request->input('limit', 20);

        // Check if keyword matches a ProductVariantUnit barcode (direct barcode scan)
        $unitBarcodeVariantId = null;
        $unitBarcodeUnitId    = null;
        if ($keyword) {
            $unitByBarcode = ProductVariantUnit::where('organization_id', $orgId)
                ->where('barcode', $keyword)
                ->where('status', 1)
                ->where('deleted', 0)
                ->first();
            if ($unitByBarcode) {
                $unitBarcodeVariantId = $unitByBarcode->product_variant_id;
                $unitBarcodeUnitId    = $unitByBarcode->unit_id;
            }
        }

        $query = ProductVariant::where('organization_id', $orgId)
            ->where('status', 1)
            ->where('deleted', 0)
            ->with([
                'product.category',
                'product.brand',
                'unit',
                'activeVariantUnits.unit',
                'batches' => fn($q) => $q->where('available_qty', '>', 0)->orderBy('expiry_date'),
            ])
            ->when($keyword, function ($q) use ($keyword, $unitBarcodeVariantId) {
                $q->where(function ($q2) use ($keyword, $unitBarcodeVariantId) {
                    $q2->where('variant_name', 'like', "%{$keyword}%")
                       ->orWhere('sku', 'like', "%{$keyword}%")
                       ->orWhere('barcode', 'like', "%{$keyword}%")
                       ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$keyword}%"))
                       ->orWhereHas('variantUnits', fn($vu) => $vu->where('barcode', $keyword)->where('status', 1)->where('deleted', 0));
                    if ($unitBarcodeVariantId) {
                        $q2->orWhere('id', $unitBarcodeVariantId);
                    }
                });
            });

        $variants = $query->limit($limit)->get()->map(function ($v) use ($unitBarcodeVariantId, $unitBarcodeUnitId) {
            $p = $v->product;
            $baseQty = max((float) $v->available_stock_base_qty, (float) $v->stock_qty);

            // Build variant units from the already-loaded activeVariantUnits relationship (no extra queries)
            $baseVU       = $v->activeVariantUnits->firstWhere('is_base_unit', 1);
            $baseUnitName = $baseVU
                ? ($baseVU->unit_name_snapshot ?? $baseVU->unit?->name ?? 'Base Unit')
                : ($v->base_unit_name ?? 'Base Unit');

            $variantUnits = $v->activeVariantUnits->map(function ($vu) use ($baseQty, $baseUnitName) {
                $conversion  = (float) $vu->conversion_qty;
                $stockInUnit = $conversion > 0 ? floor(($baseQty / $conversion) * 1000) / 1000 : 0;
                $unitName    = $vu->unit_name_snapshot ?? $vu->unit?->name ?? 'Unit';
                $display     = $conversion == floor($conversion) ? (int) $conversion : $conversion;
                $meaning     = $conversion == 1
                    ? "1 {$unitName} = 1 {$baseUnitName}"
                    : "1 {$unitName} = {$display} {$baseUnitName}";
                return [
                    'id'                       => $vu->id,
                    'unit_id'                  => $vu->unit_id,
                    'unit_name'                => $unitName,
                    'unit_short_name'          => $vu->unit?->short_name,
                    'conversion_qty'           => $conversion,
                    'selling_price'            => (float) $vu->selling_price,
                    'purchase_price'           => (float) $vu->purchase_price,
                    'mrp'                      => (float) $vu->mrp,
                    'wholesale_price'          => (float) $vu->wholesale_price,
                    'barcode'                  => $vu->barcode,
                    'is_base_unit'             => (bool) $vu->is_base_unit,
                    'is_default_sale_unit'     => (bool) $vu->is_default_sale_unit,
                    'is_default_purchase_unit' => (bool) $vu->is_default_purchase_unit,
                    'available_stock'          => $stockInUnit,
                    'meaning'                  => $meaning,
                ];
            })->values()->toArray();

            // Determine default sale unit
            $defaultSaleUnit = collect($variantUnits)->firstWhere('is_default_sale_unit', true)
                ?? collect($variantUnits)->firstWhere('is_base_unit', true)
                ?? ($variantUnits[0] ?? null);

            // When scanned by unit barcode, pre-select that unit
            if ($unitBarcodeVariantId === $v->id && $unitBarcodeUnitId) {
                $defaultSaleUnit = collect($variantUnits)->firstWhere('unit_id', $unitBarcodeUnitId) ?? $defaultSaleUnit;
            }

            $sellingPrice  = $defaultSaleUnit ? $defaultSaleUnit['selling_price'] : (float) $v->selling_price;
            $availableStock = $defaultSaleUnit ? $defaultSaleUnit['available_stock'] : $baseQty;

            return [
                'product_id'              => $p->id,
                'product_variant_id'      => $v->id,
                'product_name'            => $p->name,
                'variant_name'            => $v->variant_name,
                'sku'                     => $v->sku,
                'barcode'                 => $v->barcode,
                'mrp'                     => $defaultSaleUnit ? $defaultSaleUnit['mrp'] : (float) $v->mrp,
                'selling_price'           => $sellingPrice,
                'purchase_price'          => (float) ($v->purchase_price ?? 0),
                'available_stock'         => $availableStock,
                'available_stock_base_qty'=> $baseQty,
                'gst_percent'             => $p->gst_percent ?? 0,
                'unit_name'               => $defaultSaleUnit ? $defaultSaleUnit['unit_name'] : $v->unit?->name,
                'base_unit_name'          => $v->base_unit_name ?? $v->unit?->name,
                'image'                   => $p->image,
                'category_name'           => $p->category?->name,
                'category_id'             => $p->category_id,
                'is_batch_tracked'        => $v->batches->isNotEmpty(),
                'has_multiple_units'      => \count($variantUnits) > 1,
                'variant_units'           => $variantUnits,
                'selected_unit_id'        => $defaultSaleUnit['unit_id'] ?? null,
                'selected_unit_name'      => $defaultSaleUnit['unit_name'] ?? null,
                'selected_conversion_qty' => $defaultSaleUnit['conversion_qty'] ?? 1,
                'batches'                 => $v->batches->map(fn($b) => [
                    'batch_id'      => $b->id,
                    'batch_no'      => $b->batch_no,
                    'expiry_date'   => $b->expiry_date,
                    'available_qty' => $b->available_qty,
                    'mrp'           => $b->mrp,
                    'selling_price' => $b->selling_price ?? $b->mrp,
                    'is_expired'    => $b->expiry_date && $b->expiry_date < now()->toDateString(),
                ]),
            ];
        });

        return $this->successResponse([
            'page'       => 1,
            'total_page' => 1,
            'total_data' => $variants->count(),
            'record'     => $variants,
        ], 'Products fetched');
    }

    public function calculate(SaleCalculateRequest $request): JsonResponse
    {
        $cart = $this->calcService->calculateCart($request->validated());
        return $this->successResponse($cart, 'Cart calculated successfully');
    }

    public function validateStock(Request $request): JsonResponse
    {
        $errors = $this->calcService->validateStock($request->input('items', []));
        if (!empty($errors)) {
            return $this->errorResponse('Stock validation failed', $errors);
        }
        return $this->successResponse(null, 'Stock is available');
    }

    public function saveSale(SaleRequest $request): JsonResponse
    {
        $sale = $this->saleService->createSale($this->orgId($request), $request->validated(), $this->userId($request));
        return $this->successResponse($sale, 'Sale created successfully', 201);
    }

    public function holdBill(HeldBillRequest $request): JsonResponse
    {
        $bill = $this->heldBillService->holdBill($this->orgId($request), $request->validated(), $this->userId($request));
        return $this->successResponse($bill, 'Bill held successfully', 201);
    }

    public function recallBill(Request $request, int $id): JsonResponse
    {
        $bill = $this->heldBillService->recallBill($this->orgId($request), $id);
        return $this->successResponse($bill, 'Bill recalled successfully');
    }

    public function convertHeldBill(Request $request, int $id): JsonResponse
    {
        $sale = $this->heldBillService->convertToSale($this->orgId($request), $id, $request->all(), $this->userId($request));
        return $this->successResponse($sale, 'Bill converted to sale successfully');
    }

    private function errorResponse(string $message, array $errors = []): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message, 'errors' => $errors], 422);
    }
}
