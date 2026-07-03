<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\EmailService;
use Carbon\Carbon;


class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    /**
     * Handle authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    /**
     * Show the forgot password form.
     */
    public function showForgotPassword()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.forgot-password');
    }

    /**
     * Generate code and email to user.
     */
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $code = rand(100000, 999999);

        // Store hash of code in password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($code),
                'created_at' => Carbon::now()
            ]
        );

        // Send email
        $body = "Hello,\n\nA request has been made to reset the password for your account.\nYour 6-digit verification code is: {$code}\n\nThis code will expire in 15 minutes.\nIf you did not request this reset, you can safely ignore this email.\n";
        
        app(EmailService::class)->sendEmail(
            $request->email,
            'Password Reset Verification Code',
            $body
        );

        return redirect()->route('password.reset', ['email' => $request->email])
            ->with('success', 'A password reset code has been sent to your email.');
    }

    /**
     * Show reset password form.
     */
    public function showResetPassword(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        $email = $request->query('email');
        return view('auth.reset-password', compact('email'));
    }

    /**
     * Handle the password reset.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record) {
            return back()->withErrors(['code' => 'No active password reset request found for this email.'])->withInput();
        }

        // Check expiration (15 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['code' => 'The verification code has expired. Please request a new one.'])->withInput();
        }

        // Verify code
        if (!Hash::check($request->code, $record->token)) {
            return back()->withErrors(['code' => 'The verification code is incorrect.'])->withInput();
        }

        // Reset password
        $user = User::where('email', $request->email)->firstOrFail();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Delete token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('login')->with('success', 'Your password has been successfully reset. You can now login.');
    }
}

