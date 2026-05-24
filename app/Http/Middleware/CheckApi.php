<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token missing.',
            ], 401);
        }

        $user = User::where("remember_token", $token)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Invalid or expired token.',
            ], 401);
        }

        // Resolve permissions: SuperAdmin (1) & ShopOwner (2) have all access
        $userTypeVal = $user->user_type?->value ?? 0;
        $isOwner     = in_array($userTypeVal, [1, 2], true);
        $permissions = $isOwner ? ['*'] : $this->resolvePermissions($user);

        $request->attributes->set('user', $user);
        $request->attributes->set('user_id', (int) $user->id);
        $request->attributes->set('organization_id', (int) $user->organization_id);
        $request->attributes->set('permissions', $permissions);
        $request->attributes->set('is_owner', $isOwner);
        $request->setUserResolver(static fn() => $user);

        return $next($request);
    }

    private function resolvePermissions(User $user): array
    {
        if (!$user->role_id) return [];
        $role = Role::with('permissions')->find($user->role_id);
        return $role ? $role->permissionKeys() : [];
    }
}
