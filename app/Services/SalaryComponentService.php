<?php

namespace App\Services;

use App\Models\SalaryComponent;
use App\Models\UserSalaryStructure;
use App\Models\UserSalaryStructureItem;
use Illuminate\Support\Facades\DB;

class SalaryComponentService
{
    public function search(int $orgId, array $filters = []): array
    {
        $query = SalaryComponent::where('organization_id', $orgId)->where('deleted', 0);

        if (isset($filters['component_type'])) {
            $query->where('component_type', $filters['component_type']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $records = $query->orderBy('sort_order')->orderBy('name')->get();
        return ['record' => $records, 'total_data' => $records->count()];
    }

    public function create(int $orgId, array $data, int $createdBy): SalaryComponent
    {
        return SalaryComponent::create(array_merge($data, [
            'organization_id' => $orgId,
            'created_by'      => $createdBy,
        ]));
    }

    public function update(int $orgId, int $id, array $data): SalaryComponent
    {
        $comp = SalaryComponent::where('organization_id', $orgId)->where('id', $id)->firstOrFail();
        $comp->update($data);
        return $comp->fresh();
    }

    public function delete(int $orgId, int $id): void
    {
        SalaryComponent::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }

    public function getUserSalaryStructure(int $orgId, int $userId): ?UserSalaryStructure
    {
        return UserSalaryStructure::where('organization_id', $orgId)
            ->where('user_id', $userId)
            ->where('is_current', 1)
            ->where('deleted', 0)
            ->with('items.component')
            ->latest('effective_from')
            ->first();
    }

    public function saveUserSalaryStructure(int $orgId, int $userId, array $data, int $createdBy): UserSalaryStructure
    {
        return DB::transaction(function () use ($orgId, $userId, $data, $createdBy) {
            // Mark previous as non-current
            UserSalaryStructure::where('organization_id', $orgId)
                ->where('user_id', $userId)
                ->where('is_current', 1)
                ->update(['is_current' => 0, 'effective_to' => date('Y-m-d', strtotime(($data['effective_from'] ?? now()->toDateString()) . ' -1 day'))]);

            $structure = UserSalaryStructure::create([
                'organization_id' => $orgId,
                'user_id'         => $userId,
                'gross_salary'    => $data['gross_salary'],
                'basic_salary'    => $data['basic_salary'],
                'effective_from'  => $data['effective_from'] ?? now()->toDateString(),
                'is_current'      => 1,
                'remarks'         => $data['remarks'] ?? null,
                'created_by'      => $createdBy,
            ]);

            foreach ($data['items'] ?? [] as $item) {
                UserSalaryStructureItem::create([
                    'organization_id'    => $orgId,
                    'salary_structure_id' => $structure->id,
                    'component_id'       => $item['component_id'],
                    'component_type'     => $item['component_type'],
                    'calculation_type'   => $item['calculation_type'],
                    'value'              => $item['value'],
                    'calculated_amount'  => $item['calculated_amount'] ?? 0,
                ]);
            }

            return $structure->load('items.component');
        });
    }
}
