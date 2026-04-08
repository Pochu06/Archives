<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    private function requireRole()
    {
        if (!session('user_id')) return redirect()->route('login');
        if (!in_array(session('user_role'), ['super_admin', 'admin'])) return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        return null;
    }

    public function index()
    {
        if ($r = $this->requireRole()) return $r;
        $categories = Category::withCount('research')->orderBy('name')->paginate(10);
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        if ($r = $this->requireRole()) return $r;
        return view('categories.create');
    }

    public function store(Request $request)
    {
        if ($r = $this->requireRole()) return $r;
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
        ]);
        Category::create($validated);
        return redirect()->route('categories.index')->with('success', 'Category created!');
    }

    public function edit($id)
    {
        if ($r = $this->requireRole()) return $r;
        $category = Category::findOrFail($id);
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        if ($r = $this->requireRole()) return $r;
        $category = Category::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string',
        ]);
        $category->update($validated);
        return redirect()->route('categories.index')->with('success', 'Category updated!');
    }

    public function destroy($id)
    {
        if ($r = $this->requireRole()) return $r;
        $category = Category::findOrFail($id);
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
