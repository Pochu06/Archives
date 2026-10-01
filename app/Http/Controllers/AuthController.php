<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('user_id')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors(['email' => __($status)])->withInput();
        }

        return back()->with('status', __($status));
    }

    public function showResetPassword(string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => StrongPassword::rules(),
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => null,
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)])->withInput();
        }

        return redirect()->route('login')->with('status', __($status));
    }

    public function login(Request $request, RecaptchaService $recaptcha)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'g-recaptcha-response' => 'required|string',
        ]);

        if (! $recaptcha->verify($request->input('g-recaptcha-response'), $request->ip())) {
            return back()->withErrors(['g-recaptcha-response' => 'Please complete the CAPTCHA challenge.'])->withInput();
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        if ($user->status !== 'active') {
            return back()->withErrors(['email' => 'Your account is inactive. Please contact the administrator.'])->withInput();
        }

        if (! $user->hasVerifiedEmail()) {
            return back()->withErrors(['email' => 'Please verify your email address before signing in.'])
                ->withInput(['email' => $user->email])
                ->with('verification_email', $user->email);
        }

        session([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'user_college_id' => $user->college_id,
        ]);

        return redirect()->route('dashboard');
    }

    public function showRegister()
    {
        if (session('user_id')) {
            return redirect()->route('dashboard');
        }
        $colleges = College::where('active', true)->get();

        return view('auth.register', compact('colleges'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'min:8', 'confirmed', 'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[[:punct:]]).+$/'],
            'college_id' => 'required|exists:colleges,id',
            'student_id' => 'nullable|string|max:50',
        ], [
            'password.regex' => 'The password must contain at least one uppercase letter, one number, and one symbol.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
            'college_id' => $request->college_id,
            'student_id' => $request->student_id,
            'status' => 'active',
        ]);

        $user->sendEmailVerificationNotification();

        return redirect()->route('login')
            ->with('status', 'Account created. Check your email for a verification link before signing in.')
            ->with('verification_email', $user->email);
    }

    public function verifyEmail(int $id, string $hash)
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return redirect()->route('login')->with('status', 'Your email has been verified. You can now sign in.');
    }

    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('status', 'If that address needs verification, a new link has been sent.')
            ->with('verification_email', $request->email);
    }

    public function logout()
    {
        session()->flush();

        return redirect()->route('login');
    }
}
