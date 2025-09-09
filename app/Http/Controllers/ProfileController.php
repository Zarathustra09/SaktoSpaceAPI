<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        Log::info('User accessed profile index', ['user_id' => $user->id]);
        return view('profile.index', compact('user'));
    }

   public function update(Request $request)
   {
       $user = Auth::user();
       Log::info('User is updating profile', ['user_id' => $user->id]);

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
       Log::info('User profile updated successfully', ['user_id' => $user->id]);

       return redirect()->route('profile')->with('success', 'Profile updated successfully.');
   }

    public function uploadProfileImage(Request $request)
    {
        Log::info('Profile image upload initiated', ['user_id' => Auth::id()]);

        try {
            $request->validate([
                'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
            Log::info('Profile image validation passed', ['user_id' => Auth::id()]);

            $user = Auth::user();
            Log::info('User retrieved', ['user_id' => $user->id]);

            $imagePath = $request->file('profile_image')->store('profile_images', 'public');
            Log::info('Profile image stored', ['user_id' => $user->id, 'image_path' => $imagePath]);

            $user->profile_image = $imagePath;
            $user->save();
            Log::info('User profile image updated', ['user_id' => $user->id, 'image_path' => $imagePath]);

            return redirect()->route('profile')->with('success', 'Profile image uploaded successfully.');
        } catch (\Exception $e) {
            Log::error('Profile image upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('profile')->with('error', 'Profile image upload failed.');
        }
    }

    public function resetProfileImage()
    {
        $user = Auth::user();
        Log::info('User is resetting profile image', ['user_id' => $user->id]);

        $user->profile_image = null;
        $user->save();

        Log::info('Profile image reset successfully', ['user_id' => $user->id]);

        return redirect()->route('profile')->with('success', 'Profile image reset successfully.');
    }

    public function destroy()
    {
        $user = Auth::user();
        Log::info('User is deleting account', ['user_id' => $user->id]);

        Auth::logout();
        $user->delete();

        Log::info('User account deleted successfully', ['user_id' => $user->id]);

        return redirect('/')->with('success', 'Account deleted successfully.');
    }
}
