<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserAdminController extends Controller
{
    public function index()
    {
        // Eager load roles and payments for the Users Admin page
        $users = User::with(['roles', 'payments'])->latest()->get();

        return view('users.admins', compact('users'));
    }

    public function promote(Request $request, User $user): RedirectResponse
    {
        if (!$user->hasRole('Admin')) {
            $user->assignRole('Admin');
        }

        return back()->with('success', 'User promoted to Admin.');
    }

    public function demote(Request $request, User $user): RedirectResponse
    {
        // Prevent self-demotion and ensure at least one admin remains
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot demote yourself.');
        }

        if ($user->hasRole('Admin')) {
            $adminCount = User::role('Admin')->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'At least one Admin is required.');
            }
            $user->removeRole('Admin');
        }

        return back()->with('success', 'User demoted from Admin.');
    }
}

