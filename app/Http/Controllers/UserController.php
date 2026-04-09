<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    private function requireRole(array $roles)
    {
        if (!session('user_id')) return redirect()->route('login');
        if (!in_array(session('user_role'), $roles)) return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        return null;
    }

    public function index(Request $request)
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) return $r;

        $role = session('user_role');
        $collegeId = session('user_college_id');

        $query = User::with('college');

        if ($role === 'admin') {
            $query->where('college_id', $collegeId)->where('role', 'student');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
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

    public function create()
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) return $r;
        $colleges = College::where('active', true)->get();
        return view('users.create', compact('colleges'));
    }

    public function store(Request $request)
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) return $r;

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:super_admin,admin,student',
            'college_id' => 'required|exists:colleges,id',
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
        if ($r = $this->requireRole(['super_admin', 'admin'])) return $r;
        $user = User::findOrFail($id);
        $colleges = College::where('active', true)->get();
        return view('users.edit', compact('user', 'colleges'));
    }

    public function update(Request $request, $id)
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) return $r;
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:super_admin,admin,student',
            'college_id' => 'required|exists:colleges,id',
            'student_id' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
            'password' => 'nullable|min:8|confirmed',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'college_id' => $validated['college_id'],
            'student_id' => $validated['student_id'] ?? null,
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy($id)
    {
        if ($r = $this->requireRole(['super_admin', 'admin'])) return $r;
        $user = User::findOrFail($id);
        if ($user->id === session('user_id')) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
