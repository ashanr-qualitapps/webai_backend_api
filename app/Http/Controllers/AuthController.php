<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
        // Generate tokens (placeholders)
        $accessToken = 'access_token_example';
        $refreshToken = 'refresh_token_example';
        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => $user,
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        // Placeholder logic for refreshing token
        $newAccessToken = 'new_access_token_example';
        return response()->json([
            'access_token' => $newAccessToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Placeholder logic for logout
        return response()->json(['message' => 'Logged out successfully']);
    }
}
