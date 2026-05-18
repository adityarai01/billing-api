<?php

namespace App\Http\Controllers\Webmaster;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use ApiResponseTrait;

    private const TOKEN_TTL = 60 * 60 * 8; // 8 hours

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'mobile_no' => ['required', 'string'],
            'password'  => ['required', 'string'],
        ]);

        $admin = Admin::where('mobile_no', $request->input('mobile_no'))
            ->where('deleted', 0)
            ->first();

        if (! $admin || ! Hash::check((string) $request->input('password'), (string) $admin->getAttribute('password'))) {
            throw ValidationException::withMessages([
                'mobile_no' => ['Invalid credentials.'],
            ]);
        }

        if ($admin->status === 0) {
            return $this->errorResponse('Your account has been deactivated.', 403);
        }

        $token = Str::random(60);
        Cache::put("webmaster:auth:{$token}", $admin->getKey(), self::TOKEN_TTL);

        $admin->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return $this->successResponse([
            'token' => $token,
            'admin' => [
                'id'        => $admin->getKey(),
                'name'      => $admin->getAttribute('name'),
                'email'     => $admin->getAttribute('email'),
                'mobile_no' => $admin->getAttribute('mobile_no'),
            ],
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if ($token) {
            Cache::forget("webmaster:auth:{$token}");
        }

        return $this->successResponse(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->attributes->get('auth_admin');

        return $this->successResponse([
            'id'        => $admin->getKey(),
            'name'      => $admin->getAttribute('name'),
            'email'     => $admin->getAttribute('email'),
            'mobile_no' => $admin->getAttribute('mobile_no'),
        ], 'Authenticated admin');
    }
}
