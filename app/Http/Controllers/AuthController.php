<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('user_id')) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        if ($user->status !== 'active') {
            return back()->withErrors(['email' => 'Your account is inactive. Please contact the administrator.'])->withInput();
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
            'password' => 'required|min:8|confirmed',
            'college_id' => 'required|exists:colleges,id',
            'student_id' => 'nullable|string|max:50',
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

        session([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'user_college_id' => $user->college_id,
        ]);

        return redirect()->route('dashboard')->with('success', 'Welcome to the Research Archive!');
    }

    public function logout()
    {
        session()->flush();
        return redirect()->route('login');
    }
}
