<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class WebmasterAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Token missing.',
            ], 401);
        }

        $adminId = Cache::get("webmaster:auth:{$token}");

        if (! $adminId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Invalid or expired token.',
            ], 401);
        }

        $admin = Admin::where('id', $adminId)
            ->where('deleted', 0)
            ->where('status', 1)
            ->first();

        if (! $admin) {
            Cache::forget("webmaster:auth:{$token}");

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Admin not found.',
            ], 401);
        }

        $request->attributes->set('auth_admin', $admin);

        return $next($request);
    }
}
