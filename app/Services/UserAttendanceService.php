<?php

namespace App\Services;

use App\Models\UserAttendance;

class UserAttendanceService
{
    public function search(int $orgId, array $filters = []): array
    {
        $query = UserAttendance::where('organization_id', $orgId)->where('deleted', 0)
            ->with('user:id,name,employee_code,department,designation');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['from_date'])) {
            $query->where('attendance_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('attendance_date', '<=', $filters['to_date']);
        }
        if (!empty($filters['month'])) {
            $query->whereMonth('attendance_date', $filters['month']);
        }
        if (!empty($filters['year'])) {
            $query->whereYear('attendance_date', $filters['year']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 50), 200);
        $total   = $query->count();
        $records = $query->orderByDesc('attendance_date')->paginate($perPage);

        return ['record' => $records->items(), 'total_data' => $total];
    }

    public function markAttendance(int $orgId, array $data, int $createdBy): UserAttendance
    {
        $attendance = UserAttendance::where('organization_id', $orgId)
            ->where('user_id', $data['user_id'])
            ->where('attendance_date', $data['attendance_date'])
            ->first();

        if ($attendance) {
            $attendance->update(array_merge($data, ['updated_by' => $createdBy]));
            return $attendance->fresh();
        }

        return UserAttendance::create(array_merge($data, [
            'organization_id' => $orgId,
            'created_by'      => $createdBy,
        ]));
    }

    public function bulkMark(int $orgId, array $records, int $createdBy): int
    {
        $count = 0;
        foreach ($records as $record) {
            $this->markAttendance($orgId, $record, $createdBy);
            $count++;
        }
        return $count;
    }

    public function monthlySummary(int $orgId, int $userId, int $year, int $month): array
    {
        $records = UserAttendance::where('organization_id', $orgId)
            ->where('user_id', $userId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->where('deleted', 0)
            ->get();

        return [
            'present'    => $records->where('status', 1)->count(),
            'absent'     => $records->where('status', 2)->count(),
            'half_day'   => $records->where('status', 3)->count(),
            'late'       => $records->where('status', 4)->count(),
            'on_leave'   => $records->where('status', 5)->count(),
            'holiday'    => $records->where('status', 6)->count(),
            'weekly_off' => $records->where('status', 7)->count(),
            'total'      => $records->count(),
            'records'    => $records,
        ];
    }

    public function delete(int $orgId, int $id): void
    {
        UserAttendance::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }
}
