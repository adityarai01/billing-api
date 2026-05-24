<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use ApiResponseTrait;

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'user_name' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('mobile_no', $request->input('user_name'))
            ->orWhere("email", $request->input("user_name"))
            ->first();

        if (!$user || !Hash::check((string) $request->input('password'), (string) $user->getAttribute('password'))) {
            throw ValidationException::withMessages([
                'user_name' => ['Invalid credentials.'],
            ]);
        }

        if ($user->status === 0) {
            return $this->errorResponse('Your account has been deactivated.', 403);
        }

        $raw = $user->name . '|' . now();
        $token = base64_encode($raw);

        $user->remember_token = $token;
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $user->load(['organization', 'role:id,display_name']);

        return $this->successResponse([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();
       
        $user = User::where("remember_token", $token)->first();
        if ($user) {
            $user->remember_token = "";
            $user->save();
        }

        return $this->successResponse(null, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        $user->load(['organization', 'role:id,display_name']);

        return $this->successResponse(
            $this->formatUser($user, $request->attributes->get('permissions', [])),
            'Authenticated User'
        );
    }

    private function formatUser($user, array $permissions = []): array
    {
        $org = $user->organization;

        return [
            'id'          => $user->getKey(),
            'name'        => $user->getAttribute('name'),
            'email'       => $user->getAttribute('email'),
            'mobile_no'   => $user->getAttribute('mobile_no'),
            'profile_image' => $this->normalizeMediaPath($user->getAttribute('profile_image')),
            'user_type'   => $user->getAttribute('user_type') instanceof \BackedEnum
                                 ? $user->getAttribute('user_type')->value
                                 : $user->getAttribute('user_type'),
            'role_id'     => $user->getAttribute('role_id'),
            'role_name'   => $user->role?->display_name,
            'last_login_at' => $user->getAttribute('last_login_at')?->toDateTimeString(),
            'last_login_ip' => $user->getAttribute('last_login_ip'),
            'permissions' => $permissions,
            'organization' => $org ? [
                'id'                  => $org->getKey(),
                'shop_name'           => $org->shop_name,
                'business_name'       => $org->business_name,
                'owner_name'          => $org->owner_name,
                'shop_type'           => $org->shop_type instanceof \BackedEnum ? $org->shop_type->value : $org->shop_type,
                'mobile_no'           => $org->mobile_no,
                'email'               => $org->email,
                'gstin'               => $org->gstin,
                'pan_no'              => $org->pan_no,
                'address'             => $org->address,
                'city'                => $org->city,
                'state'               => $org->state,
                'pincode'             => $org->pincode,
                'country'             => $org->country,
                'logo'                => $this->normalizeMediaPath($org->logo),
                'currency'            => $org->currency,
                'timezone'            => $org->timezone,
                'invoice_prefix'      => $org->invoice_prefix,
                'invoice_start_no'    => $org->invoice_start_no,
                'subscription_status' => $org->subscription_status instanceof \BackedEnum ? $org->subscription_status->value : $org->subscription_status,
                'trial_end_date'      => $org->trial_end_date?->toDateString(),
            ] : null,
        ];
    }

    private function normalizeMediaPath(?string $path): ?string
    {
        if (!filled($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (Str::startsWith($path, ['/storage/', 'storage/'])) {
            return url('/' . ltrim($path, '/'));
        }

        return Storage::disk('public')->url($path);
    }
}
