<?php

namespace App\Http\Middleware;

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

       
        $request->attributes->set('user', $user);
        $request->attributes->set('organization_id', (int) $user->organization_id);

        return $next($request);
    }
}
