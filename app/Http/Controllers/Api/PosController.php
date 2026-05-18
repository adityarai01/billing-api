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

        $query = ProductVariant::where('organization_id', $orgId)
            ->where('status', 1)
            ->where('deleted', 0)
            ->with(['product.category', 'product.brand', 'unit', 'batches' => fn($q) => $q->where('available_qty', '>', 0)->orderBy('expiry_date')])
            ->when($keyword, function ($q) use ($keyword) {
                $q->where(function ($q2) use ($keyword) {
                    $q2->where('variant_name', 'like', "%{$keyword}%")
                       ->orWhere('sku', 'like', "%{$keyword}%")
                       ->orWhere('barcode', 'like', "%{$keyword}%")
                       ->orWhereHas('product', fn($p) => $p->where('name', 'like', "%{$keyword}%"));
                });
            });

        $variants = $query->limit($limit)->get()->map(function ($v) {
            $p = $v->product;
            return [
                'product_id'         => $p->id,
                'product_variant_id' => $v->id,
                'product_name'       => $p->name,
                'variant_name'       => $v->variant_name,
                'sku'                => $v->sku,
                'barcode'            => $v->barcode,
                'mrp'                => $v->mrp,
                'selling_price'      => $v->selling_price,
                'purchase_price'     => $v->purchase_price ?? 0,
                'available_stock'    => $v->stock_qty,
                'gst_percent'        => $p->gst_percent ?? 0,
                'unit_name'          => $v->unit?->name,
                'image'              => $p->image,
                'category_name'      => $p->category?->name,
                'category_id'        => $p->category_id,
                'is_batch_tracked'   => $v->batches->isNotEmpty(),
                'batches'            => $v->batches->map(fn($b) => [
                    'batch_id'     => $b->id,
                    'batch_no'     => $b->batch_no,
                    'expiry_date'  => $b->expiry_date,
                    'available_qty'=> $b->available_qty,
                    'mrp'          => $b->mrp,
                    'selling_price'=> $b->selling_price ?? $b->mrp,
                    'is_expired'   => $b->expiry_date && $b->expiry_date < now()->toDateString(),
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
