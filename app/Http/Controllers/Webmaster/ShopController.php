<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Controller;
use App\Http\Request\Webmaster\Shop\StoreShopRequest;
use App\Http\Request\Webmaster\Shop\UpdateShopRequest;
use App\Service\Webmaster\ShopService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly ShopService $shopService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $page    = (int) $request->query('page', 1);

        $shops = $this->shopService->list($perPage, $page);

        return $this->successResponse($shops, 'Shops fetched successfully');
    }

    public function store(StoreShopRequest $request): JsonResponse
    {
        $shop = $this->shopService->create($request->validated());

        return $this->successResponse($shop, 'Shop created successfully', 201);
    }

    public function show(int $id): JsonResponse
    {
        $shop = $this->shopService->findOrFail($id);

        return $this->successResponse($shop, 'Shop fetched successfully');
    }

    public function update(UpdateShopRequest $request, int $id): JsonResponse
    {
        $shop = $this->shopService->findOrFail($id);
        $shop = $this->shopService->update($shop, $request->validated());

        return $this->successResponse($shop, 'Shop updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $shop = $this->shopService->findOrFail($id);
        $this->shopService->delete($shop);

        return $this->successResponse(null, 'Shop deleted successfully');
    }
}
