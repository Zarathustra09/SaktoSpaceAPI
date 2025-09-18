<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        Log::info('API: User accessed profile', ['user_id' => $user->id]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->profile_image ? Storage::url($user->profile_image) : null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        Log::info('API: User is updating profile', ['user_id' => $user->id]);

        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:8|confirmed',
            ]);

            $user->name = $request->name;
            $user->email = $request->email;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();
            Log::info('API: User profile updated successfully', ['user_id' => $user->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_image' => $user->profile_image ? Storage::url($user->profile_image) : null,
                    'updated_at' => $user->updated_at,
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function uploadProfileImage(Request $request)
    {
        Log::info('API: Profile image upload initiated', ['user_id' => Auth::id()]);

        try {
            $request->validate([
                'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $user = Auth::user();

            // Delete old image if exists
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $imagePath = $request->file('profile_image')->store('profile_images', 'public');
            Log::info('API: Profile image stored', ['user_id' => $user->id, 'image_path' => $imagePath]);

            $user->profile_image = $imagePath;
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Profile image uploaded successfully',
                'data' => [
                    'profile_image_url' => Storage::url($imagePath)
                ]
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('API: Profile image upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Profile image upload failed'
            ], 500);
        }
    }

    public function resetProfileImage()
    {
        $user = Auth::user();
        Log::info('API: User is resetting profile image', ['user_id' => $user->id]);

        // Delete old image if exists
        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }

        $user->profile_image = null;
        $user->save();

        Log::info('API: Profile image reset successfully', ['user_id' => $user->id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Profile image reset successfully'
        ]);
    }

    public function destroy()
    {
        $user = Auth::user();
        Log::info('API: User is deleting account', ['user_id' => $user->id]);

        try {
            // Delete profile image if exists
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $userId = $user->id;
            $user->delete();

            Log::info('API: User account deleted successfully', ['user_id' => $userId]);

            return response()->json([
                'status' => 'success',
                'message' => 'Account deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('API: Account deletion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Account deletion failed'
            ], 500);
        }
    }
}
