<?php

namespace App\Http\Controllers;

use App\Models\College;
use Illuminate\Http\Request;

class CollegeController extends Controller
{
    private function requireSuperAdmin()
    {
        if (!session('user_id')) return redirect()->route('login');
        if (session('user_role') !== 'super_admin') return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        return null;
    }

    public function index()
    {
        if ($r = $this->requireSuperAdmin()) return $r;
        $colleges = College::withCount('research', 'users')->orderBy('name')->paginate(10);
        return view('colleges.index', compact('colleges'));
    }

    public function create()
    {
        if ($r = $this->requireSuperAdmin()) return $r;
        return view('colleges.create');
    }

    public function store(Request $request)
    {
        if ($r = $this->requireSuperAdmin()) return $r;
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:colleges,name',
            'code' => 'required|string|max:20|unique:colleges,code',
            'description' => 'nullable|string',
            'dean' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
        ]);
        $validated['active'] = true;
        College::create($validated);
        return redirect()->route('colleges.index')->with('success', 'College created successfully!');
    }

    public function edit($id)
    {
        if ($r = $this->requireSuperAdmin()) return $r;
        $college = College::findOrFail($id);
        return view('colleges.edit', compact('college'));
    }

    public function update(Request $request, $id)
    {
        if ($r = $this->requireSuperAdmin()) return $r;
        $college = College::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:colleges,name,' . $id,
            'code' => 'required|string|max:20|unique:colleges,code,' . $id,
            'description' => 'nullable|string',
            'dean' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'active' => 'boolean',
        ]);
        $validated['active'] = $request->has('active');
        $college->update($validated);
        return redirect()->route('colleges.index')->with('success', 'College updated successfully!');
    }

    public function destroy($id)
    {
        if ($r = $this->requireSuperAdmin()) return $r;
        $college = College::findOrFail($id);
        $college->delete();
        return redirect()->route('colleges.index')->with('success', 'College deleted.');
    }
}
