<?php

namespace App\Services;

use App\Models\HrDepartment;
use App\Models\HrDesignation;
use App\Models\User;

class HrDepartmentService
{
    public function searchDepartments(int $orgId, array $filters = []): array
    {
        $query = HrDepartment::where('organization_id', $orgId)->where('deleted', 0);

        if (!empty($filters['keyword'])) {
            $query->where('name', 'like', '%' . $filters['keyword'] . '%');
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $records = $query->with('head:id,name')->orderBy('name')->get();
        $staffCounts = User::where('organization_id', $orgId)
            ->where('deleted', 0)
            ->whereNotNull('department')
            ->selectRaw('department, COUNT(*) as total')
            ->groupBy('department')
            ->pluck('total', 'department');

        return [
            'record' => $records->map(function (HrDepartment $department) use ($staffCounts) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'description' => $department->description,
                    'head_user_id' => $department->head_user_id,
                    'head_name' => $department->head?->name,
                    'staff_count' => (int) ($staffCounts[$department->name] ?? 0),
                    'status' => (int) $department->status,
                ];
            })->values(),
            'total_data' => $records->count(),
        ];
    }

    public function createDepartment(int $orgId, array $data, int $createdBy): HrDepartment
    {
        return HrDepartment::create(array_merge($data, [
            'organization_id' => $orgId,
            'created_by'      => $createdBy,
        ]));
    }

    public function updateDepartment(int $orgId, int $id, array $data, int $updatedBy): HrDepartment
    {
        $dept = HrDepartment::where('organization_id', $orgId)->where('id', $id)->where('deleted', 0)->firstOrFail();
        $dept->update(array_merge($data, ['updated_by' => $updatedBy]));
        return $dept->fresh();
    }

    public function deleteDepartment(int $orgId, int $id): void
    {
        HrDepartment::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }

    public function searchDesignations(int $orgId, array $filters = []): array
    {
        $query = HrDesignation::where('organization_id', $orgId)->where('deleted', 0);

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }
        if (!empty($filters['keyword'])) {
            $query->where('name', 'like', '%' . $filters['keyword'] . '%');
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $records = $query->with('department:id,name')->orderBy('name')->get();

        return [
            'record' => $records->map(function (HrDesignation $designation) {
                return [
                    'id' => $designation->id,
                    'name' => $designation->name,
                    'department_id' => $designation->department_id,
                    'department' => $designation->department?->name,
                    'description' => $designation->description,
                    'status' => (int) $designation->status,
                ];
            })->values(),
            'total_data' => $records->count(),
        ];
    }

    public function createDesignation(int $orgId, array $data, int $createdBy): HrDesignation
    {
        $data = $this->normalizeDesignationPayload($orgId, $data);

        return HrDesignation::create(array_merge($data, [
            'organization_id' => $orgId,
            'created_by'      => $createdBy,
        ]));
    }

    public function updateDesignation(int $orgId, int $id, array $data, int $updatedBy): HrDesignation
    {
        $data = $this->normalizeDesignationPayload($orgId, $data);

        $desig = HrDesignation::where('organization_id', $orgId)->where('id', $id)->where('deleted', 0)->firstOrFail();
        $desig->update(array_merge($data, ['updated_by' => $updatedBy]));
        return $desig->fresh();
    }

    public function deleteDesignation(int $orgId, int $id): void
    {
        HrDesignation::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }

    private function normalizeDesignationPayload(int $orgId, array $data): array
    {
        if (empty($data['department_id']) && !empty($data['department'])) {
            $data['department_id'] = HrDepartment::where('organization_id', $orgId)
                ->where('deleted', 0)
                ->where('name', $data['department'])
                ->value('id');
        }

        unset($data['department']);

        return $data;
    }
}
