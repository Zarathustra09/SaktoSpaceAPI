<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::with('payments')->paginate(15);

        return view('users.index', compact('users'));
    }

    /**
     * Display the specified user and their transactions.
     */
    public function show(User $user)
    {
        $user->load('payments');
        $payments = $user->payments()->latest('payment_date')->paginate(10);

        return view('users.show', compact('user', 'payments'));
    }
}
