<?php

namespace App\Services;

use App\Enums\SalaryCalculationTypeEnum;
use App\Models\SalaryComponent;
use App\Models\UserSalaryStructure;
use App\Models\UserSalaryStructureItem;
use BackedEnum;
use Illuminate\Support\Facades\DB;

class SalaryComponentService
{
    public function search(int $orgId, array $filters = []): array
    {
        $query = SalaryComponent::where('organization_id', $orgId)->where('deleted', 0);

        if (!empty($filters['keyword'])) {
            $query->where(function ($builder) use ($filters) {
                $keyword = '%' . $filters['keyword'] . '%';
                $builder->where('name', 'like', $keyword)
                    ->orWhere('code', 'like', $keyword);
            });
        }
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
            'created_by' => $createdBy,
        ]));
    }

    public function update(int $orgId, int $id, array $data): SalaryComponent
    {
        $component = SalaryComponent::where('organization_id', $orgId)->where('id', $id)->firstOrFail();
        $component->update($data);
        return $component->fresh();
    }

    public function delete(int $orgId, int $id): void
    {
        SalaryComponent::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }

    public function getUserSalaryStructure(int $orgId, int $userId): ?array
    {
        $structure = UserSalaryStructure::where('organization_id', $orgId)
            ->where('user_id', $userId)
            ->where('is_current', 1)
            ->where('deleted', 0)
            ->with('items.component')
            ->latest('effective_from')
            ->first();

        return $structure ? $this->formatStructure($structure) : null;
    }

    public function saveUserSalaryStructure(int $orgId, int $userId, array $data, int $createdBy): array
    {
        return DB::transaction(function () use ($orgId, $userId, $data, $createdBy) {
            $grossSalary = (float) ($data['gross_salary'] ?? 0);
            $basicSalary = (float) ($data['basic_salary'] ?? 0);
            $effectiveFrom = $data['effective_from'] ?? now()->toDateString();
            $items = collect($data['items'] ?? []);

            UserSalaryStructure::where('organization_id', $orgId)
                ->where('user_id', $userId)
                ->where('is_current', 1)
                ->update([
                    'is_current' => 0,
                    'effective_to' => date('Y-m-d', strtotime($effectiveFrom . ' -1 day')),
                ]);

            $structure = UserSalaryStructure::create([
                'organization_id' => $orgId,
                'user_id' => $userId,
                'gross_salary' => $grossSalary,
                'basic_salary' => $basicSalary,
                'effective_from' => $effectiveFrom,
                'is_current' => 1,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $createdBy,
            ]);

            $components = SalaryComponent::where('organization_id', $orgId)
                ->where('deleted', 0)
                ->whereIn(
                    'id',
                    $items
                        ->map(fn (array $item) => (int) ($item['component_id'] ?? $item['salary_component_id'] ?? 0))
                        ->filter()
                        ->values()
                )
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $componentId = (int) ($item['component_id'] ?? $item['salary_component_id'] ?? 0);
                $component = $components->get($componentId);

                if (!$component) {
                    continue;
                }

                $componentType = (int) ($item['component_type'] ?? $this->enumValue($component->component_type));
                $calculationType = (int) ($item['calculation_type'] ?? $this->enumValue($component->calculation_type));
                $value = array_key_exists('value', $item) ? (float) $item['value'] : (float) $component->default_value;
                $calculatedAmount = array_key_exists('calculated_amount', $item)
                    ? (float) $item['calculated_amount']
                    : $this->calculateItemAmount($calculationType, $value, $basicSalary);

                UserSalaryStructureItem::create([
                    'organization_id' => $orgId,
                    'salary_structure_id' => $structure->id,
                    'component_id' => $componentId,
                    'component_type' => $componentType,
                    'calculation_type' => $calculationType,
                    'value' => $value,
                    'calculated_amount' => $calculatedAmount,
                ]);
            }

            return $this->getUserSalaryStructure($orgId, $userId)
                ?? $this->formatStructure($structure->load('items.component'));
        });
    }

    private function formatStructure(UserSalaryStructure $structure): array
    {
        return [
            'id' => $structure->id,
            'user_id' => $structure->user_id,
            'gross_salary' => (float) $structure->gross_salary,
            'basic_salary' => (float) $structure->basic_salary,
            'effective_from' => $structure->effective_from?->toDateString(),
            'effective_to' => $structure->effective_to?->toDateString(),
            'remarks' => $structure->remarks,
            'items' => $structure->items
                ->sortBy(fn (UserSalaryStructureItem $item) => $item->component?->sort_order ?? PHP_INT_MAX)
                ->map(function (UserSalaryStructureItem $item) {
                    return [
                        'salary_component_id' => $item->component_id,
                        'component_id' => $item->component_id,
                        'name' => $item->component?->name ?? '',
                        'code' => $item->component?->code ?? '',
                        'component_type' => $this->enumValue($item->component_type),
                        'calculation_type' => $this->enumValue($item->calculation_type),
                        'value' => (float) $item->value,
                        'calculated_amount' => (float) $item->calculated_amount,
                    ];
                })
                ->values(),
        ];
    }

    private function calculateItemAmount(int $calculationType, float $value, float $basicSalary): float
    {
        return round(match ($calculationType) {
            SalaryCalculationTypeEnum::PercentageOfBasic->value => ($basicSalary * $value) / 100,
            SalaryCalculationTypeEnum::Fixed->value,
            SalaryCalculationTypeEnum::PerDay->value => $value,
            default => $value,
        }, 2);
    }

    private function enumValue(mixed $value): int
    {
        if ($value instanceof BackedEnum) {
            return (int) $value->value;
        }

        return (int) $value;
    }
}
