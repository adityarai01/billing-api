<?php

namespace App\Services;

use App\Models\UserAdvance;

class UserAdvanceService
{
    public function search(int $orgId, array $filters = []): array
    {
        $query = UserAdvance::where('organization_id', $orgId)->where('deleted', 0)
            ->with('user:id,name,employee_code');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['from_date'])) {
            $query->where('advance_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('advance_date', '<=', $filters['to_date']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);
        $total   = $query->count();
        $records = $query->orderByDesc('advance_date')->paginate($perPage);

        return ['record' => $records->items(), 'total_data' => $total];
    }

    public function create(int $orgId, array $data, int $createdBy): UserAdvance
    {
        return UserAdvance::create(array_merge($data, [
            'organization_id' => $orgId,
            'created_by'      => $createdBy,
            'status'          => 1,
        ]));
    }

    public function updateStatus(int $orgId, int $id, int $status, ?string $rejectionReason, int $updatedBy): UserAdvance
    {
        $advance = UserAdvance::where('organization_id', $orgId)->where('id', $id)->firstOrFail();
        $advance->update([
            'status'           => $status,
            'rejection_reason' => $rejectionReason,
            'approved_by'      => in_array($status, [2]) ? $updatedBy : $advance->approved_by,
            'approved_at'      => in_array($status, [2]) ? now() : $advance->approved_at,
            'updated_by'       => $updatedBy,
        ]);
        return $advance->fresh();
    }

    public function delete(int $orgId, int $id): void
    {
        UserAdvance::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }
}
