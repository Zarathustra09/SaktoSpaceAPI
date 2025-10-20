<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Override resetPassword to avoid authenticating non-admin users.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string  $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        $user->password = Hash::make($password);
        $user->setRememberToken(Str::random(60));
        $user->save();

        // Only authenticate if the user has the Admin role
        if (method_exists($user, 'hasRole') && $user->hasRole('Admin')) {
            $this->guard()->login($user);
        }
    }

    /**
     * Override response after a successful password reset.
     * Admins -> normal redirect (e.g. /home). Non-admins -> /password-updated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $response
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendResetResponse(Request $request, $response)
    {
        $user = User::where('email', $request->email)->first();

        if ($user && method_exists($user, 'hasRole') && $user->hasRole('Admin')) {
            return redirect($this->redirectPath())->with('status', trans($response));
        }

        // Non-admin: do not authenticate, redirect to password-updated page
        return redirect('/password-updated')->with('status', trans($response));
    }
}
