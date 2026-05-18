<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Controller;
use App\Http\Request\Webmaster\Shopkeeper\StoreShopkeeperRequest;
use App\Http\Request\Webmaster\Shopkeeper\UpdateShopkeeperRequest;
use App\Service\Webmaster\ShopkeeperService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopkeeperController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly ShopkeeperService $shopkeeperService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $page    = (int) $request->query('page', 1);

        $shopkeepers = $this->shopkeeperService->list($perPage, $page);

        return $this->successResponse($shopkeepers, 'Shopkeepers fetched successfully');
    }

    public function store(StoreShopkeeperRequest $request): JsonResponse
    {
        $shopkeeper = $this->shopkeeperService->create($request->validated());

        return $this->successResponse($shopkeeper, 'Shopkeeper created successfully', 201);
    }

    public function show(int $id): JsonResponse
    {
        $shopkeeper = $this->shopkeeperService->findOrFail($id);

        return $this->successResponse($shopkeeper, 'Shopkeeper fetched successfully');
    }

    public function update(UpdateShopkeeperRequest $request, int $id): JsonResponse
    {
        $shopkeeper = $this->shopkeeperService->findOrFail($id);
        $shopkeeper = $this->shopkeeperService->update($shopkeeper, $request->validated());

        return $this->successResponse($shopkeeper, 'Shopkeeper updated successfully');
    }

    public function destroy(int $id): JsonResponse
    {
        $shopkeeper = $this->shopkeeperService->findOrFail($id);
        $this->shopkeeperService->delete($shopkeeper);

        return $this->successResponse(null, 'Shopkeeper deleted successfully');
    }
}
