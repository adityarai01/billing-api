<?php

namespace App\Services;

use App\Models\HrDepartment;
use App\Models\Payroll;
use App\Models\User;
use App\Models\UserAdvance;
use App\Models\UserAttendance;
use App\Models\UserLeave;
use Illuminate\Support\Facades\Hash;

class StaffUserService
{
    public function search(int $orgId, array $filters = []): array
    {
        $query = User::where('organization_id', $orgId)
            ->where('deleted', 0);

        if (!empty($filters['keyword'])) {
            $kw = '%' . $filters['keyword'] . '%';
            $query->where(fn($q) => $q->where('name', 'like', $kw)
                ->orWhere('mobile_no', 'like', $kw)
                ->orWhere('employee_code', 'like', $kw)
                ->orWhere('email', 'like', $kw));
        }
        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        if (!empty($filters['designation'])) {
            $query->where('designation', $filters['designation']);
        }
        if (isset($filters['employment_type'])) {
            $query->where('employment_type', $filters['employment_type']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }

        $perPage = min((int) ($filters['per_page'] ?? 20), 100);
        $total   = $query->count();
        $records = $query->select([
            'id', 'name', 'email', 'mobile_no', 'user_type', 'employee_code',
            'designation', 'department', 'employment_type', 'joining_date',
            'profile_image', 'gender', 'status', 'login_enabled', 'created_at',
        ])->orderBy('name')->paginate($perPage);

        return ['record' => $records->items(), 'total_data' => $total];
    }

    public function details(int $orgId, int $id): ?User
    {
        return User::where('organization_id', $orgId)
            ->where('id', $id)
            ->where('deleted', 0)
            ->with(['currentSalaryStructure.items.component'])
            ->first();
    }

    public function create(int $orgId, array $data, int $createdBy): User
    {
        $data['organization_id'] = $orgId;
        $data['created_by']      = $createdBy;
        $data['password']        = Hash::make($data['password'] ?? 'Staff@1234');

        if (empty($data['employee_code'])) {
            $data['employee_code'] = $this->generateEmployeeCode($orgId);
        }

        return User::create($data);
    }

    public function update(int $orgId, int $id, array $data, int $updatedBy): User
    {
        $user = User::where('organization_id', $orgId)->where('id', $id)->where('deleted', 0)->firstOrFail();

        $data['updated_by'] = $updatedBy;
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return $user->fresh();
    }

    public function delete(int $orgId, int $id): void
    {
        User::where('organization_id', $orgId)->where('id', $id)->update(['deleted' => 1]);
    }

    public function changeStatus(int $orgId, int $id, int $status): void
    {
        User::where('organization_id', $orgId)->where('id', $id)->update(['status' => $status]);
    }

    public function dashboardStats(int $orgId): array
    {
        $today = now()->toDateString();
        $month = (int) now()->month;
        $year  = (int) now()->year;

        $totalStaff   = User::where('organization_id', $orgId)->where('deleted', 0)->whereNotNull('employee_code')->count();
        $activeStaff  = User::where('organization_id', $orgId)->where('deleted', 0)->whereNotNull('employee_code')->where('status', 1)->count();
        $presentToday = UserAttendance::where('organization_id', $orgId)->where('attendance_date', $today)->where('status', 1)->where('deleted', 0)->count();
        $onLeaveToday = UserAttendance::where('organization_id', $orgId)->where('attendance_date', $today)->where('status', 5)->where('deleted', 0)->count();
        $pendingLeaves   = UserLeave::where('organization_id', $orgId)->where('status', 1)->where('deleted', 0)->count();
        $pendingAdvances = UserAdvance::where('organization_id', $orgId)->where('status', 1)->where('deleted', 0)->count();
        $payrollCount    = Payroll::where('organization_id', $orgId)->where('pay_year', $year)->where('pay_month', $month)->where('deleted', 0)->count();
        $salaryDisbursed = Payroll::where('organization_id', $orgId)->where('pay_year', $year)->where('pay_month', $month)->where('status', 4)->where('deleted', 0)->sum('net_salary');
        $totalSalaryDue  = Payroll::where('organization_id', $orgId)->where('pay_year', $year)->where('pay_month', $month)->whereIn('status', [2, 3])->where('deleted', 0)->sum('net_salary');
        $deptCount       = HrDepartment::where('organization_id', $orgId)->where('deleted', 0)->where('status', 1)->count();

        $recentAttendance = UserAttendance::where('organization_id', $orgId)
            ->where('attendance_date', $today)->where('deleted', 0)
            ->with('user:id,name,employee_code')->orderByDesc('id')->limit(10)->get();

        $recentLeaves = UserLeave::where('organization_id', $orgId)->where('deleted', 0)
            ->with(['user:id,name,employee_code', 'leaveType:id,name,code'])
            ->orderByDesc('created_at')->limit(5)->get();

        $recentPayrolls = Payroll::where('organization_id', $orgId)
            ->where('pay_year', $year)->where('pay_month', $month)->where('deleted', 0)
            ->with('user:id,name,employee_code,department')->limit(5)->get();

        return [
            'total_staff'        => $totalStaff,
            'active_staff'       => $activeStaff,
            'present_today'      => $presentToday,
            'on_leave_today'     => $onLeaveToday,
            'pending_leaves'     => $pendingLeaves,
            'pending_advances'   => $pendingAdvances,
            'payroll_this_month' => $payrollCount,
            'salary_disbursed'   => (float) $salaryDisbursed,
            'total_salary_due'   => (float) $totalSalaryDue,
            'departments'        => $deptCount,
            'recent_attendance'  => $recentAttendance,
            'recent_leaves'      => $recentLeaves,
            'recent_payrolls'    => $recentPayrolls,
        ];
    }

    private function generateEmployeeCode(int $orgId): string
    {
        $last = User::where('organization_id', $orgId)
            ->where('deleted', 0)
            ->whereNotNull('employee_code')
            ->orderByDesc('id')
            ->value('employee_code');

        $num = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $num = (int) $m[1] + 1;
        }

        return 'EMP' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
