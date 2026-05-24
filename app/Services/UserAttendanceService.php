<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Models\UserAttendance;
use InvalidArgumentException;

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
        if (!empty($data['id'])) {
            $attendance = UserAttendance::where('organization_id', $orgId)
                ->where('id', $data['id'])
                ->where('deleted', 0)
                ->firstOrFail();

            $updateData = $data;
            unset($updateData['id']);

            $attendance->update(array_merge($updateData, ['updated_by' => $createdBy]));
            return $attendance->fresh();
        }

        if (empty($data['user_id']) || empty($data['attendance_date'])) {
            throw new InvalidArgumentException('user_id and attendance_date are required to mark attendance.');
        }

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

    public function monthlySummary(int $orgId, ?int $userId, int $year, int $month): array
    {
        if ($userId === null) {
            return $this->monthlyReport($orgId, $year, $month);
        }

        $records = UserAttendance::where('organization_id', $orgId)
            ->where('user_id', $userId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->where('deleted', 0)
            ->get();

        return [
            'present'    => $this->countStatus($records, AttendanceStatusEnum::Present),
            'absent'     => $this->countStatus($records, AttendanceStatusEnum::Absent),
            'half_day'   => $this->countStatus($records, AttendanceStatusEnum::HalfDay),
            'late'       => $this->countStatus($records, AttendanceStatusEnum::Late),
            'on_leave'   => $this->countStatus($records, AttendanceStatusEnum::OnLeave),
            'holiday'    => $this->countStatus($records, AttendanceStatusEnum::Holiday),
            'weekly_off' => $this->countStatus($records, AttendanceStatusEnum::WeeklyOff),
            'total'      => $records->count(),
            'records'    => $records,
        ];
    }

    public function delete(int $orgId, int $id): void
    {
        UserAttendance::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }

    private function monthlyReport(int $orgId, int $year, int $month): array
    {
        $records = UserAttendance::where('organization_id', $orgId)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->where('deleted', 0)
            ->with('user:id,name,employee_code,department')
            ->get()
            ->groupBy('user_id');

        $report = $records->map(function ($userRecords, $userId) {
            $firstRecord = $userRecords->first();
            $user = $firstRecord?->user;

            return [
                'user_id' => (int) $userId,
                'name' => $user?->name ?? 'Unknown',
                'employee_code' => $user?->employee_code ?? '',
                'department' => $user?->department ?? '',
                'present' => $this->countStatus($userRecords, AttendanceStatusEnum::Present),
                'absent' => $this->countStatus($userRecords, AttendanceStatusEnum::Absent),
                'half_day' => $this->countStatus($userRecords, AttendanceStatusEnum::HalfDay),
                'leave' => $this->countStatus($userRecords, AttendanceStatusEnum::OnLeave),
                'total_days' => $userRecords->count(),
            ];
        })->values();

        return [
            'record' => $report,
            'total_data' => $report->count(),
        ];
    }

    private function countStatus($records, AttendanceStatusEnum $status): int
    {
        return $records->filter(
            fn (UserAttendance $record) => (int) $record->getRawOriginal('status') === $status->value
        )->count();
    }
}
