<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    // ─── List roles for this org ───────────────────────────────────────────────
    public function search(Request $request): JsonResponse
    {
        $orgId   = $request->attributes->get('organization_id');
        $keyword = $request->input('keyword', '');

        $query = Role::where('organization_id', $orgId)
            ->where('deleted', 0)
            ->withCount('users');

        if ($keyword) {
            $query->where(fn($q) => $q
                ->where('display_name', 'like', "%{$keyword}%")
                ->orWhere('name', 'like', "%{$keyword}%")
            );
        }

        $roles = $query->orderBy('display_name')->get();

        return response()->json(['success' => true, 'data' => ['record' => $roles]]);
    }

    // ─── Create role ───────────────────────────────────────────────────────────
    public function create(Request $request): JsonResponse
    {
        $orgId  = $request->attributes->get('organization_id');
        $userId = $request->attributes->get('user_id');

        $display = trim($request->input('display_name', ''));
        if (!$display) {
            return response()->json(['success' => false, 'messages' => ['message' => 'Display name is required.']], 422);
        }

        $name = Str::slug($display, '_');

        if (Role::where('organization_id', $orgId)->where('name', $name)->where('deleted', 0)->exists()) {
            return response()->json(['success' => false, 'messages' => ['message' => 'A role with this name already exists.']], 422);
        }

        $role = Role::create([
            'organization_id' => $orgId,
            'name'            => $name,
            'display_name'    => $display,
            'description'     => $request->input('description'),
            'created_by'      => $userId,
            'updated_by'      => $userId,
        ]);

        return response()->json(['success' => true, 'data' => $role, 'messages' => ['message' => 'Role created.']]);
    }

    // ─── Update role ───────────────────────────────────────────────────────────
    public function update(Request $request): JsonResponse
    {
        $orgId  = $request->attributes->get('organization_id');
        $userId = $request->attributes->get('user_id');
        $id     = (int) $request->input('id');

        $role = Role::where('organization_id', $orgId)->where('deleted', 0)->find($id);
        if (!$role) {
            return response()->json(['success' => false, 'messages' => ['message' => 'Role not found.']], 404);
        }

        $display = trim($request->input('display_name', $role->display_name));
        $name    = Str::slug($display, '_');

        $duplicate = Role::where('organization_id', $orgId)
            ->where('name', $name)
            ->where('deleted', 0)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return response()->json(['success' => false, 'messages' => ['message' => 'A role with this name already exists.']], 422);
        }

        $role->update([
            'name'         => $name,
            'display_name' => $display,
            'description'  => $request->input('description', $role->description),
            'updated_by'   => $userId,
        ]);

        return response()->json(['success' => true, 'data' => $role, 'messages' => ['message' => 'Role updated.']]);
    }

    // ─── Delete role ───────────────────────────────────────────────────────────
    public function delete(Request $request): JsonResponse
    {
        $orgId = $request->attributes->get('organization_id');
        $id    = (int) $request->input('id');

        $role = Role::where('organization_id', $orgId)->where('deleted', 0)->find($id);
        if (!$role) {
            return response()->json(['success' => false, 'messages' => ['message' => 'Role not found.']], 404);
        }

        if ($role->users()->count() > 0) {
            return response()->json(['success' => false, 'messages' => ['message' => 'Cannot delete role — users are assigned to it.']], 422);
        }

        $role->update(['deleted' => 1]);

        return response()->json(['success' => true, 'messages' => ['message' => 'Role deleted.']]);
    }

    // ─── Get permissions for a role ────────────────────────────────────────────
    public function getPermissions(Request $request, int $roleId): JsonResponse
    {
        $orgId = $request->attributes->get('organization_id');

        $role = Role::where('organization_id', $orgId)->where('deleted', 0)->find($roleId);
        if (!$role) {
            return response()->json(['success' => false, 'messages' => ['message' => 'Role not found.']], 404);
        }

        // All permissions grouped by module
        $all = Permission::orderBy('sort_order')->get()->groupBy('module');

        // Permission IDs this role has
        $granted = $role->permissions->pluck('id')->toArray();

        $modules = $all->map(function ($perms, $module) use ($granted) {
            $first = $perms->first();
            return [
                'module'       => $module,
                'module_label' => $first->module_label,
                'permissions'  => $perms->map(fn($p) => [
                    'id'      => $p->id,
                    'action'  => $p->action,
                    'label'   => $p->display_name,
                    'granted' => in_array($p->id, $granted),
                ])->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'role'    => $role,
                'modules' => $modules,
            ],
        ]);
    }

    // ─── Save permissions for a role ───────────────────────────────────────────
    public function savePermissions(Request $request): JsonResponse
    {
        $orgId  = $request->attributes->get('organization_id');
        $roleId = (int) $request->input('role_id');

        $role = Role::where('organization_id', $orgId)->where('deleted', 0)->find($roleId);
        if (!$role) {
            return response()->json(['success' => false, 'messages' => ['message' => 'Role not found.']], 404);
        }

        // Expect array of permission IDs to grant
        $permissionIds = array_filter(array_map('intval', (array) $request->input('permission_ids', [])));

        // Validate all ids belong to the permissions table
        $validIds = Permission::whereIn('id', $permissionIds)->pluck('id')->toArray();

        $role->permissions()->sync($validIds);

        return response()->json(['success' => true, 'messages' => ['message' => 'Permissions saved.']]);
    }

    // ─── List all permissions (for UI matrix) ─────────────────────────────────
    public function allPermissions(): JsonResponse
    {
        $grouped = Permission::orderBy('sort_order')->get()->groupBy('module')->map(function ($perms, $module) {
            return [
                'module'       => $module,
                'module_label' => $perms->first()->module_label,
                'actions'      => $perms->map(fn($p) => [
                    'id'     => $p->id,
                    'action' => $p->action,
                    'label'  => $p->display_name,
                ])->values(),
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $grouped]);
    }
}
