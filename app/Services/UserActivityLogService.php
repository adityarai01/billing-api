<?php

namespace App\Services;

use App\Models\UserActivityLog;

class UserActivityLogService
{
    public function log(int $orgId, int $userId, string $module, string $action, string $description, ?int $referenceId = null, ?string $referenceType = null, ?string $ip = null, array $meta = []): void
    {
        UserActivityLog::create([
            'organization_id' => $orgId,
            'user_id'         => $userId,
            'module'          => $module,
            'action'          => $action,
            'description'     => $description,
            'reference_id'    => $referenceId,
            'reference_type'  => $referenceType,
            'ip_address'      => $ip,
            'meta'            => !empty($meta) ? $meta : null,
            'logged_at'       => now(),
        ]);
    }

    public function search(int $orgId, array $filters = []): array
    {
        $query = UserActivityLog::where('organization_id', $orgId)
            ->with('user:id,name,employee_code');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (!empty($filters['from_date'])) {
            $query->where('logged_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('logged_at', '<=', $filters['to_date'] . ' 23:59:59');
        }
        if (!empty($filters['keyword'])) {
            $query->where('description', 'like', '%' . $filters['keyword'] . '%');
        }

        $perPage = min((int) ($filters['per_page'] ?? 50), 200);
        $total   = $query->count();
        $records = $query->orderByDesc('logged_at')->paginate($perPage);

        return ['record' => $records->items(), 'total_data' => $total];
    }
}
