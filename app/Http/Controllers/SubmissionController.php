<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Research;
use App\Models\User;
use App\Notifications\InAppAlertNotification;
use App\Services\ResearchCategoryClassification;
use App\Support\ResearchToken;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubmissionController extends Controller
{
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

    private function revisionFieldRules(): array
    {
        return ['required', 'array', 'min:1'];
    }

    private function revisionFieldItemRules(): array
    {
        return ['required', 'string', 'in:'.implode(',', array_keys(Research::revisionFieldOptions()))];
    }

    private function validatedRevisionFieldNotes(Request $request, array $selectedFields): array
    {
        $rawFieldNotes = $request->input('revision_field_notes', []);
        $fieldNotes = [];

        foreach ($selectedFields as $field) {
            $note = trim((string) ($rawFieldNotes[$field] ?? ''));

            if ($note === '') {
                throw ValidationException::withMessages([
                    'revision_field_notes' => 'Please add a revision note for every selected section.',
                ]);
            }

            if (mb_strlen($note) > 1000) {
                throw ValidationException::withMessages([
                    'revision_field_notes' => 'Each revision note must not be greater than 1000 characters.',
                ]);
            }

            $fieldNotes[$field] = $note;
        }

        return $fieldNotes;
    }

    private function requireAuth()
    {
        if (! session('user_id')) {
            return redirect()->route('login');
        }

        return null;
    }

    private function requireStudent()
    {
        if ($r = $this->requireAuth()) {
            return $r;
        }
        if (! in_array(session('user_role'), ['student', 'faculty'], true)) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        return null;
    }

    private function requireCollegeAdmin()
    {
        if ($r = $this->requireAuth()) {
            return $r;
        }
        if (! (session('user_role') === 'admin' && session('user_college_id'))) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        return null;
    }

    private function requireRde()
    {
        if ($r = $this->requireAuth()) {
            return $r;
        }
        if (! (session('user_role') === 'super_admin' || (session('user_role') === 'admin' && ! session('user_college_id')))) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        return null;
    }

    private function requireCampusReviewBoard()
    {
        if ($r = $this->requireAuth()) {
            return $r;
        }
        if (session('user_role') !== 'campus_review_board') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        return null;
    }

    private function notifyResearchOwner(Research $research, array $payload): void
    {
        $recipient = $research->user()->first();

        if (! $recipient) {
            return;
        }

        $recipient->notify(new InAppAlertNotification($payload));
    }

    private function notifyReviewers(array $roles, Research $research, string $title, string $message, string $routeName, string $icon): void
    {
        $actionUrl = route($routeName);

        User::query()
            ->whereIn('role', $roles)
            ->where('status', 'active')
            ->when($roles === ['admin', 'super_admin'], function ($query) {
                $query->where(function ($query) {
                    $query->where('role', 'super_admin')
                        ->orWhere(function ($query) {
                            $query->where('role', 'admin')->whereNull('college_id');
                        });
                });
            })
            ->each(function (User $reviewer) use ($research, $title, $message, $actionUrl, $icon) {
                $reviewer->notify(new InAppAlertNotification([
                    'type' => 'research_review',
                    'title' => $title,
                    'message' => $message,
                    'action_url' => $actionUrl,
                    'action_label' => 'Open review queue',
                    'icon' => $icon,
                    'level' => 'info',
                    'meta' => ['research_id' => $research->id],
                ]));
            });
    }

    private function notifyNextReviewStage(Research $research): void
    {
        if ($research->status === Research::STATUS_PENDING_COLLEGE) {
            User::query()
                ->where('role', 'admin')
                ->where('college_id', $research->college_id)
                ->where('status', 'active')
                ->each(fn (User $reviewer) => $reviewer->notify(new InAppAlertNotification([
                    'type' => 'research_review',
                    'title' => 'Research awaiting college review',
                    'message' => '"'.$research->title.'" is awaiting college review.',
                    'action_url' => route('submissions.college'),
                    'action_label' => 'Open review queue',
                    'icon' => 'fa-building-columns',
                    'level' => 'info',
                ])));
        } elseif ($research->status === Research::STATUS_PENDING_CAMPUS_REVIEW) {
            $this->notifyReviewers(
                ['campus_review_board'],
                $research,
                'Research awaiting Campus Review Board',
                '"'.$research->title.'" is ready for Campus Review Board review.',
                'submissions.campus-review-board',
                'fa-users'
            );
        } elseif ($research->status === Research::STATUS_PENDING_RDE) {
            $this->notifyReviewers(
                ['admin', 'super_admin'],
                $research,
                'Research awaiting RDE review',
                '"'.$research->title.'" passed Campus Review Board and is ready for RDE review.',
                'submissions.rde',
                'fa-stamp'
            );
        }
    }

    public function index(Request $request)
    {
        if ($r = $this->requireStudent()) {
            return $r;
        }

        $research = Research::with(['college', 'category'])
            ->where('user_id', session('user_id'))
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('submissions.index', compact('research'));
    }

    public function categoryReview(ResearchCategoryClassification $classification)
    {
        if ($r = $this->requireAuth()) {
            return $r;
        }
        if (! in_array(session('user_role'), ['admin', 'super_admin'], true)) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        $query = $classification->reviewRequired()
            ->with(['college:id,name,code', 'category:id,name', 'user:id,name'])
            ->latest('updated_at');
        if (session('user_role') === 'admin' && session('user_college_id')) {
            $query->where('college_id', session('user_college_id'));
        }

        $research = $query->paginate(25);
        $categories = $classification->supportedCategories();
        $academicLevels = $classification->levels();

        return view('admin.category-review', compact('research', 'categories', 'academicLevels'));
    }

    public function updateCategoryClassification(
        Request $request,
        int $id,
        ResearchCategoryClassification $classification
    ) {
        if ($r = $this->requireAuth()) {
            return $r;
        }
        if (! in_array(session('user_role'), ['admin', 'super_admin'], true)) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        $research = Research::query()->with('category')->findOrFail($id);
        if (session('user_role') === 'admin' && session('user_college_id')
            && (int) $research->college_id !== (int) session('user_college_id')) {
            return redirect()->route('dashboard')->with('error', 'You can only correct classification for papers from your college.');
        }

        $validated = $request->validate([
            'academic_level' => ['required', 'in:BS,GS'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ]);
        if (! $classification->allows($validated['academic_level'], (int) $validated['category_id'])) {
            throw ValidationException::withMessages([
                'category_id' => 'The selected research category does not match the academic level.',
            ]);
        }

        $previous = [
            'academic_level' => $research->academic_level,
            'category_id' => $research->category_id,
            'category_name' => $research->category?->name,
        ];
        $research->update($validated);
        $research->load('category');
        $this->logStatusEvent(
            $research,
            'category_classification_corrected',
            $research->status,
            $research->status,
            'Authorized classification review and correction.',
            [
                'previous' => $previous,
                'updated' => [
                    'academic_level' => $research->academic_level,
                    'category_id' => $research->category_id,
                    'category_name' => $research->category?->name,
                ],
            ]
        );

        return redirect()->route('submissions.category-review')->with('success', 'Research classification corrected and recorded.');
    }

    public function assignAdviser(Request $request, int $id)
    {
        if (! session('user_id')) {
            return redirect()->route('login');
        }
        $role = session('user_role');
        $collegeId = session('user_college_id');
        if (! in_array($role, ['super_admin', 'admin'], true)) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        $validated = $request->validate([
            'adviser_id' => ['required', 'exists:users,id'],
        ]);
        $research = Research::query()->findOrFail($id);
        if ($role === 'admin' && $collegeId && (int) $research->college_id !== (int) $collegeId) {
            return redirect()->route('dashboard')->with('error', 'You can only assign advisers to papers from your college.');
        }
        if ($research->status === Research::STATUS_APPROVED) {
            return redirect()->back()->with('error', 'Published papers cannot be assigned for adviser review.');
        }

        $adviser = User::query()
            ->whereKey($validated['adviser_id'])
            ->where('role', 'adviser')
            ->where('status', 'active')
            ->where('college_id', $research->college_id)
            ->first();
        if (! $adviser) {
            return redirect()->back()->with('error', 'Select an active adviser from the paper’s college.');
        }

        $previousAdviserId = $research->adviser_id;
        $research->update(['adviser_id' => $adviser->id]);
        $this->logStatusEvent($research, 'adviser_assigned', $research->status, $research->status, null, [
            'previous_adviser_id' => $previousAdviserId,
            'adviser_id' => $adviser->id,
        ]);
        $adviser->notify(new InAppAlertNotification([
            'type' => 'research_review',
            'title' => 'Research assigned for adviser review',
            'message' => '"'.$research->title.'" has been assigned to you for adviser review.',
            'action_url' => route('dashboard'),
            'action_label' => 'Open dashboard',
            'icon' => 'fa-user-check',
            'level' => 'info',
            'meta' => ['research_id' => $research->id],
        ]));

        return redirect()->back()->with('success', 'Adviser assigned for review.');
    }

    public function recordAdviserReview(Request $request, int $id)
    {
        if (! session('user_id')) {
            return redirect()->route('login');
        }
        if (session('user_role') !== 'adviser') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:approve,return'],
            'notes' => ['required', 'string', 'max:1000'],
        ]);
        $research = Research::query()
            ->whereKey($id)
            ->where('adviser_id', session('user_id'))
            ->firstOrFail();
        $action = $validated['decision'] === 'approve' ? 'adviser_review_approved' : 'adviser_review_returned';

        $this->logStatusEvent($research, $action, $research->status, $research->status, $validated['notes']);
        $this->notifyResearchOwner($research, [
            'type' => 'adviser_review',
            'title' => $validated['decision'] === 'approve' ? 'Adviser review completed' : 'Adviser feedback received',
            'message' => 'Your adviser reviewed "'.$research->title.'" and provided feedback.',
            'action_url' => route('research.show', ResearchToken::encode($research->id)),
            'action_label' => 'View Feedback',
            'icon' => $validated['decision'] === 'approve' ? 'fa-circle-check' : 'fa-rotate-left',
            'level' => $validated['decision'] === 'approve' ? 'success' : 'warning',
        ]);

        return redirect()->back()->with('success', 'Adviser review recorded.');
    }

    public function collegeIndex(Request $request)
    {
        if ($r = $this->requireCollegeAdmin()) {
            return $r;
        }

        $research = Research::with(['user', 'college', 'category', 'adviser:id,name'])
            ->where('college_id', session('user_college_id'))
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            }, function ($query) {
                $query->where('status', Research::STATUS_PENDING_COLLEGE);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $pageTitle = 'College Research Submissions';
        $pageSubtitle = 'Review papers submitted to your college before forwarding them to the RDE office';
        $approveRouteName = 'submissions.college-approve';
        $revisionRouteName = 'submissions.college-revision';
        $rejectRouteName = 'submissions.college-reject';
        $defaultPendingStatus = Research::STATUS_PENDING_COLLEGE;
        $showRdeLabels = false;
        $revisionFieldOptions = Research::revisionFieldOptions();
        $categories = app(ResearchCategoryClassification::class)->supportedCategories();
        $colleges = College::where('id', session('user_college_id'))
            ->orderBy('name')
            ->get();
        $advisers = User::query()->where('role', 'adviser')->where('status', 'active')
            ->where('college_id', session('user_college_id'))->orderBy('name')->get(['id', 'name', 'college_id']);

        return view('admin.submissions', compact('research', 'pageTitle', 'pageSubtitle', 'approveRouteName', 'revisionRouteName', 'rejectRouteName', 'defaultPendingStatus', 'showRdeLabels', 'revisionFieldOptions', 'categories', 'colleges', 'advisers'));
    }

    public function rdeIndex(Request $request)
    {
        if ($r = $this->requireRde()) {
            return $r;
        }

        $allowedStatuses = [Research::STATUS_PENDING_RDE, Research::STATUS_REVISION_RDE, Research::STATUS_REJECTED_RDE, Research::STATUS_APPROVED];
        $research = Research::with(['user', 'college', 'category', 'adviser:id,name'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            }, function ($query) {
                $query->where('status', Research::STATUS_PENDING_RDE);
            })
            ->whereIn('status', $allowedStatuses)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $pageTitle = 'RDE Research Approvals';
        $pageSubtitle = 'Review Campus Review Board-approved papers and decide final archive approval';
        $approveRouteName = 'submissions.rde-approve';
        $revisionRouteName = 'submissions.rde-revision';
        $rejectRouteName = 'submissions.rde-reject';
        $defaultPendingStatus = Research::STATUS_PENDING_RDE;
        $showRdeLabels = true;
        $revisionFieldOptions = Research::revisionFieldOptions();
        $categories = app(ResearchCategoryClassification::class)->supportedCategories();
        $colleges = College::query()
            ->orderBy('name')
            ->get();
        $advisers = User::query()->where('role', 'adviser')->where('status', 'active')
            ->orderBy('name')->get(['id', 'name', 'college_id']);

        return view('admin.submissions', compact('research', 'pageTitle', 'pageSubtitle', 'approveRouteName', 'revisionRouteName', 'rejectRouteName', 'defaultPendingStatus', 'showRdeLabels', 'revisionFieldOptions', 'categories', 'colleges', 'advisers'));
    }

    public function campusReviewBoardIndex(Request $request)
    {
        if ($r = $this->requireCampusReviewBoard()) {
            return $r;
        }

        $allowedStatuses = [
            Research::STATUS_PENDING_CAMPUS_REVIEW,
            Research::STATUS_REVISION_CAMPUS_REVIEW,
            Research::STATUS_REJECTED_CAMPUS_REVIEW,
            Research::STATUS_PENDING_RDE,
            Research::STATUS_APPROVED,
        ];
        $research = Research::with(['user:id,name', 'college:id,name,code', 'category:id,name'])
            ->whereIn('status', $allowedStatuses)
            ->when(in_array($request->query('status'), $allowedStatuses, true), function ($query) use ($request) {
                $query->where('status', $request->query('status'));
            }, function ($query) {
                $query->where('status', Research::STATUS_PENDING_CAMPUS_REVIEW);
            })
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('admin.campus-review-board', compact('research', 'allowedStatuses'));
    }

    public function bulkUpdate(Request $request)
    {
        if ($r = $this->requireAuth()) {
            return $r;
        }

        $isCollegeAdmin = session('user_role') === 'admin' && session('user_college_id');
        $isRde = session('user_role') === 'super_admin' || (session('user_role') === 'admin' && ! session('user_college_id'));

        if (! $isCollegeAdmin && ! $isRde) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        $validated = $request->validate([
            'research_ids' => 'required|array|min:1',
            'research_ids.*' => 'integer|exists:research,id',
            'action' => 'required|in:approve,reject,assign_college,tag_category',
            'reason' => 'nullable|string|max:1000',
            'college_id' => 'nullable|exists:colleges,id',
            'category_id' => 'nullable|exists:categories,id',
            'academic_level' => ['nullable', 'in:BS,GS'],
        ]);

        if ($validated['action'] === 'reject' && empty(trim((string) ($validated['reason'] ?? '')))) {
            return redirect()->back()->with('error', 'A rejection reason is required for bulk reject.');
        }

        if ($validated['action'] === 'assign_college' && empty($validated['college_id'])) {
            return redirect()->back()->with('error', 'Select a college before running bulk college assignment.');
        }

        if ($validated['action'] === 'assign_college' && $isCollegeAdmin && (int) $validated['college_id'] !== (int) session('user_college_id')) {
            return redirect()->back()->with('error', 'College admins can only assign submissions to their own college.');
        }

        if ($validated['action'] === 'tag_category' && empty($validated['category_id'])) {
            return redirect()->back()->with('error', 'Select a category before running bulk category tagging.');
        }
        if ($validated['action'] === 'tag_category' && empty($validated['academic_level'])) {
            return redirect()->back()->with('error', 'Select an academic level before running bulk category tagging.');
        }
        if ($validated['action'] === 'tag_category'
            && ! app(ResearchCategoryClassification::class)->allows(
                $validated['academic_level'],
                (int) $validated['category_id']
            )) {
            throw ValidationException::withMessages([
                'category_id' => 'The selected research category does not match the academic level.',
            ]);
        }

        $query = Research::whereIn('id', $validated['research_ids']);

        if ($isCollegeAdmin) {
            $query->where('college_id', session('user_college_id'));
        }

        $records = $query->get();
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($records as $research) {
            $fromStatus = $research->status;
            $changed = false;

            if ($validated['action'] === 'approve') {
                if ($isCollegeAdmin && $research->status === Research::STATUS_PENDING_COLLEGE) {
                    $research->recordLifecycleStage('college_reviewed', (int) session('user_id'));
                    $research->update([
                        'status' => Research::STATUS_PENDING_CAMPUS_REVIEW,
                        'revision_notes' => null,
                        'revision_fields' => null,
                        'revision_field_notes' => null,
                        'rejection_reason' => null,
                    ]);
                    $this->notifyResearchOwner($research, [
                        'type' => 'approval_decision',
                        'title' => 'College review passed',
                        'message' => '"'.$research->title.'" passed college review and is now waiting for Campus Review Board review.',
                        'action_url' => route('research.show', ResearchToken::encode($research->id)),
                        'action_label' => 'View Submission',
                        'icon' => 'fa-building-columns',
                        'level' => 'success',
                    ]);
                    $this->notifyNextReviewStage($research);
                    $this->logStatusEvent($research, 'bulk_approved_college', $fromStatus, $research->status);
                    $changed = true;
                } elseif ($isRde && $research->status === Research::STATUS_PENDING_RDE && $research->hasCurrentCampusReviewBoardApproval()) {
                    $reviewedAt = now();
                    $research->recordLifecycleStage('rde_reviewed', (int) session('user_id'), $reviewedAt);
                    $research->recordLifecycleStage('accepted', (int) session('user_id'), $reviewedAt);
                    $research->recordLifecycleStage('published', (int) session('user_id'), $reviewedAt);
                    $research->update([
                        'status' => Research::STATUS_APPROVED,
                        'revision_notes' => null,
                        'revision_fields' => null,
                        'revision_field_notes' => null,
                        'rejection_reason' => null,
                    ]);
                    $this->notifyResearchOwner($research, [
                        'type' => 'approval_decision',
                        'title' => 'Research approved',
                        'message' => '"'.$research->title.'" was approved by the RDE office and added to the archive.',
                        'action_url' => route('research.show', ResearchToken::encode($research->id)),
                        'action_label' => 'Open Research',
                        'icon' => 'fa-circle-check',
                        'level' => 'success',
                    ]);
                    $this->logStatusEvent($research, 'bulk_approved_rde', $fromStatus, $research->status);
                    $changed = true;
                }
            }

            if ($validated['action'] === 'reject') {
                $reason = trim((string) $validated['reason']);

                if ($isCollegeAdmin && $research->status === Research::STATUS_PENDING_COLLEGE) {
                    $research->recordLifecycleStage('college_reviewed', (int) session('user_id'));
                    $research->update([
                        'status' => Research::STATUS_REJECTED_COLLEGE,
                        'revision_notes' => null,
                        'revision_fields' => null,
                        'revision_field_notes' => null,
                        'rejection_reason' => $reason,
                    ]);
                    $this->notifyResearchOwner($research, [
                        'type' => 'approval_decision',
                        'title' => 'Submission rejected by college',
                        'message' => '"'.$research->title.'" was rejected during college review. Open the submission to see the reason.',
                        'action_url' => route('research.show', ResearchToken::encode($research->id)),
                        'action_label' => 'Review Feedback',
                        'icon' => 'fa-circle-xmark',
                        'level' => 'danger',
                    ]);
                    $this->logStatusEvent($research, 'bulk_rejected_college', $fromStatus, $research->status, $reason);
                    $changed = true;
                } elseif ($isRde && $research->status === Research::STATUS_PENDING_RDE) {
                    if (! $research->hasCurrentCampusReviewBoardApproval()) {
                        $skippedCount++;

                        continue;
                    }
                    $research->recordLifecycleStage('rde_reviewed', (int) session('user_id'));
                    $research->update([
                        'status' => Research::STATUS_REJECTED_RDE,
                        'revision_notes' => null,
                        'revision_fields' => null,
                        'revision_field_notes' => null,
                        'rejection_reason' => $reason,
                    ]);
                    $this->notifyResearchOwner($research, [
                        'type' => 'approval_decision',
                        'title' => 'Submission rejected by RDE',
                        'message' => '"'.$research->title.'" was rejected during final review. Open the submission to see the decision details.',
                        'action_url' => route('research.show', ResearchToken::encode($research->id)),
                        'action_label' => 'Review Feedback',
                        'icon' => 'fa-circle-xmark',
                        'level' => 'danger',
                    ]);
                    $this->logStatusEvent($research, 'bulk_rejected_rde', $fromStatus, $research->status, $reason);
                    $changed = true;
                }
            }

            if ($validated['action'] === 'assign_college') {
                $research->update([
                    'college_id' => $validated['college_id'],
                ]);
                $this->logStatusEvent($research, 'bulk_assigned_college', $fromStatus, $research->status, null, [
                    'college_id' => (int) $validated['college_id'],
                ]);
                $changed = true;
            }

            if ($validated['action'] === 'tag_category') {
                $previousClassification = [
                    'academic_level' => $research->academic_level,
                    'category_id' => $research->category_id,
                ];
                $research->update([
                    'academic_level' => $validated['academic_level'],
                    'category_id' => $validated['category_id'],
                ]);
                $this->logStatusEvent($research, 'bulk_tagged_category', $fromStatus, $research->status, null, [
                    'previous' => $previousClassification,
                    'academic_level' => $validated['academic_level'],
                    'category_id' => (int) $validated['category_id'],
                ]);
                $changed = true;
            }

            if ($changed) {
                $updatedCount++;
            } else {
                $skippedCount++;
            }
        }

        return redirect()->back()->with('success', 'Bulk action finished. Updated: '.$updatedCount.'. Skipped: '.$skippedCount.'.');
    }

    public function approveByCollege($id)
    {
        if ($r = $this->requireCollegeAdmin()) {
            return $r;
        }

        $research = Research::findOrFail($id);

        if ($research->college_id !== session('user_college_id')) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        if ($research->status !== Research::STATUS_PENDING_COLLEGE) {
            return redirect()->back()->with('error', 'Only submissions pending college approval can be forwarded.');
        }

        $research->recordLifecycleStage('college_reviewed', (int) session('user_id'));
        $research->update([
            'status' => Research::STATUS_PENDING_CAMPUS_REVIEW,
            'revision_notes' => null,
            'revision_fields' => null,
            'revision_field_notes' => null,
            'rejection_reason' => null,
        ]);

        $this->logStatusEvent($research, 'approved_by_college', Research::STATUS_PENDING_COLLEGE, Research::STATUS_PENDING_CAMPUS_REVIEW);

        $this->notifyResearchOwner($research, [
            'type' => 'approval_decision',
            'title' => 'College review passed',
            'message' => '"'.$research->title.'" passed college review and is now waiting for Campus Review Board review.',
            'action_url' => route('research.show', ResearchToken::encode($research->id)),
            'action_label' => 'View Submission',
            'icon' => 'fa-building-columns',
            'level' => 'success',
        ]);
        $this->notifyNextReviewStage($research);

        return redirect()->back()->with('success', 'Paper approved by college and forwarded to the Campus Review Board.');
    }

    public function rejectByCollege(Request $request, $id)
    {
        if ($r = $this->requireCollegeAdmin()) {
            return $r;
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $research = Research::findOrFail($id);

        if ($research->college_id !== session('user_college_id')) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        if ($research->status !== Research::STATUS_PENDING_COLLEGE) {
            return redirect()->back()->with('error', 'Only submissions pending college approval can be rejected here.');
        }

        $research->recordLifecycleStage('college_reviewed', (int) session('user_id'));
        $research->update([
            'status' => Research::STATUS_REJECTED_COLLEGE,
            'revision_notes' => null,
            'revision_fields' => null,
            'revision_field_notes' => null,
            'rejection_reason' => $validated['reason'],
        ]);

        $this->logStatusEvent($research, 'rejected_by_college', Research::STATUS_PENDING_COLLEGE, Research::STATUS_REJECTED_COLLEGE, $validated['reason']);

        $this->notifyResearchOwner($research, [
            'type' => 'approval_decision',
            'title' => 'Submission rejected by college',
            'message' => '"'.$research->title.'" was rejected during college review. Open the submission to see the reason.',
            'action_url' => route('research.show', ResearchToken::encode($research->id)),
            'action_label' => 'Review Feedback',
            'icon' => 'fa-circle-xmark',
            'level' => 'danger',
        ]);

        return redirect()->back()->with('success', 'Paper rejected at the college review stage.');
    }

    public function requestRevisionByCollege(Request $request, $id)
    {
        if ($r = $this->requireCollegeAdmin()) {
            return $r;
        }

        $validated = $request->validate([
            'revision_fields' => $this->revisionFieldRules(),
            'revision_fields.*' => $this->revisionFieldItemRules(),
        ]);
        $selectedFields = array_values($validated['revision_fields']);
        $fieldNotes = $this->validatedRevisionFieldNotes($request, $selectedFields);

        $research = Research::findOrFail($id);

        if ($research->college_id !== session('user_college_id')) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        if ($research->status !== Research::STATUS_PENDING_COLLEGE) {
            return redirect()->back()->with('error', 'Only submissions pending college approval can be sent back for revision.');
        }

        $research->recordLifecycleStage('college_reviewed', (int) session('user_id'));
        $research->update([
            'status' => Research::STATUS_REVISION_COLLEGE,
            'revision_notes' => null,
            'revision_fields' => $selectedFields,
            'revision_field_notes' => $fieldNotes,
            'rejection_reason' => null,
        ]);

        $this->logStatusEvent($research, 'revision_requested_by_college', Research::STATUS_PENDING_COLLEGE, Research::STATUS_REVISION_COLLEGE, null, [
            'fields' => $selectedFields,
            'field_notes' => $fieldNotes,
        ]);

        $this->notifyResearchOwner($research, [
            'type' => 'revision_request',
            'title' => 'College revision requested',
            'message' => 'Your submission "'.$research->title.'" needs updates before it can move forward.',
            'action_url' => route('research.edit', ResearchToken::encode($research->id)),
            'action_label' => 'Revise Submission',
            'icon' => 'fa-rotate-left',
            'level' => 'warning',
        ]);

        return redirect()->back()->with('success', 'Revision request sent back to the student from the college review stage.');
    }

    public function approveByCampusReviewBoard(Request $request, $id)
    {
        if ($r = $this->requireCampusReviewBoard()) {
            return $r;
        }

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);
        $research = Research::findOrFail($id);
        if ($research->status !== Research::STATUS_PENDING_CAMPUS_REVIEW) {
            return redirect()->back()->with('error', 'Only submissions awaiting Campus Review Board review can be approved.');
        }

        $fromStatus = $research->status;
        $research->recordLifecycleStage('campus_review_board_reviewed', (int) session('user_id'));
        $research->update([
            'status' => Research::STATUS_PENDING_RDE,
            'revision_notes' => null,
            'revision_fields' => null,
            'revision_field_notes' => null,
            'rejection_reason' => null,
        ]);
        $comment = trim((string) ($validated['comment'] ?? ''));
        $this->logStatusEvent(
            $research,
            'approved_by_campus_review_board',
            $fromStatus,
            $research->status,
            $comment !== '' ? $comment : null
        );
        $this->notifyResearchOwner($research, [
            'type' => 'approval_decision',
            'title' => 'Campus Review Board review passed',
            'message' => '"'.$research->title.'" passed Campus Review Board review and is now awaiting RDE approval.',
            'action_url' => route('research.show', ResearchToken::encode($research->id)),
            'action_label' => 'View Submission',
            'icon' => 'fa-users',
            'level' => 'success',
        ]);
        $this->notifyNextReviewStage($research);

        return redirect()->back()->with('success', 'Paper approved by the Campus Review Board and forwarded to RDE.');
    }

    public function rejectByCampusReviewBoard(Request $request, $id)
    {
        if ($r = $this->requireCampusReviewBoard()) {
            return $r;
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $research = Research::findOrFail($id);
        if ($research->status !== Research::STATUS_PENDING_CAMPUS_REVIEW) {
            return redirect()->back()->with('error', 'Only submissions awaiting Campus Review Board review can be rejected.');
        }

        $fromStatus = $research->status;
        $research->recordLifecycleStage('campus_review_board_reviewed', (int) session('user_id'));
        $research->update([
            'status' => Research::STATUS_REJECTED_CAMPUS_REVIEW,
            'revision_notes' => null,
            'revision_fields' => null,
            'revision_field_notes' => null,
            'rejection_reason' => $validated['reason'],
        ]);
        $this->logStatusEvent(
            $research,
            'rejected_by_campus_review_board',
            $fromStatus,
            $research->status,
            $validated['reason']
        );
        $this->notifyResearchOwner($research, [
            'type' => 'approval_decision',
            'title' => 'Submission rejected by Campus Review Board',
            'message' => '"'.$research->title.'" was rejected by the Campus Review Board. Open the submission to see the reason.',
            'action_url' => route('research.show', ResearchToken::encode($research->id)),
            'action_label' => 'Review Decision',
            'icon' => 'fa-circle-xmark',
            'level' => 'danger',
        ]);

        return redirect()->back()->with('success', 'Paper rejected by the Campus Review Board.');
    }

    public function requestRevisionByCampusReviewBoard(Request $request, $id)
    {
        if ($r = $this->requireCampusReviewBoard()) {
            return $r;
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $research = Research::findOrFail($id);
        if ($research->status !== Research::STATUS_PENDING_CAMPUS_REVIEW) {
            return redirect()->back()->with('error', 'Only submissions awaiting Campus Review Board review can be returned for revision.');
        }

        $fromStatus = $research->status;
        $research->recordLifecycleStage('campus_review_board_reviewed', (int) session('user_id'));
        $research->update([
            'status' => Research::STATUS_REVISION_CAMPUS_REVIEW,
            'revision_notes' => $validated['reason'],
            'revision_fields' => null,
            'revision_field_notes' => null,
            'rejection_reason' => null,
        ]);
        $this->logStatusEvent(
            $research,
            'revision_requested_by_campus_review_board',
            $fromStatus,
            $research->status,
            $validated['reason']
        );
        $this->notifyResearchOwner($research, [
            'type' => 'revision_request',
            'title' => 'Campus Review Board revision requested',
            'message' => 'Your submission "'.$research->title.'" needs changes before review can continue.',
            'action_url' => route('research.edit', ResearchToken::encode($research->id)),
            'action_label' => 'Revise Submission',
            'icon' => 'fa-rotate-left',
            'level' => 'warning',
        ]);

        return redirect()->back()->with('success', 'Revision request sent to the submitter.');
    }

    public function approveByRde($id)
    {
        if ($r = $this->requireRde()) {
            return $r;
        }

        $research = Research::findOrFail($id);

        if ($research->status !== Research::STATUS_PENDING_RDE || ! $research->hasCurrentCampusReviewBoardApproval()) {
            return redirect()->back()->with('error', 'Campus Review Board approval is required before RDE approval.');
        }

        $reviewedAt = now();
        $research->recordLifecycleStage('rde_reviewed', (int) session('user_id'), $reviewedAt);
        $research->recordLifecycleStage('accepted', (int) session('user_id'), $reviewedAt);
        $research->recordLifecycleStage('published', (int) session('user_id'), $reviewedAt);
        $research->update([
            'status' => Research::STATUS_APPROVED,
            'revision_notes' => null,
            'revision_fields' => null,
            'revision_field_notes' => null,
            'rejection_reason' => null,
        ]);

        $this->logStatusEvent($research, 'approved_by_rde', Research::STATUS_PENDING_RDE, Research::STATUS_APPROVED);

        $this->notifyResearchOwner($research, [
            'type' => 'approval_decision',
            'title' => 'Research approved',
            'message' => '"'.$research->title.'" was approved by the RDE office and added to the archive.',
            'action_url' => route('research.show', ResearchToken::encode($research->id)),
            'action_label' => 'Open Research',
            'icon' => 'fa-circle-check',
            'level' => 'success',
        ]);

        return redirect()->back()->with('success', 'Paper approved by the RDE office and added to the archive.');
    }

    public function rejectByRde(Request $request, $id)
    {
        if ($r = $this->requireRde()) {
            return $r;
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $research = Research::findOrFail($id);

        if ($research->status !== Research::STATUS_PENDING_RDE || ! $research->hasCurrentCampusReviewBoardApproval()) {
            return redirect()->back()->with('error', 'Campus Review Board approval is required before RDE review.');
        }

        $research->recordLifecycleStage('rde_reviewed', (int) session('user_id'));
        $research->update([
            'status' => Research::STATUS_REJECTED_RDE,
            'revision_notes' => null,
            'revision_fields' => null,
            'revision_field_notes' => null,
            'rejection_reason' => $validated['reason'],
        ]);

        $this->logStatusEvent($research, 'rejected_by_rde', Research::STATUS_PENDING_RDE, Research::STATUS_REJECTED_RDE, $validated['reason']);

        $this->notifyResearchOwner($research, [
            'type' => 'approval_decision',
            'title' => 'Submission rejected by RDE',
            'message' => '"'.$research->title.'" was rejected during final review. Open the submission to see the decision details.',
            'action_url' => route('research.show', ResearchToken::encode($research->id)),
            'action_label' => 'Review Feedback',
            'icon' => 'fa-circle-xmark',
            'level' => 'danger',
        ]);

        return redirect()->back()->with('success', 'Paper rejected at the RDE review stage.');
    }

    public function requestRevisionByRde(Request $request, $id)
    {
        if ($r = $this->requireRde()) {
            return $r;
        }

        $validated = $request->validate([
            'revision_fields' => $this->revisionFieldRules(),
            'revision_fields.*' => $this->revisionFieldItemRules(),
        ]);
        $selectedFields = array_values($validated['revision_fields']);
        $fieldNotes = $this->validatedRevisionFieldNotes($request, $selectedFields);

        $research = Research::findOrFail($id);

        if ($research->status !== Research::STATUS_PENDING_RDE || ! $research->hasCurrentCampusReviewBoardApproval()) {
            return redirect()->back()->with('error', 'Campus Review Board approval is required before RDE review.');
        }

        $research->recordLifecycleStage('rde_reviewed', (int) session('user_id'));
        $research->update([
            'status' => Research::STATUS_REVISION_RDE,
            'revision_notes' => null,
            'revision_fields' => $selectedFields,
            'revision_field_notes' => $fieldNotes,
            'rejection_reason' => null,
        ]);

        $this->logStatusEvent($research, 'revision_requested_by_rde', Research::STATUS_PENDING_RDE, Research::STATUS_REVISION_RDE, null, [
            'fields' => $selectedFields,
            'field_notes' => $fieldNotes,
        ]);

        $this->notifyResearchOwner($research, [
            'type' => 'revision_request',
            'title' => 'RDE revision requested',
            'message' => 'Your submission "'.$research->title.'" needs revision before final approval.',
            'action_url' => route('research.edit', ResearchToken::encode($research->id)),
            'action_label' => 'Revise Submission',
            'icon' => 'fa-file-pen',
            'level' => 'warning',
        ]);

        return redirect()->back()->with('success', 'Revision request sent back to the student from the RDE review stage.');
    }
}
