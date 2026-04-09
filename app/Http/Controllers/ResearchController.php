<?php

namespace App\Http\Controllers;

use App\Models\Research;
use App\Models\College;
use App\Models\Category;
use App\Models\User;
use App\Models\ResearchDraft;
use Illuminate\Http\Request;
use App\Models\DownloadRequest;
use Barryvdh\DomPDF\Facade\Pdf;
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

        $query = Research::with(['user', 'college', 'category']);

        $role = session('user_role');
        $collegeId = session('user_college_id');

        if ($role === 'admin' && $collegeId) {
            $query->where('college_id', $collegeId);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                  ->orWhere('abstract', 'like', "%$search%")
                  ->orWhere('keywords', 'like', "%$search%")
                  ->orWhere('authors', 'like', "%$search%");
            });
        }

        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('year')) {
            $query->where('publication_year', $request->year);
        }

        $research = $query->orderBy('created_at', 'desc')->paginate(12);
        $colleges = College::where('active', true)->get();
        $categories = Category::all();

        return view('research.index', compact('research', 'colleges', 'categories'));
    }

    public function publicIndex(Request $request)
    {
        $query = Research::with(['user', 'college', 'category']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                    ->orWhere('abstract', 'like', "%$search%")
                    ->orWhere('keywords', 'like', "%$search%")
                    ->orWhere('authors', 'like', "%$search%");
            });
        }

        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('year')) {
            $query->where('publication_year', $request->year);
        }

        $research = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();
        $colleges = College::where('active', true)->get();
        $categories = Category::all();

        return view('research.public', compact('research', 'colleges', 'categories'));
    }

    public function create()
    {
        if ($r = $this->authCheck()) return $r;
        $colleges = College::where('active', true)->get();
        $categories = Category::all();
        $draft = ResearchDraft::where('user_id', session('user_id'))->first();
        return view('research.create', compact('colleges', 'categories', 'draft'));
    }

    public function saveDraft(Request $request)
    {
        if (!session('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $userId = session('user_id');
        $data = $request->only([
            'title', 'abstract', 'introduction', 'methodology',
            'results', 'discussion', 'references', 'conclusion',
            'recommendations', 'keywords', 'authors',
            'college_id', 'category_id', 'publication_year',
        ]);
        $data['user_id'] = $userId;
        $data['last_saved_at'] = now();

        ResearchDraft::updateOrCreate(['user_id' => $userId], $data);

        return response()->json(['success' => true, 'message' => 'Draft saved successfully']);
    }

    public function loadDraft()
    {
        if (!session('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $draft = ResearchDraft::where('user_id', session('user_id'))->first();
        if (!$draft) {
            return response()->json(['success' => false, 'message' => 'No draft found'], 404);
        }

        return response()->json([
            'success' => true,
            'draft' => $draft->toArray(),
        ]);
    }

    public function deleteDraft()
    {
        if (!session('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        ResearchDraft::where('user_id', session('user_id'))->delete();
        return response()->json(['success' => true, 'message' => 'Draft deleted']);
    }

    public function tutorial()
    {
        if ($r = $this->authCheck()) return $r;
        return view('research.tutorial');
    }

    public function store(Request $request)
    {
        if ($r = $this->authCheck()) return $r;

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'abstract' => 'required|string',
            'introduction' => 'required|string',
            'methodology' => 'required|string',
            'results' => 'required|string',
            'discussion' => 'required|string',
            'references' => 'required|string',
            'conclusion' => 'required|string',
            'recommendations' => 'required|string',
            'keywords' => 'required|string|max:500',
            'authors' => 'required|string|max:500',
            'college_id' => 'required|exists:colleges,id',
            'category_id' => 'required|exists:categories,id',
            'publication_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);

        $validated['user_id'] = session('user_id');

        Research::create($validated);

        return redirect()->route('research.index')->with('success', 'Research paper archived successfully!');
    }

    public function show($id)
    {
        $research = Research::with(['user', 'college', 'category'])->findOrFail($id);

        $userId = session('user_id');
        $role = session('user_role');
        $collegeId = session('user_college_id');

        $downloadRequest = null;
        $canDownload = false;
        if ($userId) {
            $downloadRequest = DownloadRequest::where('user_id', $userId)
                ->where('research_id', $id)
                ->orderBy('created_at', 'desc')
                ->first();
            $canDownload = ($role === 'super_admin' || ($role === 'admin' && !$collegeId))
                || ($downloadRequest && $downloadRequest->status === 'approved');
        }

        return view('research.show', compact('research', 'downloadRequest', 'canDownload'));
    }

    public function edit($id)
    {
        if ($r = $this->authCheck()) return $r;
        $research = Research::findOrFail($id);
        $role = session('user_role');
        $userId = session('user_id');

        if (!in_array($role, ['super_admin', 'admin']) && $research->user_id != $userId) {
            return redirect()->route('research.index')->with('error', 'Unauthorized action.');
        }

        $colleges = College::where('active', true)->get();
        $categories = Category::all();
        return view('research.edit', compact('research', 'colleges', 'categories'));
    }

    public function update(Request $request, $id)
    {
        if ($r = $this->authCheck()) return $r;
        $research = Research::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'abstract' => 'required|string',
            'introduction' => 'required|string',
            'methodology' => 'required|string',
            'results' => 'required|string',
            'discussion' => 'required|string',
            'references' => 'required|string',
            'conclusion' => 'required|string',
            'recommendations' => 'required|string',
            'keywords' => 'required|string|max:500',
            'authors' => 'required|string|max:500',
            'college_id' => 'required|exists:colleges,id',
            'category_id' => 'required|exists:categories,id',
            'publication_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
        ]);

        $research->update($validated);

        return redirect()->route('research.show', $id)->with('success', 'Research paper updated successfully!');
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

        $research->delete();
        return redirect()->route('research.index')->with('success', 'Research paper deleted successfully.');
    }

    public function download($id)
    {
        if ($r = $this->authCheck()) return $r;
        $research = Research::with(['user', 'college', 'category'])->findOrFail($id);

        $role = session('user_role');
        $collegeId = session('user_college_id');

        // RDE and super_admin can always download
        if (!($role === 'super_admin' || ($role === 'admin' && !$collegeId))) {
            $approved = DownloadRequest::where('user_id', session('user_id'))
                ->where('research_id', $id)
                ->where('status', 'approved')
                ->exists();

            if (!$approved) {
                return redirect()->back()->with('error', 'You need an approved download request to download this paper.');
            }
        }

        $pdf = Pdf::loadView('research.pdf', compact('research'));
        $pdf->setPaper('letter', 'portrait');

        $filename = $research->title . '.pdf';
        return $pdf->download($filename);
    }

    public function uploadImage(Request $request)
    {
        if (!session('user_id')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        $file = $request->file('image');
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        $path = $file->storeAs('research_images', $filename, 'public');

        return response()->json([
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
            'syntax' => '[figure: ' . $filename . ' | Figure X. Description here]',
        ]);
    }

    public function deleteImage(Request $request)
    {
        if (!session('user_id')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'filename' => 'required|string|max:255',
        ]);

        $filename = basename($request->filename);

        if (Storage::disk('public')->exists('research_images/' . $filename)) {
            Storage::disk('public')->delete('research_images/' . $filename);
            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'File not found'], 404);
    }
}
