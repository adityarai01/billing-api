<?php

namespace App\Services;

use App\Models\HrLeaveType;
use App\Models\UserLeave;

class UserLeaveService
{
    public function searchLeaveTypes(int $orgId, array $filters = []): array
    {
        $query = HrLeaveType::where('organization_id', $orgId)->where('deleted', 0);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        $records = $query->orderBy('name')->get();
        return ['record' => $records, 'total_data' => $records->count()];
    }

    public function createLeaveType(int $orgId, array $data, int $createdBy): HrLeaveType
    {
        return HrLeaveType::create(array_merge($data, [
            'organization_id' => $orgId,
            'created_by'      => $createdBy,
        ]));
    }

    public function updateLeaveType(int $orgId, int $id, array $data): HrLeaveType
    {
        $lt = HrLeaveType::where('organization_id', $orgId)->where('id', $id)->firstOrFail();
        $lt->update($data);
        return $lt->fresh();
    }

    public function deleteLeaveType(int $orgId, int $id): void
    {
        HrLeaveType::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }

    public function searchLeaves(int $orgId, array $filters = []): array
    {
        $query = UserLeave::where('organization_id', $orgId)->where('deleted', 0)
            ->with(['user:id,name,employee_code', 'leaveType:id,name,code,is_paid']);

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['from_date'])) {
            $query->where('from_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('to_date', '<=', $filters['to_date']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);
        $total   = $query->count();
        $records = $query->orderByDesc('from_date')->paginate($perPage);

        return ['record' => $records->items(), 'total_data' => $total];
    }

    public function applyLeave(int $orgId, array $data, int $createdBy): UserLeave
    {
        $targetUserId = !empty($data['user_id']) ? (int) $data['user_id'] : $createdBy;
        $totalDays    = $this->calculateDays($data['from_date'], $data['to_date']);

        return UserLeave::create([
            'organization_id' => $orgId,
            'user_id'         => $targetUserId,
            'leave_type_id'   => $data['leave_type_id'],
            'from_date'       => $data['from_date'],
            'to_date'         => $data['to_date'],
            'total_days'      => $totalDays,
            'reason'          => $data['reason'] ?? null,
            'status'          => 1,
            'created_by'      => $createdBy,
        ]);
    }

    public function updateLeaveStatus(int $orgId, int $id, int $status, ?string $rejectionReason, int $approvedBy): UserLeave
    {
        $leave = UserLeave::where('organization_id', $orgId)->where('id', $id)->firstOrFail();
        $leave->update([
            'status'           => $status,
            'rejection_reason' => $rejectionReason,
            'approved_by'      => $approvedBy,
            'approved_at'      => now(),
            'updated_by'       => $approvedBy,
        ]);
        return $leave->fresh();
    }

    public function deleteLeave(int $orgId, int $id): void
    {
        UserLeave::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }

    private function calculateDays(string $from, string $to): float
    {
        $diff = (new \DateTime($to))->diff(new \DateTime($from));
        return $diff->days + 1;
    }
}
