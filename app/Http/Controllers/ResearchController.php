<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\College;
use App\Models\DownloadRequest;
use App\Models\Research;
use App\Models\ResearchDraft;
use App\Models\SavedSearch;
use App\Models\User;
use App\Notifications\InAppAlertNotification;
use App\Services\BulkDownloadLimitExceeded;
use App\Services\BulkDownloadService;
use App\Services\FeatureToggleService;
use App\Services\RelatedResearchService;
use App\Services\ResearchCategoryClassification;
use App\Services\ResearchSummaryService;
use App\Services\ResearchThrustService;
use App\Services\TopicSuggestionService;
use App\Support\ResearchToken;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ResearchController extends Controller
{
    private const SEARCH_CONTEXT_RESEARCH_INDEX = 'research.index';

    private function aiFeaturesEnabled(): bool
    {
        return app(FeatureToggleService::class)->aiFeaturesEnabled();
    }

    private function researchStatusOptions(): array
    {
        return [
            Research::STATUS_PENDING_COLLEGE => 'Pending College Approval',
            Research::STATUS_PENDING_CAMPUS_REVIEW => 'Pending Campus Review Board',
            Research::STATUS_PENDING_RDE => 'Pending RDE Approval',
            Research::STATUS_REVISION_COLLEGE => 'For College Revision',
            Research::STATUS_REVISION_CAMPUS_REVIEW => 'For Campus Review Board Revision',
            Research::STATUS_REVISION_RDE => 'For RDE Revision',
            Research::STATUS_APPROVED => 'Approved',
            Research::STATUS_REJECTED_COLLEGE => 'Rejected by College',
            Research::STATUS_REJECTED_CAMPUS_REVIEW => 'Rejected by Campus Review Board',
            Research::STATUS_REJECTED_RDE => 'Rejected by RDE',
        ];
    }

    private function currentFilterPayload(Request $request): array
    {
        return array_filter([
            'search' => trim((string) $request->input('search', '')),
            'college_id' => $request->input('college_id'),
            'category_id' => $request->input('category_id'),
            'academic_level' => $request->input('academic_level'),
            'year' => $request->input('year'),
            'year_to' => $request->input('year_to'),
            'status' => $request->input('status'),
        ], static fn ($value) => ! is_null($value) && $value !== '');
    }

    private function smartPresetLinks(): array
    {
        $year = (int) date('Y');
        $collegeId = session('user_college_id');

        $presets = [
            [
                'label' => 'This Year',
                'query' => ['year' => $year],
            ],
        ];

        if (session('user_role') === 'student') {
            $presets[] = [
                'label' => 'Approved This Year',
                'query' => ['year' => $year, 'status' => Research::STATUS_APPROVED],
            ];
        } else {
            $presets[] = [
                'label' => 'Needs Revision',
                'query' => ['status' => Research::STATUS_REVISION_RDE],
            ];
            $presets[] = [
                'label' => 'Pending Review',
                'query' => ['status' => Research::STATUS_PENDING_COLLEGE],
            ];
        }

        if ($collegeId) {
            $presets[] = [
                'label' => 'My College',
                'query' => ['college_id' => $collegeId],
            ];
        }

        return $presets;
    }

    private function canDownloadResearch(Research $research): bool
    {
        $userId = session('user_id');

        return $userId && app(BulkDownloadService::class)->canDownload(
            $research,
            (int) $userId,
            (string) session('user_role'),
            session('user_college_id') ? (int) session('user_college_id') : null
        );
    }

    private function buildResearchPdf(Research $research)
    {
        $research->loadMissing([
            'user:id,name',
            'college:id,name',
            'category:id,name',
            'submitter:id,name',
            'collegeReviewer:id,name',
            'campusReviewBoardReviewer:id,name',
            'rdeReviewer:id,name',
            'acceptor:id,name',
            'publisher:id,name',
            'journalIssue',
        ]);
        $statusTimeline = $research->statusEvents()
            ->with('actor:id,name')
            ->reorder()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $pdf = Pdf::loadView('research.pdf', compact('research', 'statusTimeline'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf;
    }

    private function canPrintCertificate(Research $research): bool
    {
        if (! session('user_id')) {
            return false;
        }

        if ($research->status !== Research::STATUS_APPROVED) {
            return false;
        }

        $role = session('user_role');

        return in_array($role, ['super_admin', 'admin'], true)
            || (int) $research->user_id === (int) session('user_id');
    }

    private function buildAiInsightViewData(
        Research $research,
        ResearchSummaryService $researchSummaryService,
        RelatedResearchService $relatedResearchService
    ): array {
        if (! $this->aiFeaturesEnabled()) {
            return [
                'aiSummary' => ['summary' => null, 'pending' => false, 'source' => 'disabled'],
                'relatedResearch' => ['items' => [], 'pending' => false, 'source' => 'disabled'],
            ];
        }

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

    private function normalizeThrustSelections($thrusts): array
    {
        if (is_string($thrusts)) {
            $thrusts = [$thrusts];
        }

        if (! is_array($thrusts)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(static function ($thrust) {
            $thrust = trim((string) $thrust);

            return $thrust === '' ? null : $thrust;
        }, $thrusts))));
    }

    private function resolvePrimaryThrust(array $thrusts, array $suggestion = []): ?string
    {
        if (! empty($thrusts[0])) {
            return $thrusts[0];
        }

        if (! empty($suggestion['thrust'])) {
            return $suggestion['thrust'];
        }

        return null;
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
        $research->loadMissing('journalIssue');
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
        if (! session('user_id')) {
            return redirect()->route('login');
        }

        return null;
    }

    private function logStatusEvent(Research $research, string $action, ?string $fromStatus, ?string $toStatus, ?string $notes = null, array $meta = []): void
    {
        $research->statusEvents()->create([
            'actor_id' => session('user_id'),
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'notes' => $notes,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }

    private function notifyNextReviewStage(Research $research): void
    {
        $roleAndCollege = match ($research->status) {
            Research::STATUS_PENDING_COLLEGE => [['admin'], $research->college_id, 'submissions.college', 'College review is required.'],
            Research::STATUS_PENDING_CAMPUS_REVIEW => [['campus_review_board'], null, 'submissions.campus-review-board', 'Campus Review Board review is required.'],
            Research::STATUS_PENDING_RDE => [['admin', 'super_admin'], null, 'submissions.rde', 'RDE review is required.'],
            default => null,
        };

        if ($roleAndCollege === null) {
            return;
        }

        [$roles, $collegeId, $routeName, $message] = $roleAndCollege;
        $reviewers = User::query()->whereIn('role', $roles)->where('status', 'active');
        if ($collegeId !== null) {
            $reviewers->where('college_id', $collegeId);
        } elseif ($research->status === Research::STATUS_PENDING_RDE) {
            $reviewers->where(function ($query) {
                $query->where('role', 'super_admin')
                    ->orWhere(function ($query) {
                        $query->where('role', 'admin')->whereNull('college_id');
                    });
            });
        }

        $reviewers->each(fn (User $reviewer) => $reviewer->notify(new InAppAlertNotification([
            'type' => 'research_review',
            'title' => 'Research awaiting review',
            'message' => '"'.$research->title.'" is ready. '.$message,
            'action_url' => route($routeName),
            'action_label' => 'Open review queue',
            'icon' => 'fa-clipboard-check',
            'level' => 'info',
            'meta' => ['research_id' => $research->id],
        ])));
    }

    private function similarityScore(?string $left, ?string $right): float
    {
        $left = trim((string) $left);
        $right = trim((string) $right);

        if ($left === '' || $right === '') {
            return 0.0;
        }

        similar_text(mb_strtolower($left), mb_strtolower($right), $percent);

        return round((float) $percent, 1);
    }

    public function index(Request $request)
    {
        if ($r = $this->authCheck()) {
            return $r;
        }

        if ($request->boolean('print') && ! in_array(session('user_role'), ['admin', 'super_admin'], true)) {
            abort(403);
        }

        $query = Research::with(['user', 'college', 'category']);

        $role = session('user_role');
        $userId = session('user_id');
        $collegeId = session('user_college_id');

        if ($role === 'student') {
            $query->approved();
        } elseif ($role === 'admin' && $collegeId) {
            $query->where('college_id', $collegeId);
        }

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
        if (in_array($request->input('academic_level'), ['BS', 'GS'], true)) {
            $query->where('academic_level', $request->input('academic_level'));
        }

        if ($request->filled('year') && $request->filled('year_to')) {
            $query->whereBetween('publication_year', [min((int) $request->year, (int) $request->year_to), max((int) $request->year, (int) $request->year_to)]);
        } elseif ($request->filled('year') || $request->filled('year_to')) {
            $query->where('publication_year', $request->input('year') ?: $request->input('year_to'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $collegePriority = $request->filled('college_id') ? null : $collegeId;
        $query->archiveRelevance($collegePriority, $request->input('sort', 'engagement'));

        $colleges = College::where('active', true)->get();

        if ($request->boolean('print')) {
            $research = $query->with(['category', 'college'])
                ->reorder()
                ->orderByDesc('publication_year')
                ->orderByDesc('created_at')
                ->get();

            return view('research.print', compact('research', 'colleges'));
        }

        $research = $query->paginate(12);
        $categories = Category::all();
        $academicLevels = app(ResearchCategoryClassification::class)->levels();
        $statuses = $this->researchStatusOptions();
        $savedSearches = SavedSearch::where('user_id', session('user_id'))
            ->where('context', self::SEARCH_CONTEXT_RESEARCH_INDEX)
            ->orderBy('name')
            ->get();
        $smartPresets = $this->smartPresetLinks();
        $activeFilters = $this->currentFilterPayload($request);

        return view('research.index', compact(
            'research',
            'colleges',
            'categories',
            'academicLevels',
            'statuses',
            'savedSearches',
            'smartPresets',
            'activeFilters'
        ));
    }

    public function saveSearch(Request $request)
    {
        if ($r = $this->authCheck()) {
            return $r;
        }

        $statuses = array_keys($this->researchStatusOptions());
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'search' => 'nullable|string|max:255',
            'college_id' => 'nullable|exists:colleges,id',
            'category_id' => 'nullable|exists:categories,id',
            'academic_level' => ['nullable', Rule::in(['BS', 'GS'])],
            'year' => 'nullable|integer|min:2000|max:'.date('Y'),
            'year_to' => 'nullable|integer|min:2000|max:'.date('Y'),
            'status' => ['nullable', Rule::in($statuses)],
        ]);

        $filters = array_filter([
            'search' => trim((string) ($validated['search'] ?? '')),
            'college_id' => $validated['college_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'academic_level' => $validated['academic_level'] ?? null,
            'year' => $validated['year'] ?? null,
            'year_to' => $validated['year_to'] ?? null,
            'status' => $validated['status'] ?? null,
        ], static fn ($value) => ! is_null($value) && $value !== '');

        if ($filters === []) {
            return redirect()->route('research.index')->with('error', 'Select at least one filter before saving a search preset.');
        }

        SavedSearch::updateOrCreate(
            [
                'user_id' => session('user_id'),
                'context' => self::SEARCH_CONTEXT_RESEARCH_INDEX,
                'name' => $validated['name'],
            ],
            [
                'filters' => $filters,
            ]
        );

        return redirect()->route('research.index', $filters)->with('success', 'Search preset saved.');
    }

    public function applySavedSearch($id)
    {
        if ($r = $this->authCheck()) {
            return $r;
        }

        $savedSearch = SavedSearch::where('id', $id)
            ->where('user_id', session('user_id'))
            ->where('context', self::SEARCH_CONTEXT_RESEARCH_INDEX)
            ->firstOrFail();

        return redirect()->route('research.index', $savedSearch->filters ?? []);
    }

    public function destroySavedSearch($id)
    {
        if ($r = $this->authCheck()) {
            return $r;
        }

        $savedSearch = SavedSearch::where('id', $id)
            ->where('user_id', session('user_id'))
            ->where('context', self::SEARCH_CONTEXT_RESEARCH_INDEX)
            ->firstOrFail();

        $savedSearch->delete();

        return redirect()->route('research.index')->with('success', 'Saved search deleted.');
    }

    public function landing()
    {
        $baseQuery = fn () => Research::with(['user', 'college', 'category'])->approved();

        $featuredResearch = $baseQuery()
            ->latest('approved_at')
            ->latest('created_at')
            ->take(3)
            ->get();

        $trendingResearch = $baseQuery()
            ->where('publication_year', '>=', now()->year - 2)
            ->latest('publication_year')
            ->latest('created_at')
            ->take(3)
            ->get();

        $topDownloadedResearch = $baseQuery()
            ->withCount(['downloadRequests as approved_downloads_count' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->orderByDesc('approved_downloads_count')
            ->latest('created_at')
            ->take(3)
            ->get();

        return view('welcome', compact('featuredResearch', 'trendingResearch', 'topDownloadedResearch'));
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
        if (in_array($request->input('academic_level'), ['BS', 'GS'], true)) {
            $query->where('academic_level', $request->input('academic_level'));
        }

        if ($request->filled('year')) {
            $query->where('publication_year', $request->year);
        }

        $collegeId = session('user_college_id');
        $collegePriority = $request->filled('college_id') ? null : $collegeId;
        $query->archiveRelevance($collegePriority, $request->input('sort', 'engagement'));

        $research = $query->paginate(12)->withQueryString();
        $colleges = College::where('active', true)->get();
        $categories = Category::all();
        $academicLevels = app(ResearchCategoryClassification::class)->levels();

        return view('research.public', compact('research', 'colleges', 'categories', 'academicLevels'));
    }

    public function topicSuggestions(Request $request, TopicSuggestionService $topicSuggestionService)
    {
        if (! $this->aiFeaturesEnabled()) {
            abort(404);
        }

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
        if ($r = $this->authCheck()) {
            return $r;
        }
        $colleges = College::where('active', true)->get();
        $classification = app(ResearchCategoryClassification::class);
        $categories = $classification->supportedCategories();
        $academicLevels = $classification->levels();
        $draft = ResearchDraft::where('user_id', session('user_id'))->first();
        $thrustOptions = ResearchThrustService::options();
        $resumableUploadId = (string) Str::uuid();

        return view('research.create', compact('colleges', 'categories', 'academicLevels', 'draft', 'thrustOptions', 'resumableUploadId'));
    }

    public function saveDraft(Request $request)
    {
        if (! session('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $thrustService = new ResearchThrustService;
        $userId = session('user_id');
        $classification = app(ResearchCategoryClassification::class);
        $classificationFields = $request->validate([
            'academic_level' => ['nullable', 'string', Rule::in(array_keys($classification->levels()))],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);
        $data = $request->only([
            'title', 'abstract', 'introduction', 'methodology',
            'results', 'references', 'conclusion',
            'recommendations', 'keywords', 'authors',
            'thrust', 'thrusts', 'college_id', 'category_id', 'academic_level', 'academic_program', 'publication_year', 'table_design', 'file_path', 'file_name',
        ]);
        $data['academic_level'] = $classificationFields['academic_level'] ?? null;
        $data['category_id'] = $classificationFields['category_id'] ?? null;
        if ($data['category_id'] !== null) {
            if (! $classification->allowsAnyLevel((int) $data['category_id'])) {
                throw ValidationException::withMessages([
                    'category_id' => 'Select Thesis, Capstone Project, or Dissertation.',
                ]);
            }
            if ($data['academic_level'] !== null && ! $classification->allows($data['academic_level'], (int) $data['category_id'])) {
                throw ValidationException::withMessages([
                    'category_id' => 'The selected research category does not match the academic level.',
                ]);
            }
        }
        $data['table_design'] = in_array($data['table_design'] ?? null, ['classic', 'striped', 'minimal'], true)
            ? $data['table_design']
            : 'classic';
        $suggestion = $thrustService->suggest($data, true);
        $data['thrusts'] = $this->normalizeThrustSelections($data['thrusts'] ?? []);
        if ($data['thrusts'] === []) {
            $data['thrusts'] = $suggestion['thrusts'] ?? [];
        }
        $data['thrust'] = $this->resolvePrimaryThrust($data['thrusts'], $suggestion);
        $data['discussion'] = null;
        $data['user_id'] = $userId;
        $data['last_saved_at'] = now();

        ResearchDraft::updateOrCreate(['user_id' => $userId], $data);

        return response()->json(['success' => true, 'message' => 'Draft saved successfully']);
    }

    public function loadDraft()
    {
        if (! session('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $draft = ResearchDraft::where('user_id', session('user_id'))->first();
        if (! $draft) {
            return response()->json(['success' => false, 'message' => 'No draft found'], 404);
        }

        return response()->json([
            'success' => true,
            'draft' => $draft->toArray(),
        ]);
    }

    public function deleteDraft()
    {
        if (! session('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        ResearchDraft::where('user_id', session('user_id'))->delete();

        return response()->json(['success' => true, 'message' => 'Draft deleted']);
    }

    public function tutorial()
    {
        if ($r = $this->authCheck()) {
            return $r;
        }

        return view('research.tutorial');
    }

    public function store(Request $request)
    {
        if ($r = $this->authCheck()) {
            return $r;
        }

        $thrustService = new ResearchThrustService;

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
            'thrusts' => 'nullable|array',
            'thrusts.*' => ['string', Rule::in(ResearchThrustService::options())],
            'college_id' => 'required|exists:colleges,id',
            'category_id' => 'required|exists:categories,id',
            'academic_level' => ['required', Rule::in(array_keys(app(ResearchCategoryClassification::class)->levels()))],
            'academic_program' => 'nullable|string|max:255',
            'publication_year' => 'required|integer|min:2000|max:'.date('Y'),
            'table_design' => 'nullable|in:classic,striped,minimal',
            'file_path' => 'nullable|string|max:255',
            'file_name' => 'nullable|string|max:255',
        ], [
            'title.unique' => 'A research paper with this title has already been submitted.',
        ]);
        if (! app(ResearchCategoryClassification::class)->allows($validated['academic_level'], (int) $validated['category_id'])) {
            throw ValidationException::withMessages([
                'category_id' => 'The selected research category does not match the academic level.',
            ]);
        }

        $validated['discussion'] = null;
        $validated['table_design'] = $validated['table_design'] ?? 'classic';
        $validated['file_path'] = $validated['file_path'] ?? null;
        $validated['file_name'] = $validated['file_name'] ?? null;
        $validated['thrusts'] = $this->normalizeThrustSelections($request->input('thrusts', []));
        $suggestion = $thrustService->suggest($validated, true);
        if ($validated['thrusts'] === []) {
            $validated['thrusts'] = $suggestion['thrusts'] ?? [];
        }
        $validated['thrust'] = $this->resolvePrimaryThrust($validated['thrusts'], $suggestion);

        $validated['user_id'] = session('user_id');

        if (session('user_role') === 'super_admin'
            || (session('user_role') === 'admin' && ! session('user_college_id'))) {
            $validated['status'] = Research::STATUS_PENDING_CAMPUS_REVIEW;
        } else {
            $validated['status'] = Research::STATUS_PENDING_COLLEGE;
        }

        $createdResearch = Research::create($validated);
        $submittedAt = $createdResearch->created_at ?? now();
        $createdResearch->recordLifecycleStage('submitted', (int) session('user_id'), $submittedAt);
        $this->logStatusEvent($createdResearch, 'submission_created', null, $createdResearch->status);
        $this->notifyNextReviewStage($createdResearch);

        $message = $validated['status'] === Research::STATUS_PENDING_CAMPUS_REVIEW
            ? 'Research paper submitted for Campus Review Board review.'
            : 'Research paper submitted for college approval.';

        $redirectRoute = in_array(session('user_role'), ['student', 'faculty'], true) ? 'submissions.index' : 'research.index';

        return redirect()->route($redirectRoute)->with('success', $message);
    }

    public function show($id, ResearchSummaryService $researchSummaryService, RelatedResearchService $relatedResearchService)
    {
        $research = Research::with(['user', 'college', 'category'])->findOrFail($id);
        $isCampusReviewBoardMember = session('user_role') === 'campus_review_board';

        if (! session('user_id')) {
            if ($research->status !== Research::STATUS_APPROVED) {
                return redirect()->route('login');
            }

            return redirect()->route('research.public-show', ResearchToken::encode($research->id));
        }

        if (! in_array(session('user_role'), ['super_admin', 'admin'])
            && ! $isCampusReviewBoardMember
            && ! (session('user_role') === 'adviser' && (int) $research->adviser_id === (int) session('user_id'))
            && $research->status !== Research::STATUS_APPROVED
            && $research->user_id !== session('user_id')) {
            return redirect()->route('research.index')->with('error', 'You are not allowed to view this submission yet.');
        }

        if (session('user_role') === 'admin' && session('user_college_id') && $research->college_id !== session('user_college_id') && $research->status !== Research::STATUS_APPROVED) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }

        $research->increment('view_count');
        $research->refresh();

        extract($this->buildShowViewData($research));
        extract($this->buildAiInsightViewData($research, $researchSummaryService, $relatedResearchService));
        $research->load([
            'submitter:id,name',
            'collegeReviewer:id,name',
            'campusReviewBoardReviewer:id,name',
            'rdeReviewer:id,name',
            'acceptor:id,name',
            'publisher:id,name',
        ]);
        $statusTimeline = $research->statusEvents()
            ->with('actor:id,name')
            ->reorder()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $showWorkflowTimeline = true;

        return view('research.show', compact('research', 'downloadRequest', 'canDownload', 'aiSummary', 'relatedResearch', 'statusTimeline', 'showWorkflowTimeline'));
    }

    public function publicShow($id, ResearchSummaryService $researchSummaryService, RelatedResearchService $relatedResearchService)
    {
        $researchId = ResearchToken::decode((string) $id);
        abort_if($researchId === null, 404);

        $research = Research::with(['user', 'college', 'category'])->approved()->findOrFail($researchId);

        $research->increment('view_count');
        $research->refresh();

        extract($this->buildShowViewData($research));
        extract($this->buildAiInsightViewData($research, $researchSummaryService, $relatedResearchService));
        $statusTimeline = collect();
        $showWorkflowTimeline = false;

        return view('research.show', compact('research', 'downloadRequest', 'canDownload', 'aiSummary', 'relatedResearch', 'statusTimeline', 'showWorkflowTimeline'));
    }

    public function edit($id)
    {
        if ($r = $this->authCheck()) {
            return $r;
        }
        if (session('user_role') === 'campus_review_board') {
            return redirect()->route('dashboard')->with('error', 'Campus Review Board members can review papers but cannot edit submissions.');
        }
        $research = Research::findOrFail($id);
        $role = session('user_role');
        $userId = session('user_id');

        if (! in_array($role, ['super_admin', 'admin']) && $research->user_id != $userId) {
            return redirect()->route('research.index')->with('error', 'Unauthorized action.');
        }

        if (in_array($role, ['student', 'faculty'], true) && $research->status === Research::STATUS_APPROVED) {
            return redirect()->route('research.show', ResearchToken::encode($research->id))->with('error', 'Approved research can no longer be edited by submitters.');
        }

        $colleges = College::where('active', true)->get();
        $classification = app(ResearchCategoryClassification::class);
        $categories = $classification->supportedCategories();
        $academicLevels = $classification->levels();
        $classificationNeedsReview = ! $classification->allows(
            $research->academic_level,
            $research->category_id ? (int) $research->category_id : null
        );
        $thrustOptions = ResearchThrustService::options();
        $resumableUploadId = (string) Str::uuid();

        return view('research.edit', compact('research', 'colleges', 'categories', 'academicLevels', 'classificationNeedsReview', 'thrustOptions', 'resumableUploadId'));
    }

    public function update(Request $request, $id)
    {
        if ($r = $this->authCheck()) {
            return $r;
        }
        if (session('user_role') === 'campus_review_board') {
            return redirect()->route('dashboard')->with('error', 'Campus Review Board members can review papers but cannot edit submissions.');
        }
        $research = Research::findOrFail($id);
        $previousStatus = $research->status;
        $role = session('user_role');
        $userId = session('user_id');
        $thrustService = new ResearchThrustService;

        if (! in_array($role, ['super_admin', 'admin']) && $research->user_id != $userId) {
            return redirect()->route('research.index')->with('error', 'Unauthorized action.');
        }

        if (in_array($role, ['student', 'faculty'], true) && $research->status === Research::STATUS_APPROVED) {
            return redirect()->route('research.show', ResearchToken::encode($research->id))->with('error', 'Approved research can no longer be edited by submitters.');
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
            'thrusts' => 'nullable|array',
            'thrusts.*' => ['string', Rule::in(ResearchThrustService::options())],
            'college_id' => 'required|exists:colleges,id',
            'category_id' => 'required|exists:categories,id',
            'academic_level' => ['required', Rule::in(array_keys(app(ResearchCategoryClassification::class)->levels()))],
            'academic_program' => 'nullable|string|max:255',
            'publication_year' => 'required|integer|min:2000|max:'.date('Y'),
            'table_design' => 'nullable|in:classic,striped,minimal',
            'file_path' => 'nullable|string|max:255',
            'file_name' => 'nullable|string|max:255',
        ], [
            'title.unique' => 'A research paper with this title already exists.',
        ]);
        if (! app(ResearchCategoryClassification::class)->allows($validated['academic_level'], (int) $validated['category_id'])) {
            throw ValidationException::withMessages([
                'category_id' => 'The selected research category does not match the academic level.',
            ]);
        }

        $validated['discussion'] = null;
        $validated['table_design'] = $validated['table_design'] ?? 'classic';
        if (! array_key_exists('file_path', $validated) || $validated['file_path'] === null) {
            $validated['file_path'] = $research->file_path;
        }
        if (! array_key_exists('file_name', $validated) || $validated['file_name'] === null) {
            $validated['file_name'] = $research->file_name;
        }
        $validated['thrusts'] = $this->normalizeThrustSelections($request->input('thrusts', []));
        $suggestion = $thrustService->suggest($validated, true);
        if ($validated['thrusts'] === []) {
            $validated['thrusts'] = $suggestion['thrusts'] ?? [];
        }
        $validated['thrust'] = $this->resolvePrimaryThrust($validated['thrusts'], $suggestion);

        if (in_array($role, ['student', 'faculty'], true)) {
            if ($research->status === Research::STATUS_REVISION_COLLEGE) {
                $validated['status'] = Research::STATUS_PENDING_COLLEGE;
                $validated['revision_notes'] = null;
                $validated['revision_fields'] = null;
                $validated['revision_field_notes'] = null;
                $validated['rejection_reason'] = null;
            } elseif ($research->status === Research::STATUS_REVISION_RDE) {
                $validated['status'] = Research::STATUS_PENDING_RDE;
                $validated['revision_notes'] = null;
                $validated['revision_fields'] = null;
                $validated['revision_field_notes'] = null;
                $validated['rejection_reason'] = null;
            } elseif ($research->status === Research::STATUS_REVISION_CAMPUS_REVIEW) {
                $validated['status'] = Research::STATUS_PENDING_CAMPUS_REVIEW;
                $validated['revision_notes'] = null;
                $validated['revision_fields'] = null;
                $validated['revision_field_notes'] = null;
                $validated['rejection_reason'] = null;
            }
        }

        $research->update($validated);

        if ($research->wasChanged(['academic_level', 'category_id'])) {
            $this->logStatusEvent(
                $research,
                'category_classification_updated',
                $previousStatus,
                $research->status,
                'Research academic level or category was updated.',
                [
                    'academic_level' => $research->academic_level,
                    'category_id' => $research->category_id,
                ]
            );
        }

        if ($previousStatus !== $research->status) {
            $this->logStatusEvent(
                $research,
                'submission_updated_status_transition',
                $previousStatus,
                $research->status
            );
            $this->notifyNextReviewStage($research);
        }

        $message = 'Research paper updated successfully!';

        if (($validated['status'] ?? null) === Research::STATUS_PENDING_COLLEGE) {
            $message = 'Research paper updated and resubmitted for college approval.';
        } elseif (($validated['status'] ?? null) === Research::STATUS_PENDING_RDE) {
            $message = 'Research paper updated and resubmitted for RDE approval.';
        } elseif (($validated['status'] ?? null) === Research::STATUS_PENDING_CAMPUS_REVIEW) {
            $message = 'Research paper updated and resubmitted for Campus Review Board review.';
        }

        return redirect()->route('research.show', ResearchToken::encode($id))->with('success', $message);
    }

    public function thrustSuggestion(Request $request)
    {
        if (! session('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (! $this->aiFeaturesEnabled()) {
            return response()->json([
                'success' => true,
                'suggestion' => [
                    'thrust' => null,
                    'thrusts' => [],
                    'reason' => 'AI thrust suggestion is currently disabled by the super admin. Select one or more thrusts manually.',
                    'source' => 'disabled',
                    'confidence' => 0,
                ],
                'options' => ResearchThrustService::options(),
            ]);
        }

        $thrustService = new ResearchThrustService;

        $validated = $request->validate([
            'title' => 'nullable|string|max:500',
            'keywords' => 'nullable|string|max:500',
            'abstract' => 'nullable|string',
            'introduction' => 'nullable|string',
            'methodology' => 'nullable|string',
            'results' => 'nullable|string',
            'conclusion' => 'nullable|string',
            'recommendations' => 'nullable|string',
        ]);

        return response()->json([
            'success' => true,
            'suggestion' => $thrustService->suggest($validated, true),
            'options' => ResearchThrustService::options(),
        ]);
    }

    public function destroy($id)
    {
        if ($r = $this->authCheck()) {
            return $r;
        }
        $research = Research::findOrFail($id);
        $role = session('user_role');

        if (! in_array($role, ['super_admin', 'admin'])) {
            if ($research->user_id != session('user_id')) {
                return redirect()->route('research.index')->with('error', 'Unauthorized.');
            }
        }

        $this->logStatusEvent($research, 'submission_deleted', $research->status, null);
        $research->delete();

        return redirect()->route('research.index')->with('success', 'Research paper deleted successfully.');
    }

    public function checkDuplicates(Request $request)
    {
        if ($r = $this->authCheck()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'title' => 'required|string|min:3|max:500',
            'abstract' => 'nullable|string',
            'ignore_id' => 'nullable|integer|exists:research,id',
        ]);

        $candidates = Research::with(['college'])
            ->when(! empty($validated['ignore_id']), function ($query) use ($validated) {
                $query->where('id', '!=', $validated['ignore_id']);
            })
            ->latest()
            ->limit(80)
            ->get();

        $results = $candidates->map(function (Research $candidate) use ($validated) {
            $titleScore = $this->similarityScore($validated['title'], $candidate->title);
            $abstractScore = $this->similarityScore(
                Str::limit((string) ($validated['abstract'] ?? ''), 1500, ''),
                Str::limit((string) $candidate->abstract, 1500, '')
            );
            $combinedScore = round(($titleScore * 0.65) + ($abstractScore * 0.35), 1);

            return [
                'id' => $candidate->id,
                'title' => $candidate->title,
                'college' => $candidate->college?->code,
                'year' => $candidate->publication_year,
                'status' => $candidate->status,
                'score' => $combinedScore,
                'title_score' => $titleScore,
                'abstract_score' => $abstractScore,
                'url' => route('research.show', ResearchToken::encode($candidate->id)),
            ];
        })
            ->filter(fn (array $item) => $item['score'] >= 55.0)
            ->sortByDesc('score')
            ->take(5)
            ->values();

        return response()->json([
            'success' => true,
            'has_duplicates' => $results->isNotEmpty(),
            'matches' => $results,
        ]);
    }

    public function uploadFileChunk(Request $request)
    {
        if (! session('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'upload_id' => 'required|string|max:100',
            'file_name' => 'required|string|max:255',
            'chunk_index' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1|max:2000',
            'chunk' => 'required|file|max:10240',
        ]);

        $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $validated['upload_id']);

        if ($uploadId === '') {
            return response()->json(['success' => false, 'message' => 'Invalid upload id'], 422);
        }

        $basePath = 'research_upload_chunks/'.session('user_id').'/'.$uploadId;
        Storage::disk('local')->putFileAs($basePath, $request->file('chunk'), 'part_'.$validated['chunk_index']);

        $uploadedChunks = collect(Storage::disk('local')->files($basePath))
            ->filter(fn (string $path) => str_contains($path, '/part_'))
            ->count();

        return response()->json([
            'success' => true,
            'uploaded_chunks' => $uploadedChunks,
            'total_chunks' => (int) $validated['total_chunks'],
        ]);
    }

    public function uploadFileStatus(Request $request)
    {
        if (! session('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'upload_id' => 'required|string|max:100',
            'total_chunks' => 'required|integer|min:1|max:2000',
        ]);

        $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $validated['upload_id']);
        $basePath = 'research_upload_chunks/'.session('user_id').'/'.$uploadId;

        $uploadedIndexes = collect(Storage::disk('local')->files($basePath))
            ->map(function (string $path) {
                if (preg_match('/part_(\d+)$/', $path, $matches)) {
                    return (int) $matches[1];
                }

                return null;
            })
            ->filter(fn ($value) => $value !== null)
            ->values();

        return response()->json([
            'success' => true,
            'uploaded_indexes' => $uploadedIndexes,
            'uploaded_chunks' => $uploadedIndexes->count(),
            'total_chunks' => (int) $validated['total_chunks'],
        ]);
    }

    public function completeFileUpload(Request $request)
    {
        if (! session('user_id')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'upload_id' => 'required|string|max:100',
            'file_name' => 'required|string|max:255',
            'total_chunks' => 'required|integer|min:1|max:2000',
        ]);

        $uploadId = preg_replace('/[^a-zA-Z0-9_-]/', '', $validated['upload_id']);
        $basePath = 'research_upload_chunks/'.session('user_id').'/'.$uploadId;

        for ($i = 0; $i < (int) $validated['total_chunks']; $i++) {
            if (! Storage::disk('local')->exists($basePath.'/part_'.$i)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing chunk '.$i.'. Resume upload and try again.',
                ], 422);
            }
        }

        $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '', $validated['file_name']);
        $originalName = $originalName !== '' ? $originalName : 'research_source.bin';
        $finalFileName = time().'_'.$originalName;
        $finalPath = 'research_files/'.$finalFileName;
        $absolutePath = storage_path('app/public/'.$finalPath);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0775, true);
        }

        $target = fopen($absolutePath, 'wb');

        if (! $target) {
            return response()->json(['success' => false, 'message' => 'Unable to open destination file.'], 500);
        }

        for ($i = 0; $i < (int) $validated['total_chunks']; $i++) {
            $chunkContent = Storage::disk('local')->get($basePath.'/part_'.$i);
            fwrite($target, $chunkContent);
        }

        fclose($target);
        Storage::disk('local')->deleteDirectory($basePath);

        return response()->json([
            'success' => true,
            'file_path' => $finalPath,
            'file_name' => $finalFileName,
            'url' => Storage::url($finalPath),
        ]);
    }

    public function download($id)
    {
        if ($r = $this->authCheck()) {
            return $r;
        }
        $research = Research::with(['user', 'college', 'category'])->findOrFail($id);

        if (! $this->canDownloadResearch($research)) {
            return redirect()->back()->with('error', 'You need an approved download request to download this paper.');
        }

        $research->increment('download_count');

        $filename = $this->researchPdfFilename($research);

        return $this->buildResearchPdf($research)->download($filename);
    }

    public function bulkDownload(Request $request, BulkDownloadService $bulkDownloadService)
    {
        if ($r = $this->authCheck()) {
            return $r;
        }

        $role = (string) session('user_role');
        abort_unless(in_array($role, ['student', 'faculty', 'adviser', 'admin', 'campus_review_board', 'super_admin'], true), 403);
        $limits = $bulkDownloadService->limits();
        try {
            $validated = $request->validate([
                'research_ids' => ['required', 'array', 'min:1', 'max:'.$limits['max_files']],
                'research_ids.*' => ['required', 'integer', 'distinct'],
            ], [
                'research_ids.required' => 'Select at least one research paper to download.',
                'research_ids.min' => 'Select at least one research paper to download.',
                'research_ids.max' => 'You can include up to '.$limits['max_files'].' papers in one ZIP archive.',
                'research_ids.*.distinct' => 'A research paper can only be included once.',
            ]);
        } catch (ValidationException $exception) {
            $requestedCount = is_array($request->input('research_ids')) ? count($request->input('research_ids')) : 0;
            $bulkDownloadService->recordActivity(
                $request,
                'blocked',
                $requestedCount,
                0,
                $requestedCount,
                'Bulk-download request validation failed.'
            );

            throw $exception;
        }
        $requestedCount = count($validated['research_ids']);
        $researchPapers = Research::with(['user', 'college', 'category'])
            ->whereIn('id', $validated['research_ids'])
            ->get();
        $userId = (int) session('user_id');
        $collegeId = session('user_college_id') ? (int) session('user_college_id') : null;
        $eligiblePapers = $bulkDownloadService->filterDownloadable($researchPapers, $userId, $role, $collegeId);
        $excludedCount = $requestedCount - $eligiblePapers->count();

        if ($eligiblePapers->isEmpty()) {
            $bulkDownloadService->recordActivity(
                $request,
                'blocked',
                $requestedCount,
                0,
                $excludedCount,
                'No selected papers are downloadable by this account.'
            );

            return redirect()->back()->with('error', 'No eligible papers were selected. Check that you own the papers or have an approved download request.');
        }

        try {
            $archivePath = $bulkDownloadService->createArchive(
                $eligiblePapers,
                $limits['max_size_mb'],
                fn (Research $research) => $this->buildResearchPdf($research)->output(),
                $excludedCount
            );
        } catch (BulkDownloadLimitExceeded $exception) {
            $bulkDownloadService->recordActivity(
                $request,
                'blocked',
                $requestedCount,
                0,
                $excludedCount,
                $exception->getMessage()
            );

            return redirect()->back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            Log::error('Bulk research download failed.', [
                'user_id' => $userId,
                'requested_count' => $requestedCount,
                'included_count' => $eligiblePapers->count(),
                'exception' => $exception,
            ]);
            $bulkDownloadService->recordActivity(
                $request,
                'failed',
                $requestedCount,
                0,
                $excludedCount,
                'Archive generation failed.'
            );

            throw $exception;
        }

        $eligiblePapers->each->increment('download_count');
        $bulkDownloadService->recordActivity(
            $request,
            'completed',
            $requestedCount,
            $eligiblePapers->count(),
            $excludedCount
        );

        return response()->download(
            $archivePath,
            'research-papers-'.now()->format('Ymd-His').'.zip',
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }

    private function researchPdfFilename(Research $research): string
    {
        return (Str::limit(Str::slug($research->title) ?: 'research-paper', 120, '') ?: 'research-paper').'.pdf';
    }

    public function preview($id)
    {
        if ($r = $this->authCheck()) {
            return $r;
        }
        $research = Research::with(['user', 'college', 'category'])->findOrFail($id);

        if (! $this->canDownloadResearch($research)) {
            return redirect()->back()->with('error', 'You need an approved download request to preview this PDF.');
        }

        $filename = $this->researchPdfFilename($research);

        return $this->buildResearchPdf($research)->stream($filename);
    }

    public function certificate(Request $request, $id)
    {
        if ($r = $this->authCheck()) {
            return $r;
        }

        $research = Research::with(['user', 'college', 'category', 'approver'])->findOrFail($id);

        if (! $this->canPrintCertificate($research)) {
            return redirect()->back()->with('error', 'You are not allowed to print this certificate.');
        }

        $pdf = Pdf::loadView('research.certificate', compact('research'));
        $pdf->setPaper('letter', 'landscape');

        $filename = 'Certificate - '.preg_replace('/[^a-zA-Z0-9 _-]/', '', $research->title).'.pdf';

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    public function verifyCertificate($id)
    {
        $research = Research::with(['user', 'college', 'category', 'approver'])->findOrFail($id);
        $isLegitimate = $research->status === Research::STATUS_APPROVED && $research->approved_at !== null;
        $certificateReference = 'CERT-'.$research->id.'-'.strtoupper(substr(hash('sha256', 'research-certificate:'.$research->id.':'.$research->approved_at), 0, 10));

        return view('research.certificate-verify', compact('research', 'isLegitimate', 'certificateReference'));
    }

    public function uploadImage(Request $request)
    {
        if (! session('user_id')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        $file = $request->file('image');
        $filename = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        $path = $file->storeAs('research_images', $filename, 'public');

        return response()->json([
            'filename' => $filename,
            'path' => $path,
            'url' => Storage::url($path),
            'syntax' => '[figure: '.$filename.' | Figure X. Description here]',
        ]);
    }

    public function deleteImage(Request $request)
    {
        if (! session('user_id')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'filename' => 'required|string|max:255',
        ]);

        $filename = basename($request->filename);

        if (Storage::disk('public')->exists('research_images/'.$filename)) {
            Storage::disk('public')->delete('research_images/'.$filename);

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'File not found'], 404);
    }
}
