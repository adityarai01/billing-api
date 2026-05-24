<?php

namespace App\Services;

use App\Enums\AttendanceStatusEnum;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use App\Models\UserAttendance;
use App\Models\UserAdvance;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function search(int $orgId, array $filters = []): array
    {
        $query = Payroll::where('organization_id', $orgId)->where('deleted', 0)
            ->with('user:id,name,employee_code,department,designation');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['pay_year'])) {
            $query->where('pay_year', $filters['pay_year']);
        }
        if (!empty($filters['pay_month'])) {
            $query->where('pay_month', $filters['pay_month']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 30), 100);
        $total   = $query->count();
        $records = $query->orderByDesc('pay_year')->orderByDesc('pay_month')->paginate($perPage);

        return ['record' => $records->items(), 'total_data' => $total];
    }

    public function generate(int $orgId, int $year, int $month, int $createdBy): array
    {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $staff       = User::where('organization_id', $orgId)
            ->where('deleted', 0)
            ->where('status', 1)
            ->whereNotNull('employee_code')
            ->get();

        $generated = [];
        DB::transaction(function () use ($orgId, $year, $month, $daysInMonth, $staff, $createdBy, &$generated) {
            foreach ($staff as $user) {
                if (Payroll::where('organization_id', $orgId)
                    ->where('user_id', $user->id)
                    ->where('pay_year', $year)
                    ->where('pay_month', $month)
                    ->exists()) {
                    continue;
                }

                $structure = $user->currentSalaryStructure;
                if (!$structure) {
                    continue;
                }

                $attendance = UserAttendance::where('organization_id', $orgId)
                    ->where('user_id', $user->id)
                    ->whereYear('attendance_date', $year)
                    ->whereMonth('attendance_date', $month)
                    ->where('deleted', 0)
                    ->get();

                $presentDays    = $this->countAttendanceStatus($attendance, AttendanceStatusEnum::Present);
                $halfDays       = $this->countAttendanceStatus($attendance, AttendanceStatusEnum::HalfDay);
                $paidLeaveDays  = $this->countAttendanceStatus($attendance, AttendanceStatusEnum::OnLeave);
                $nonWorkingDays = $this->countAttendanceStatuses($attendance, [
                    AttendanceStatusEnum::Holiday,
                    AttendanceStatusEnum::WeeklyOff,
                ]);
                $absentDays     = $daysInMonth - $presentDays - $halfDays - $paidLeaveDays - $nonWorkingDays;
                $workingDays    = $daysInMonth - $nonWorkingDays;
                $effectiveDays  = $presentDays + ($halfDays * 0.5) + $paidLeaveDays;
                $overtimeHours  = $attendance->sum('overtime_hours');

                $perDaySalary   = $structure->gross_salary / max($workingDays, 1);
                $unpaidDays     = max($absentDays, 0);
                $deductForAbsent = $unpaidDays * $perDaySalary;

                $earnings   = 0;
                $deductions = 0;
                $items      = [];

                foreach ($structure->items as $item) {
                    $amount = $item->calculated_amount;
                    if ($item->component_type->value === 1) {
                        $earnings += $amount;
                        $items[]   = ['component_id' => $item->component_id, 'component_name' => $item->component?->name ?? '', 'component_type' => 1, 'value' => $item->value, 'amount' => $amount];
                    } else {
                        $deductions += $amount;
                        $items[]     = ['component_id' => $item->component_id, 'component_name' => $item->component?->name ?? '', 'component_type' => 2, 'value' => $item->value, 'amount' => $amount];
                    }
                }

                // Absent deduction item
                if ($deductForAbsent > 0) {
                    $deductions += $deductForAbsent;
                    $items[]     = ['component_id' => 0, 'component_name' => 'Absent Deduction', 'component_type' => 2, 'value' => $unpaidDays, 'amount' => round($deductForAbsent, 2)];
                }

                // Advance recovery
                $advanceDeduction = (float) UserAdvance::where('organization_id', $orgId)
                    ->where('user_id', $user->id)
                    ->where('status', 2)
                    ->where('recover_from_salary', 1)
                    ->whereColumn('recovered_amount', '<', 'amount')
                    ->sum(DB::raw('LEAST(amount - recovered_amount, amount / recovery_months)'));

                $netSalary = max(0, $earnings - $deductions - $advanceDeduction);

                $payroll = Payroll::create([
                    'organization_id'   => $orgId,
                    'user_id'           => $user->id,
                    'pay_year'          => $year,
                    'pay_month'         => $month,
                    'working_days'      => $workingDays,
                    'present_days'      => $presentDays,
                    'absent_days'       => max($absentDays, 0),
                    'half_days'         => $halfDays,
                    'paid_leave_days'   => $paidLeaveDays,
                    'unpaid_leave_days' => $unpaidDays,
                    'overtime_hours'    => $overtimeHours,
                    'gross_salary'      => $structure->gross_salary,
                    'basic_salary'      => $structure->basic_salary,
                    'total_earnings'    => round($earnings, 2),
                    'total_deductions'  => round($deductions, 2),
                    'advance_deduction' => round($advanceDeduction, 2),
                    'net_salary'        => round($netSalary, 2),
                    'status'            => 2,
                    'created_by'        => $createdBy,
                ]);

                foreach ($items as $item) {
                    PayrollItem::create(array_merge($item, [
                        'organization_id' => $orgId,
                        'payroll_id'      => $payroll->id,
                    ]));
                }

                $generated[] = $payroll->id;
            }
        });

        return $generated;
    }

    public function details(int $orgId, int $id): ?Payroll
    {
        return Payroll::where('organization_id', $orgId)->where('id', $id)->where('deleted', 0)
            ->with(['user:id,name,employee_code,designation,department', 'items', 'payments'])
            ->first();
    }

    public function changeStatus(int $orgId, int $id, int $status, int $updatedBy): Payroll
    {
        $payroll = Payroll::where('organization_id', $orgId)->where('id', $id)->firstOrFail();
        $payroll->update([
            'status'      => $status,
            'approved_by' => in_array($status, [3, 4]) ? $updatedBy : $payroll->approved_by,
            'approved_at' => in_array($status, [3, 4]) ? now() : $payroll->approved_at,
            'updated_by'  => $updatedBy,
        ]);
        return $payroll->fresh();
    }

    private function countAttendanceStatus($records, AttendanceStatusEnum $status): int
    {
        return $records->filter(
            fn (UserAttendance $record) => (int) $record->getRawOriginal('status') === $status->value
        )->count();
    }

    private function countAttendanceStatuses($records, array $statuses): int
    {
        $allowed = array_map(fn (AttendanceStatusEnum $status) => $status->value, $statuses);

        return $records->filter(
            fn (UserAttendance $record) => in_array((int) $record->getRawOriginal('status'), $allowed, true)
        )->count();
    }
}
