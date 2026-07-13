<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use App\Support\AppRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Log;

class AuthController extends Controller
{
    public function register(Request $request, SmsService $sms)
{
    $validator = Validator::make($request->all(), [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
        'password' => ['required', 'confirmed', 'min:8'],
        'address' => ['nullable', 'string', 'max:500'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    $plainPassword = $request->password;

    // ✅ CREATE USER ONLY ONCE
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'address' => $request->address,
        'password' => Hash::make($plainPassword),
        'is_active' => true,
    ]);

    // ✅ ROLE
    $user->syncRoles(['customer']);

    // ✅ TOKENS
    $accessToken = $user->createToken('api-token')->plainTextToken;
    $refreshToken = Str::random(64);

    $user->forceFill([
        'refresh_token' => hash('sha256', $refreshToken),
        'refresh_token_expires_at' => now()->addDays(30),
    ])->save();

    // ✅ SMS (no password sending)
    try {
        $message = "Welcome {$user->name}! Your Adama MESOB account has been created successfully. You can log in using your email or phone number. If you have any questions, please contact 9141 free call.";
        $sms->sendToPhone($user->phone, $message);
    } catch (\Throwable $exception) {
        Log::error('Registration SMS failed: ' . $exception->getMessage());
    }

    return response()->json(
        $this->authPayload($user, $accessToken, $refreshToken),
        201
    )->cookie($this->refreshCookie($refreshToken));
}

    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required_without:email', 'nullable', 'string'],
            'email' => ['required_without:login', 'nullable', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = $request->login ?? $request->email;

        $user = User::where('email', $loginInput)
            ->orWhere('phone', $loginInput)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid email/phone or password.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is disabled. Please contact the administrator.',
            ], 403);
        }

        $accessToken = $user->createToken('aig-api-token')->plainTextToken;
        $refreshToken = Str::random(64);

        $user->forceFill([
            'refresh_token' => hash('sha256', $refreshToken),
            'refresh_token_expires_at' => now()->addDays(30),
            'last_login_at' => now(),
        ])->save();

        return response()->json($this->authPayload($user, $accessToken, $refreshToken))
            ->cookie($this->refreshCookie($refreshToken));
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => $this->userPayload($user),
            'user' => $this->userPayload($user),
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        $user?->currentAccessToken()?->delete();
        $user?->forceFill([
            'refresh_token' => null,
            'refresh_token_expires_at' => null,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ])->withoutCookie('refresh_token');
    }

    protected function authPayload(User $user, string $accessToken, string $refreshToken): array
    {
        return [
            'success' => true,
            'message' => 'Authenticated successfully',
            'token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => $this->userPayload($user),
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }

    protected function userPayload(User $user): array
    {
        $user->loadMissing(['city', 'subcity', 'woreda', 'roles']);
        $role = $user->getRoleNames()->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'status' => $user->is_active ? 'active' : 'disabled',
            'city_id' => $user->city_id,
            'subcity_id' => $user->subcity_id,
            'woreda_id' => $user->woreda_id,
            'location_level' => AppRoles::userLevel($user),
            'city' => $user->city,
            'subcity' => $user->subcity,
            'woreda' => $user->woreda,
            'role' => $role,
            'roles' => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
        ];
    }

    protected function refreshCookie(string $refreshToken)
    {
        return cookie(
            'refresh_token',
            $refreshToken,
            60 * 24 * 30,
            '/',
            null,
            app()->environment('production'),
            true
        )->withSameSite('Lax');
    }
}
