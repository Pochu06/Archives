<?php

namespace App\Http\Controllers;

use App\Models\Research;
use App\Models\College;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResearchController extends Controller
{
    private function authCheck()
    {
        if (!session('user_id')) {
            return redirect()->route('login');
        }
        return null;
    }

    public function index(Request $request)
    {
        if ($r = $this->authCheck()) return $r;

        $role = session('user_role');
        $collegeId = session('user_college_id');

        $query = Research::with(['user', 'college', 'category']);

        if ($role === 'admin') {
            $query->where('college_id', $collegeId);
        } elseif ($role === 'student') {
            $query->where('status', 'approved');
        } elseif ($role === 'adviser') {
            $user = User::find(session('user_id'));
            $studentIds = $user->students()->pluck('users.id');
            $query->where(function($q) use ($studentIds) {
                $q->whereIn('user_id', $studentIds)->orWhere('status', 'approved');
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                  ->orWhere('abstract', 'like', "%$search%")
                  ->orWhere('keywords', 'like', "%$search%");
            });
        }

        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status') && in_array($role, ['super_admin', 'admin', 'adviser'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('year')) {
            $query->where('publication_year', $request->year);
        }

        $research = $query->orderBy('created_at', 'desc')->paginate(12);
        $colleges = College::where('active', true)->get();
        $categories = Category::all();

        return view('research.index', compact('research', 'colleges', 'categories'));
    }

    public function create()
    {
        if ($r = $this->authCheck()) return $r;
        $role = session('user_role');
        if (!in_array($role, ['student', 'adviser', 'admin', 'super_admin'])) {
            return redirect()->route('dashboard');
        }
        $colleges = College::where('active', true)->get();
        $categories = Category::all();
        $advisers = User::where('role', 'adviser')->get();
        return view('research.create', compact('colleges', 'categories', 'advisers'));
    }

    public function store(Request $request)
    {
        if ($r = $this->authCheck()) return $r;

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'abstract' => 'required|string',
            'keywords' => 'required|string|max:500',
            'authors' => 'required|string|max:500',
            'college_id' => 'required|exists:colleges,id',
            'category_id' => 'required|exists:categories,id',
            'publication_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'adviser_id' => 'nullable|exists:users,id',
            'document' => 'nullable|file|mimes:pdf,doc,docx|max:20480',
        ]);

        $filePath = null;
        $fileName = null;
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('research_documents', $fileName, 'public');
        }

        Research::create([
            'title' => $validated['title'],
            'abstract' => $validated['abstract'],
            'keywords' => $validated['keywords'],
            'authors' => $validated['authors'],
            'college_id' => $validated['college_id'],
            'category_id' => $validated['category_id'],
            'publication_year' => $validated['publication_year'],
            'adviser_id' => $validated['adviser_id'] ?? null,
            'user_id' => session('user_id'),
            'status' => 'pending',
            'file_path' => $filePath,
            'file_name' => $fileName,
        ]);

        return redirect()->route('research.index')->with('success', 'Research submitted successfully! Awaiting approval.');
    }

    public function show($id)
    {
        if ($r = $this->authCheck()) return $r;
        $research = Research::with(['user', 'college', 'category', 'adviser'])->findOrFail($id);
        return view('research.show', compact('research'));
    }

    public function edit($id)
    {
        if ($r = $this->authCheck()) return $r;
        $research = Research::findOrFail($id);
        $role = session('user_role');
        $userId = session('user_id');

        if ($role === 'student' && $research->user_id != $userId) {
            return redirect()->route('research.index')->with('error', 'Unauthorized action.');
        }
        if ($role === 'student' && $research->status === 'approved') {
            return redirect()->route('research.index')->with('error', 'Approved research cannot be edited.');
        }

        $colleges = College::where('active', true)->get();
        $categories = Category::all();
        $advisers = User::where('role', 'adviser')->get();
        return view('research.edit', compact('research', 'colleges', 'categories', 'advisers'));
    }

    public function update(Request $request, $id)
    {
        if ($r = $this->authCheck()) return $r;
        $research = Research::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'abstract' => 'required|string',
            'keywords' => 'required|string|max:500',
            'authors' => 'required|string|max:500',
            'college_id' => 'required|exists:colleges,id',
            'category_id' => 'required|exists:categories,id',
            'publication_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'adviser_id' => 'nullable|exists:users,id',
            'document' => 'nullable|file|mimes:pdf,doc,docx|max:20480',
        ]);

        $filePath = $research->file_path;
        $fileName = $research->file_name;
        if ($request->hasFile('document')) {
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }
            $file = $request->file('document');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('research_documents', $fileName, 'public');
        }

        $research->update([
            'title' => $validated['title'],
            'abstract' => $validated['abstract'],
            'keywords' => $validated['keywords'],
            'authors' => $validated['authors'],
            'college_id' => $validated['college_id'],
            'category_id' => $validated['category_id'],
            'publication_year' => $validated['publication_year'],
            'adviser_id' => $validated['adviser_id'] ?? null,
            'status' => 'pending',
            'file_path' => $filePath,
            'file_name' => $fileName,
        ]);

        return redirect()->route('research.show', $id)->with('success', 'Research updated successfully!');
    }

    public function destroy($id)
    {
        if ($r = $this->authCheck()) return $r;
        $research = Research::findOrFail($id);
        $role = session('user_role');

        if (!in_array($role, ['super_admin', 'admin'])) {
            if ($research->user_id != session('user_id')) {
                return redirect()->route('research.index')->with('error', 'Unauthorized.');
            }
        }

        if ($research->file_path) {
            Storage::disk('public')->delete($research->file_path);
        }

        $research->delete();
        return redirect()->route('research.index')->with('success', 'Research deleted successfully.');
    }

    public function approve($id)
    {
        if ($r = $this->authCheck()) return $r;
        $role = session('user_role');
        if (!in_array($role, ['super_admin', 'admin', 'adviser'])) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        $research = Research::findOrFail($id);
        $research->update(['status' => 'approved', 'approved_by' => session('user_id'), 'approved_at' => now()]);
        return redirect()->back()->with('success', 'Research approved successfully!');
    }

    public function reject(Request $request, $id)
    {
        if ($r = $this->authCheck()) return $r;
        $role = session('user_role');
        if (!in_array($role, ['super_admin', 'admin', 'adviser'])) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        $research = Research::findOrFail($id);
        $research->update(['status' => 'rejected', 'rejection_reason' => $request->reason]);
        return redirect()->back()->with('success', 'Research rejected.');
    }

    public function download($id)
    {
        if ($r = $this->authCheck()) return $r;
        $research = Research::findOrFail($id);
        if (!$research->file_path) {
            return redirect()->back()->with('error', 'No document attached.');
        }
        return Storage::disk('public')->download($research->file_path, $research->file_name);
    }
}
