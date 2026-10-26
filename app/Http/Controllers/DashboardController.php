<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\College;
use App\Models\Research;
use App\Models\User;
use App\Services\BulkDownloadService;
use App\Services\DashboardAnalyticsService;
use App\Services\FeatureToggleService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    private function requireSuperAdmin()
    {
        if (! session('user_id')) {
            return redirect()->route('login');
        }

        if (session('user_role') !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        return null;
    }

    public function index(Request $request, DashboardAnalyticsService $analyticsService)
    {
        if (! session('user_id')) {
            return redirect()->route('login');
        }

        $role = session('user_role');
        $userId = session('user_id');
        $collegeId = session('user_college_id');
        $filters = $this->validatedAnalyticsFilters($request);
        $analytics = $analyticsService->forUser((int) $userId, (string) $role, $collegeId ? (int) $collegeId : null, $filters);

        if ($role === 'adviser') {
            return view('dashboards.adviser', compact('analytics'));
        }

        if ($role === 'campus_review_board') {
            $pendingBoardReview = Research::where('status', Research::STATUS_PENDING_CAMPUS_REVIEW)->count();
            $returnedByBoard = Research::where('status', Research::STATUS_REVISION_CAMPUS_REVIEW)->count();
            $approvedByBoard = Research::whereHas('statusEvents', function ($query) {
                $query->where('action', 'approved_by_campus_review_board');
            })->count();
            $rejectedByBoard = Research::where('status', Research::STATUS_REJECTED_CAMPUS_REVIEW)->count();
            $recentBoardResearch = Research::with(['user:id,name', 'college:id,name'])
                ->where('status', Research::STATUS_PENDING_CAMPUS_REVIEW)
                ->latest()
                ->take(8)
                ->get();

            return view('dashboards.campus-review-board', compact(
                'pendingBoardReview',
                'returnedByBoard',
                'approvedByBoard',
                'rejectedByBoard',
                'recentBoardResearch',
                'analytics'
            ));
        }

        if ($role === 'super_admin') {
            $totalResearch = Research::count();
            $pendingResearch = Research::whereIn('status', [
                Research::STATUS_PENDING_COLLEGE,
                Research::STATUS_PENDING_CAMPUS_REVIEW,
                Research::STATUS_PENDING_RDE,
            ])->count();
            $approvedResearch = Research::where('status', Research::STATUS_APPROVED)->count();
            $totalUsers = User::count();
            $totalColleges = College::count();
            $totalCategories = Category::count();
            $recentResearch = Research::with(['user', 'college', 'category'])->orderBy('created_at', 'desc')->take(8)->get();
            $researchByCollege = College::withCount('research')->get();
            $bulkDownloadLimits = app(BulkDownloadService::class)->limits();

            return view('dashboards.super_admin', compact(
                'totalResearch', 'totalUsers', 'totalColleges', 'totalCategories',
                'recentResearch', 'researchByCollege', 'analytics', 'bulkDownloadLimits'
            ));
        }

        if ($role === 'admin') {
            $researchQuery = Research::query();
            $userQuery = User::where('role', 'student');
            $recentQuery = Research::with(['user', 'category']);
            $pendingQuery = Research::query();

            if ($collegeId) {
                $researchQuery->where('college_id', $collegeId);
                $userQuery->where('college_id', $collegeId);
                $recentQuery->where('college_id', $collegeId);
                $pendingQuery->where('college_id', $collegeId)->where('status', Research::STATUS_PENDING_COLLEGE);
            } else {
                $pendingQuery->where('status', Research::STATUS_PENDING_RDE);
            }

            $totalResearch = $researchQuery->count();
            $totalStudents = $userQuery->count();
            $recentResearch = $recentQuery->orderBy('created_at', 'desc')->take(5)->get();
            $pendingResearch = $pendingQuery->count();

            return view('dashboards.admin', compact(
                'totalResearch', 'totalStudents', 'recentResearch', 'pendingResearch', 'analytics'
            ));
        }

        abort_unless(in_array($role, ['student', 'faculty'], true), 403);

        $myResearch = Research::where('user_id', $userId)
            ->approved()
            ->count();
        $recentResearch = Research::with(['category'])
            ->where('user_id', $userId)
            ->approved()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        $browseResearch = Research::with(['user', 'college', 'category'])
            ->approved()
            ->archiveRelevance($collegeId)
            ->take(6)
            ->get();

        return view('dashboards.student', compact(
            'myResearch', 'recentResearch', 'browseResearch', 'analytics'
        ));
    }

    public function export(Request $request, DashboardAnalyticsService $analyticsService): StreamedResponse
    {
        if (! session('user_id')) {
            abort(403);
        }

        $role = (string) session('user_role');
        abort_unless(in_array($role, ['student', 'faculty', 'adviser', 'admin', 'campus_review_board', 'super_admin'], true), 403);
        $filters = $this->validatedAnalyticsFilters($request);
        $query = $analyticsService->exportQuery(
            (int) session('user_id'),
            $role,
            session('user_college_id') ? (int) session('user_college_id') : null,
            $filters
        );
        $includeSubmitter = in_array($role, ['admin', 'campus_review_board', 'super_admin'], true);

        return response()->streamDownload(function () use ($query, $includeSubmitter) {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Title', 'Authors', 'Status', 'College', 'Category', 'Academic Program',
                'Academic Level',
                'Publication Year', 'Submission Date', 'Acceptance Date', 'Publication Date',
                ...($includeSubmitter ? ['Submitter'] : []),
            ]);

            $query->reorder('id')->chunkById(500, function ($papers) use ($output, $includeSubmitter) {
                foreach ($papers as $research) {
                    $row = [
                        $research->title,
                        $research->authors,
                        $research->status_label,
                        $research->college?->name,
                        $research->category?->name,
                        $research->academic_program,
                        $research->academic_level,
                        $research->publication_year,
                        $research->submitted_at?->format('Y-m-d H:i:s'),
                        $research->accepted_at?->format('Y-m-d H:i:s'),
                        $research->published_at?->format('Y-m-d H:i:s'),
                    ];
                    if ($includeSubmitter) {
                        $row[] = $research->user?->name;
                    }

                    fputcsv($output, array_map($this->spreadsheetSafeValue(...), $row));
                }
            }, 'id');

            fclose($output);
        }, 'research-analytics-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function validatedAnalyticsFilters(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'college_id' => ['nullable', 'integer', 'exists:colleges,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'academic_level' => ['nullable', 'in:BS,GS'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.now()->year],
            'status' => ['nullable', 'in:pending_college,pending_campus_review,pending_rde,revision_college,revision_campus_review,revision_rde,approved,rejected_college,rejected_campus_review,rejected_rde'],
        ]);

        if (! empty($validated['date_from']) && ! empty($validated['date_to'])
            && $validated['date_from'] > $validated['date_to']) {
            throw ValidationException::withMessages([
                'date_from' => 'The start date must be before or equal to the end date.',
            ]);
        }

        return array_filter($validated, static fn ($value) => $value !== null && $value !== '');
    }

    private function spreadsheetSafeValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[\s]*[=+\-@]/u', $value) ? "'".$value : $value;
    }

    public function updateAiFeatures(Request $request, FeatureToggleService $featureToggleService)
    {
        if ($r = $this->requireSuperAdmin()) {
            return $r;
        }

        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $featureToggleService->setAiFeaturesEnabled((bool) $validated['enabled'], (int) session('user_id'));

        return redirect()->route('dashboard')->with('success', 'AI features setting updated.');
    }

    public function updateBulkDownloadLimits(Request $request, BulkDownloadService $bulkDownloadService)
    {
        if ($r = $this->requireSuperAdmin()) {
            return $r;
        }

        $validated = $request->validate([
            'max_files' => ['required', 'integer', 'min:1', 'max:100'],
            'max_size_mb' => ['required', 'integer', 'min:1', 'max:512'],
        ]);
        $bulkDownloadService->saveLimits(
            (int) $validated['max_files'],
            (int) $validated['max_size_mb'],
            (int) session('user_id')
        );

        return redirect()->route('dashboard')->with('success', 'Bulk-download limits updated.');
    }
}
