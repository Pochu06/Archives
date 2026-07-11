<?php

namespace App\Http\Controllers;

use App\Models\Thrust;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThrustController extends Controller
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

        $thrusts = Thrust::orderBy('active', 'desc')->orderBy('name')->paginate(10);

        return view('thrusts.index', compact('thrusts'));
    }

    public function create()
    {
        if ($r = $this->requireSuperAdmin()) return $r;

        return view('thrusts.create');
    }

    public function store(Request $request)
    {
        if ($r = $this->requireSuperAdmin()) return $r;

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:thrusts,name',
            'description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);

        $validated['active'] = $request->boolean('active', true);

        Thrust::create($validated);

        return redirect()->route('thrusts.index')->with('success', 'Thrust created successfully!');
    }

    public function edit($id)
    {
        if ($r = $this->requireSuperAdmin()) return $r;

        $thrust = Thrust::findOrFail($id);

        return view('thrusts.edit', compact('thrust'));
    }

    public function update(Request $request, $id)
    {
        if ($r = $this->requireSuperAdmin()) return $r;

        $thrust = Thrust::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('thrusts', 'name')->ignore($thrust->id)],
            'description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'active' => 'nullable|boolean',
        ]);

        $validated['active'] = $request->boolean('active');

        $thrust->update($validated);

        return redirect()->route('thrusts.index')->with('success', 'Thrust updated successfully!');
    }

    public function toggle($id)
    {
        if ($r = $this->requireSuperAdmin()) return $r;

        $thrust = Thrust::findOrFail($id);
        $thrust->active = ! $thrust->active;
        $thrust->save();

        $status = $thrust->active ? 'activated' : 'deactivated';

        return redirect()->route('thrusts.index')->with('success', "Thrust {$status} successfully!");
    }

    public function destroy($id)
    {
        if ($r = $this->requireSuperAdmin()) return $r;

        $thrust = Thrust::findOrFail($id);
        $thrust->delete();

        return redirect()->route('thrusts.index')->with('success', 'Thrust deleted successfully.');
    }
}