<?php

namespace App\Services;

use App\Models\HrShift;
use App\Models\UserShift;

class HrShiftService
{
    public function searchShifts(int $orgId, array $filters = []): array
    {
        $query = HrShift::where('organization_id', $orgId)->where('deleted', 0);

        if (!empty($filters['keyword'])) {
            $query->where('name', 'like', '%' . $filters['keyword'] . '%');
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $records = $query->orderBy('name')->get();
        return ['record' => $records, 'total_data' => $records->count()];
    }

    public function createShift(int $orgId, array $data, int $createdBy): HrShift
    {
        return HrShift::create(array_merge($data, [
            'organization_id' => $orgId,
            'created_by'      => $createdBy,
        ]));
    }

    public function updateShift(int $orgId, int $id, array $data, int $updatedBy): HrShift
    {
        $shift = HrShift::where('organization_id', $orgId)->where('id', $id)->where('deleted', 0)->firstOrFail();
        $shift->update(array_merge($data, ['updated_by' => $updatedBy]));
        return $shift->fresh();
    }

    public function deleteShift(int $orgId, int $id): void
    {
        HrShift::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }

    public function assignShift(int $orgId, int $userId, int $shiftId, string $effectiveFrom, ?string $effectiveTo, int $createdBy): UserShift
    {
        // Close any active shift assignment
        UserShift::where('organization_id', $orgId)
            ->where('user_id', $userId)
            ->whereNull('effective_to')
            ->update(['effective_to' => date('Y-m-d', strtotime($effectiveFrom . ' -1 day'))]);

        return UserShift::create([
            'organization_id' => $orgId,
            'user_id'         => $userId,
            'shift_id'        => $shiftId,
            'effective_from'  => $effectiveFrom,
            'effective_to'    => $effectiveTo,
            'created_by'      => $createdBy,
        ]);
    }

    public function getUserShifts(int $orgId, array $filters = []): array
    {
        $query = UserShift::where('organization_id', $orgId)->where('deleted', 0)
            ->with(['user:id,name,employee_code', 'shift:id,name,start_time,end_time']);

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['shift_id'])) {
            $query->where('shift_id', $filters['shift_id']);
        }

        $records = $query->orderByDesc('effective_from')->get();
        return ['record' => $records, 'total_data' => $records->count()];
    }
}
