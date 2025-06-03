<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        \Log::info('Login attempt', ['email' => $credentials['email']]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;
            \Log::info('token', ['token' => $token]);
            return response()->json(['token' => $token, 'user_id' => $user->id], 200);
        }

        \Log::warning('Unauthorized login attempt', ['email' => $credentials['email']]);
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function getUser(Request $request)
    {
        return response()->json($request->user());
    }
}
