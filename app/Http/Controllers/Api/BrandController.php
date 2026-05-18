<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BrandController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);

        $brands = Brand::query()
            ->withCount([
                'products' => fn ($query) => $query->where('deleted', 0),
            ])
            ->where('deleted', 0)
            ->where('organization_id', $organizationId)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();

        return $this->successResponse($brands, 'Brands fetched successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $validated = $this->validatePayload($request, $organizationId);

        $brand = Brand::create([
            'organization_id' => $organizationId,
            'name'            => $validated['name'],
            'image'           => $validated['image'] ?? null,
            'status'          => $validated['status'] ?? 1,
            'deleted'         => 0,
        ]);

        return $this->successResponse(
            $brand->loadCount(['products' => fn ($query) => $query->where('deleted', 0)]),
            'Brand created successfully',
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $brand = $this->findBrand($id, $organizationId);
        $validated = $this->validatePayload($request, $organizationId, $brand->id);

        $brand->update([
            'name'   => $validated['name'],
            'image'  => $validated['image'] ?? null,
            'status' => $validated['status'] ?? 1,
        ]);

        return $this->successResponse(
            $brand->fresh()->loadCount(['products' => fn ($query) => $query->where('deleted', 0)]),
            'Brand updated successfully'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $brand = $this->findBrand($id, $organizationId);

        $brand->update([
            'deleted' => 1,
            'status'  => 0,
        ]);

        return $this->successResponse(null, 'Brand deleted successfully');
    }

    private function validatePayload(Request $request, int $organizationId, ?int $ignoreId = null): array
    {
        return Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'name')->where(function ($query) use ($organizationId) {
                    $query->where('deleted', 0)
                        ->where('organization_id', $organizationId);
                })->ignore($ignoreId),
            ],
            'image'  => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', Rule::in([0, 1])],
        ])->validate();
    }

    private function findBrand(int $id, int $organizationId): Brand
    {
        return Brand::query()
            ->where('id', $id)
            ->where('organization_id', $organizationId)
            ->where('deleted', 0)
            ->firstOrFail();
    }

    private function organizationId(Request $request): int
    {
        $user = $request->attributes->get('user');
        $user?->loadMissing('organization');
        $organizationId = $user->organization?->id;

        if (!$organizationId) {
            throw ValidationException::withMessages([
                'organization' => ['Organization not found for current user.'],
            ]);
        }

        return (int) $organizationId;
    }
}
