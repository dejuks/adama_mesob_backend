<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\FaydaService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FaydaController extends Controller
{
   public function callback(Request $request, FaydaService $fayda)
{
    try {
        $tokenData = $fayda->getToken($request->code);

        if (!isset($tokenData['access_token'])) {
            return response()->json([
                'error' => 'Token failed',
                'fayda_response' => $tokenData
            ], 500);
        }

        $userInfo = $fayda->userInfo($tokenData['access_token']);

        if (!isset($userInfo['sub'])) {
            return response()->json([
                'error' => 'User info failed',
                'fayda_response' => $userInfo
            ], 500);
        }

        $user = User::updateOrCreate(
            ['fin' => $userInfo['sub']],
            [
                'name' => $userInfo['name'] ?? 'Citizen',
                'email' => $userInfo['email'] ?? null,
                'is_fayda_verified' => true,
                'fayda_payload' => $userInfo,
            ]
        );

        $user->syncRoles(['customer']);

        $token = $user->createToken('mesob')->plainTextToken;

        // 🔥 IMPORTANT FIX: set cookies for middleware
        return response()
            ->json([
                'success' => true,
                'token' => $token,
                'user' => $user->load('roles'),
            ])
            ->cookie('token', $token, 60, '/', null, false, true)
            ->cookie('role', 'customer', 60, '/', null, false, true);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Fayda callback crashed',
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
        ], 500);
    }
}
    
}