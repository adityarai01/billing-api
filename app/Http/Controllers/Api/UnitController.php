<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UnitController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);

        $units = Unit::query()
            ->withCount([
                'productVariants' => fn ($query) => $query->where('deleted', 0),
            ])
            ->where('deleted', 0)
            ->where('organization_id', $organizationId)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('short_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->get();

        return $this->successResponse($units, 'Units fetched successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $validated = $this->validatePayload($request, $organizationId);

        $unit = Unit::create([
            'organization_id' => $organizationId,
            'name'            => $validated['name'],
            'short_name'      => $validated['short_name'] ?? null,
            'status'          => $validated['status'] ?? 1,
            'deleted'         => 0,
        ]);

        return $this->successResponse(
            $unit->loadCount(['productVariants' => fn ($query) => $query->where('deleted', 0)]),
            'Unit created successfully',
            201
        );
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $unit = $this->findUnit($id, $organizationId);
        $validated = $this->validatePayload($request, $organizationId, $unit->id);

        $unit->update([
            'name'       => $validated['name'],
            'short_name' => $validated['short_name'] ?? null,
            'status'     => $validated['status'] ?? 1,
        ]);

        return $this->successResponse(
            $unit->fresh()->loadCount(['productVariants' => fn ($query) => $query->where('deleted', 0)]),
            'Unit updated successfully'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $organizationId = $this->organizationId($request);
        $unit = $this->findUnit($id, $organizationId);

        $unit->update([
            'deleted' => 1,
            'status'  => 0,
        ]);

        return $this->successResponse(null, 'Unit deleted successfully');
    }

    private function validatePayload(Request $request, int $organizationId, ?int $ignoreId = null): array
    {
        return Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units', 'name')->where(function ($query) use ($organizationId) {
                    $query->where('deleted', 0)
                        ->where('organization_id', $organizationId);
                })->ignore($ignoreId),
            ],
            'short_name' => ['nullable', 'string', 'max:20'],
            'status'     => ['nullable', 'integer', Rule::in([0, 1])],
        ])->validate();
    }

    private function findUnit(int $id, int $organizationId): Unit
    {
        return Unit::query()
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
