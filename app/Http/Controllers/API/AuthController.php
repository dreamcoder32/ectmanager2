<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)
                    ->where('is_active', true)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        // Revoke previous tokens optionally if desired
        // $user->tokens()->delete();

        $token = $user->createToken($request->device_name ?: 'api-token');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->display_name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        // Revoke current access token
        if ($request->user()) {
            $request->user()->currentAccessToken()?->delete();
        }

        return response()->json(['message' => 'Logged out']);
    }
}