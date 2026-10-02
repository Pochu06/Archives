<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private function requireAuth()
    {
        if (! session('user_id')) {
            return redirect()->route('login');
        }

        return null;
    }

    private function requireRole(array $roles)
    {
        if (! session('user_id')) {
            return redirect()->route('login');
        }
        if (! in_array(session('user_role'), $roles)) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        return null;
    }

    public function editProfile()
    {
        if ($r = $this->requireAuth()) {
            return $r;
        }

        $user = User::with('college')->findOrFail(session('user_id'));

        return view('users.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        if ($r = $this->requireAuth()) {
            return $r;
        }

        $user = User::findOrFail(session('user_id'));

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'student_id' => 'nullable|string|max:50',
            'password' => StrongPassword::optionalRules(),
            'notification_digest_frequency' => 'nullable|in:none,daily,weekly',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'student_id' => $validated['student_id'] ?? null,
            'notification_digest_frequency' => $validated['notification_digest_frequency'] ?? 'none',
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Keep sidebar/header session info in sync after profile updates.
        session([
            'user_name' => $user->name,
            'user_email' => $user->email,
        ]);

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully!');
    }

    public function index(Request $request)
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) {
            return $r;
        }

        $role = session('user_role');
        $collegeId = session('user_college_id');

        $query = User::with('college');

        if ($role === 'admin') {
            $query->where('college_id', $collegeId)->where('role', 'student');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")->orWhere('email', 'like', "%$search%");
            });
        }

        if ($request->filled('role_filter')) {
            $query->where('role', $request->role_filter);
        }

        if ($request->filled('college_filter') && $role === 'super_admin') {
            $query->where('college_id', $request->college_filter);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        $colleges = College::where('active', true)->get();

        return view('users.index', compact('users', 'colleges'));
    }

    public function pendingRegistrations()
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) {
            return $r;
        }

        $query = User::with('college')
            ->where('role', 'student')
            ->where('status', 'pending');

        if (session('user_role') === 'admin' && session('user_college_id')) {
            $query->where('college_id', session('user_college_id'));
        }

        $users = $query->orderBy('created_at')->paginate(15);

        return view('users.pending', compact('users'));
    }

    public function approveRegistration($id)
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) {
            return $r;
        }

        $query = User::whereKey($id)
            ->where('role', 'student')
            ->where('status', 'pending');

        if (session('user_role') === 'admin' && session('user_college_id')) {
            $query->where('college_id', session('user_college_id'));
        }

        if ($query->update(['status' => 'active']) === 0) {
            return redirect()->route('users.pending')
                ->with('error', 'This registration is no longer pending or is outside your college.');
        }

        return redirect()->route('users.pending')->with('success', 'Registration approved. The user can now sign in after verifying their email.');
    }

    public function create()
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) {
            return $r;
        }
        $colleges = College::where('active', true)->get();

        return view('users.create', compact('colleges'));
    }

    public function store(Request $request)
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) {
            return $r;
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => StrongPassword::rules(),
            'role' => 'required|in:super_admin,admin,student',
            'college_id' => 'nullable|exists:colleges,id',
            'student_id' => 'nullable|string|max:50',
        ];

        if (session('user_role') === 'admin') {
            $rules['role'] = 'required|in:student';
        }

        $validated = $request->validate($rules);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'college_id' => $validated['college_id'],
            'student_id' => $validated['student_id'] ?? null,
            'status' => 'active',
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function edit($id)
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) {
            return $r;
        }
        $user = User::findOrFail($id);
        $colleges = College::where('active', true)->get();

        return view('users.edit', compact('user', 'colleges'));
    }

    public function update(Request $request, $id)
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) {
            return $r;
        }
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'role' => 'required|in:super_admin,admin,student',
            'college_id' => 'nullable|exists:colleges,id',
            'student_id' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
            'password' => StrongPassword::optionalRules(),
        ]);

        if ($user->status === 'pending' && $validated['status'] === 'active') {
            return redirect()->route('users.pending')
                ->with('error', 'Pending registrations must be approved from the pending registrations list.');
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'college_id' => $validated['college_id'],
            'student_id' => $validated['student_id'] ?? null,
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy($id)
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) {
            return $r;
        }
        $user = User::findOrFail($id);
        if ($user->id === session('user_id')) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
