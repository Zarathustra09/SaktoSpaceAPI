<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'fcm_token' => 'nullable|string',
            'device_type' => 'nullable|string|in:android,ios,web',
            'timestamp' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        \Log::info('Login attempt', ['email' => $credentials['email']]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;

            if ($request->fcm_token) {
                $timestamp = $request->timestamp ? Carbon::parse($request->timestamp) : now();
                $user->addDeviceToken(
                    $request->fcm_token,
                    $request->device_type,
                    $timestamp
                );
                \Log::info('FCM token registered on login', [
                    'user_id' => $user->id,
                    'device_type' => $request->device_type
                ]);
            }

            \Log::info('Successful login', ['user_id' => $user->id]);
            return response()->json([
                'token' => $token,
                'user_id' => $user->id,
                'user' => $user
            ], 200);
        }

        \Log::warning('Unauthorized login attempt', ['email' => $credentials['email']]);
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'fcm_token' => 'nullable|string',
            'device_type' => 'nullable|string|in:android,ios,web',
            'timestamp' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Hash::make($request->password),
        ]);

        $token = $user->createToken('authToken')->plainTextToken;

        if ($request->fcm_token) {
            $timestamp = $request->timestamp ? Carbon::parse($request->timestamp) : now();
            $user->addDeviceToken(
                $request->fcm_token,
                $request->device_type,
                $timestamp
            );
            \Log::info('FCM token registered on registration', [
                'user_id' => $user->id,
                'device_type' => $request->device_type
            ]);
        }

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user
        ], 201);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Already logged out'], 200);
        }

        if ($request->fcm_token) {
            $user->removeDeviceToken($request->fcm_token);
        }

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function getUser(Request $request)
    {
        return response()->json($request->user());
    }
}
