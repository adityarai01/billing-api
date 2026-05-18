<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);

        $categories = Category::query()
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

        return $this->successResponse($categories, 'Categories fetched successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $validated = $this->validatePayload($request, $organizationId);

        $category = Category::create([
            'organization_id' => $organizationId,
            'parent_id'       => $validated['parent_id'] ?? null,
            'name'            => $validated['name'],
            'image'           => $validated['image'] ?? null,
            'status'          => $validated['status'] ?? 1,
            'deleted'         => 0,
        ]);

        return $this->successResponse(
            $category->loadCount(['products' => fn ($query) => $query->where('deleted', 0)]),
            'Category created successfully',
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $category = $this->findCategory($id, $organizationId);
        $validated = $this->validatePayload($request, $organizationId, $category->id);

        $category->update([
            'parent_id' => $validated['parent_id'] ?? null,
            'name'      => $validated['name'],
            'image'     => $validated['image'] ?? null,
            'status'    => $validated['status'] ?? 1,
        ]);

        return $this->successResponse(
            $category->fresh()->loadCount(['products' => fn ($query) => $query->where('deleted', 0)]),
            'Category updated successfully'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $category = $this->findCategory($id, $organizationId);

        $category->update([
            'deleted' => 1,
            'status'  => 0,
        ]);

        return $this->successResponse(null, 'Category deleted successfully');
    }

    private function validatePayload(Request $request, int $organizationId, ?int $ignoreId = null): array
    {
        return Validator::make($request->all(), [
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(function ($query) use ($organizationId, $ignoreId) {
                    $query->where('deleted', 0)
                        ->where(function ($subQuery) use ($organizationId) {
                            $subQuery->where('organization_id', $organizationId)
                                ->orWhereNull('organization_id');
                        });

                    if ($ignoreId) {
                        $query->where('id', '!=', $ignoreId);
                    }
                }),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(function ($query) use ($organizationId) {
                    $query->where('deleted', 0)
                        ->where('organization_id', $organizationId);
                })->ignore($ignoreId),
            ],
            'image'  => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'integer', Rule::in([0, 1])],
        ])->validate();
    }

    private function findCategory(int $id, int $organizationId): Category
    {
        return Category::query()
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
