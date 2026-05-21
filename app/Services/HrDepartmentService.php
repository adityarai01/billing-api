<?php

namespace App\Services;

use App\Models\HrDepartment;
use App\Models\HrDesignation;

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
        return ['record' => $records, 'total_data' => $records->count()];
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
        return ['record' => $records, 'total_data' => $records->count()];
    }

    public function createDesignation(int $orgId, array $data, int $createdBy): HrDesignation
    {
        return HrDesignation::create(array_merge($data, [
            'organization_id' => $orgId,
            'created_by'      => $createdBy,
        ]));
    }

    public function updateDesignation(int $orgId, int $id, array $data, int $updatedBy): HrDesignation
    {
        $desig = HrDesignation::where('organization_id', $orgId)->where('id', $id)->where('deleted', 0)->firstOrFail();
        $desig->update(array_merge($data, ['updated_by' => $updatedBy]));
        return $desig->fresh();
    }

    public function deleteDesignation(int $orgId, int $id): void
    {
        HrDesignation::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }
}
