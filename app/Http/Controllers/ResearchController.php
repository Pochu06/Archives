<?php

namespace App\Http\Controllers;

use App\Models\Research;
use App\Models\College;
use App\Models\Category;
use App\Models\User;
use App\Services\RelatedResearchService;
use App\Services\ResearchSummaryService;
use App\Services\TopicSuggestionService;
use App\Models\ResearchDraft;
use Illuminate\Http\Request;
use App\Models\DownloadRequest;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ResearchController extends Controller
{
    private function canDownloadResearch(Research $research): bool
    {
        $userId = session('user_id');
        $role = session('user_role');
        $collegeId = session('user_college_id');

        if (! $userId) {
            return false;
        }

        if ($role === 'super_admin' || ($role === 'admin' && ! $collegeId)) {
            return true;
        }

        return DownloadRequest::where('user_id', $userId)
            ->where('research_id', $research->id)
            ->where('status', 'approved')
            ->exists();
    }

    private function buildResearchPdf(Research $research)
    {
        $pdf = Pdf::loadView('research.pdf', compact('research'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf;
    }

    private function buildAiInsightViewData(
        Research $research,
        ResearchSummaryService $researchSummaryService,
        RelatedResearchService $relatedResearchService
    ): array {
        $aiSummary = $researchSummaryService->generateForResearch($research);
        $relatedResearch = $relatedResearchService->generateForResearch($research);

        $researchSummaryService->queueForResearch($research);
        $relatedResearchService->queueForResearch($research);

        return compact('aiSummary', 'relatedResearch');
    }

    private function normalizedTitle(?string $title): string
    {
        return trim((string) $title);
    }

    private function combinedResultsAndDiscussion(?string $results, ?string $discussion): string
    {
        $parts = array_filter([
            trim((string) $results),
            trim((string) $discussion),
        ], fn (?string $value) => $value !== null && $value !== '');

        return implode("\n\n", array_unique($parts));
    }

    private function buildShowViewData(Research $research): array
    {
        $userId = session('user_id');
        $role = session('user_role');
        $collegeId = session('user_college_id');

        $downloadRequest = null;
        $canDownload = false;

        if ($userId) {
            $downloadRequest = DownloadRequest::where('user_id', $userId)
                ->where('research_id', $research->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $canDownload = $this->canDownloadResearch($research);
        }

        return compact('research', 'downloadRequest', 'canDownload');
    }

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

        if ($role === 'student') {
            $query->approved();
        } elseif ($role === 'admin' && $collegeId) {
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
        $query = Research::with(['user', 'college', 'category'])->approved();

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

    public function topicSuggestions(Request $request, TopicSuggestionService $topicSuggestionService)
    {
        $colleges = College::where('active', true)->get();
        $categories = Category::all();
        $mode = $request->get('mode', 'fast');
        $suggestions = [
            'items' => [],
            'source' => 'none',
            'references' => collect(),
        ];

        if ($request->filled('interest') || $request->filled('category_id') || $request->filled('college_id')) {
            $validated = $request->validate([
                'interest' => 'nullable|string|max:500',
                'category_id' => 'nullable|exists:categories,id',
                'college_id' => 'nullable|exists:colleges,id',
                'mode' => 'nullable|in:fast,ai',
            ]);

            $suggestions = $topicSuggestionService->generate($validated, ($mode === 'ai'));
        }

        return view('research.topic-suggestions', compact('colleges', 'categories', 'suggestions', 'mode'));
    }

    public function authorProfile(Request $request, $id)
    {
        $author = User::with('college')->findOrFail($id);

        $baseQuery = Research::with(['user', 'college', 'category'])
            ->where('user_id', $author->id);

        $role = session('user_role');
        $viewerId = (int) session('user_id');
        $viewerCollegeId = session('user_college_id');

        if ($role === 'super_admin' || ($role === 'admin' && ! $viewerCollegeId)) {
            // RDE users can view all author submissions.
        } elseif ($role === 'admin' && $viewerCollegeId) {
            $baseQuery->where(function ($query) use ($viewerCollegeId) {
                $query->where('college_id', $viewerCollegeId)
                    ->orWhere('status', Research::STATUS_APPROVED);
            });
        } elseif ($viewerId === (int) $author->id && $viewerId !== 0) {
            // Authors can view all of their own submissions.
        } else {
            $baseQuery->approved();
        }

        $visibleResearchCount = (clone $baseQuery)->count();
        $categories = Category::whereIn(
            'id',
            (clone $baseQuery)->select('category_id')->whereNotNull('category_id')->distinct()
        )
            ->orderBy('name')
            ->get();

        $query = clone $baseQuery;

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                    ->orWhere('abstract', 'like', "%$search%")
                    ->orWhere('keywords', 'like', "%$search%")
                    ->orWhere('authors', 'like', "%$search%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('year')) {
            $query->where('publication_year', $request->year);
        }

        $research = $query
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        return view('research.author', compact('author', 'research', 'categories', 'visibleResearchCount'));
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
            'results', 'references', 'conclusion',
            'recommendations', 'keywords', 'authors',
            'college_id', 'category_id', 'publication_year', 'table_design',
        ]);
        $data['table_design'] = in_array($data['table_design'] ?? null, ['classic', 'striped', 'minimal'], true)
            ? $data['table_design']
            : 'classic';
        $data['discussion'] = null;
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

        $request->merge([
            'title' => $this->normalizedTitle($request->input('title')),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500', Rule::unique('research', 'title')],
            'abstract' => 'required|string',
            'introduction' => 'required|string',
            'methodology' => 'required|string',
            'results' => 'required|string',
            'references' => 'required|string',
            'conclusion' => 'required|string',
            'recommendations' => 'required|string',
            'keywords' => 'required|string|max:500',
            'authors' => 'required|string|max:500',
            'college_id' => 'required|exists:colleges,id',
            'category_id' => 'required|exists:categories,id',
            'publication_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'table_design' => 'nullable|in:classic,striped,minimal',
        ], [
            'title.unique' => 'A research paper with this title has already been submitted.',
        ]);

        $validated['discussion'] = null;
        $validated['table_design'] = $validated['table_design'] ?? 'classic';

        $validated['user_id'] = session('user_id');

        if (in_array(session('user_role'), ['super_admin', 'admin'])) {
            $validated['status'] = Research::STATUS_APPROVED;
            $validated['approved_by'] = session('user_id');
            $validated['approved_at'] = now();
        } else {
            $validated['status'] = Research::STATUS_PENDING_COLLEGE;
        }

        Research::create($validated);

        $message = $validated['status'] === Research::STATUS_APPROVED
            ? 'Research paper archived successfully!'
            : 'Research paper submitted for college approval.';

        $redirectRoute = session('user_role') === 'student' ? 'submissions.index' : 'research.index';

        return redirect()->route($redirectRoute)->with('success', $message);
    }

    public function show($id, ResearchSummaryService $researchSummaryService, RelatedResearchService $relatedResearchService)
    {
        $research = Research::with(['user', 'college', 'category'])->findOrFail($id);

        if (!session('user_id')) {
            if ($research->status !== Research::STATUS_APPROVED) {
                return redirect()->route('login');
            }

            return redirect()->route('research.public-show', $research->id);
        }

        if (!in_array(session('user_role'), ['super_admin', 'admin'])
            && $research->status !== Research::STATUS_APPROVED
            && $research->user_id !== session('user_id')) {
            return redirect()->route('research.index')->with('error', 'You are not allowed to view this submission yet.');
        }

        if (session('user_role') === 'admin' && session('user_college_id') && $research->college_id !== session('user_college_id') && $research->status !== Research::STATUS_APPROVED) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }

        extract($this->buildShowViewData($research));
        extract($this->buildAiInsightViewData($research, $researchSummaryService, $relatedResearchService));

        return view('research.show', compact('research', 'downloadRequest', 'canDownload', 'aiSummary', 'relatedResearch'));
    }

    public function publicShow($id, ResearchSummaryService $researchSummaryService, RelatedResearchService $relatedResearchService)
    {
        $research = Research::with(['user', 'college', 'category'])->approved()->findOrFail($id);

        extract($this->buildShowViewData($research));
        extract($this->buildAiInsightViewData($research, $researchSummaryService, $relatedResearchService));

        return view('research.show', compact('research', 'downloadRequest', 'canDownload', 'aiSummary', 'relatedResearch'));
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
        $role = session('user_role');
        $userId = session('user_id');

        if (!in_array($role, ['super_admin', 'admin']) && $research->user_id != $userId) {
            return redirect()->route('research.index')->with('error', 'Unauthorized action.');
        }

        $request->merge([
            'title' => $this->normalizedTitle($request->input('title')),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500', Rule::unique('research', 'title')->ignore($research->id)],
            'abstract' => 'required|string',
            'introduction' => 'required|string',
            'methodology' => 'required|string',
            'results' => 'required|string',
            'references' => 'required|string',
            'conclusion' => 'required|string',
            'recommendations' => 'required|string',
            'keywords' => 'required|string|max:500',
            'authors' => 'required|string|max:500',
            'college_id' => 'required|exists:colleges,id',
            'category_id' => 'required|exists:categories,id',
            'publication_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'table_design' => 'nullable|in:classic,striped,minimal',
        ], [
            'title.unique' => 'A research paper with this title already exists.',
        ]);

        $validated['discussion'] = null;
        $validated['table_design'] = $validated['table_design'] ?? 'classic';

        if ($role === 'student') {
            if ($research->status === Research::STATUS_REVISION_COLLEGE) {
                $validated['status'] = Research::STATUS_PENDING_COLLEGE;
                $validated['revision_notes'] = null;
                $validated['revision_fields'] = null;
                $validated['revision_field_notes'] = null;
                $validated['rejection_reason'] = null;
                $validated['approved_by'] = null;
                $validated['approved_at'] = null;
            } elseif ($research->status === Research::STATUS_REVISION_RDE) {
                $validated['status'] = Research::STATUS_PENDING_RDE;
                $validated['revision_notes'] = null;
                $validated['revision_fields'] = null;
                $validated['revision_field_notes'] = null;
                $validated['rejection_reason'] = null;
                $validated['approved_by'] = null;
                $validated['approved_at'] = null;
            }
        }

        $research->update($validated);

        $message = 'Research paper updated successfully!';

        if (($validated['status'] ?? null) === Research::STATUS_PENDING_COLLEGE) {
            $message = 'Research paper updated and resubmitted for college approval.';
        } elseif (($validated['status'] ?? null) === Research::STATUS_PENDING_RDE) {
            $message = 'Research paper updated and resubmitted for RDE approval.';
        }

        return redirect()->route('research.show', $id)->with('success', $message);
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

        if (! $this->canDownloadResearch($research)) {
            return redirect()->back()->with('error', 'You need an approved download request to download this paper.');
        }

        $filename = $research->title . '.pdf';

        return $this->buildResearchPdf($research)->download($filename);
    }

    public function preview($id)
    {
        if ($r = $this->authCheck()) return $r;
        $research = Research::with(['user', 'college', 'category'])->findOrFail($id);

        if (! $this->canDownloadResearch($research)) {
            return redirect()->back()->with('error', 'You need an approved download request to preview this PDF.');
        }

        $filename = $research->title . '.pdf';

        return $this->buildResearchPdf($research)->stream($filename);
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
