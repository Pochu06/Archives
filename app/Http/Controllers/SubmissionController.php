<?php

namespace App\Http\Controllers;

use App\Models\Research;
use App\Models\Category;
use App\Models\College;
use App\Notifications\InAppAlertNotification;
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
        return ['required', 'string', 'in:' . implode(',', array_keys(Research::revisionFieldOptions()))];
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
        if (!session('user_id')) return redirect()->route('login');
        return null;
    }

    private function requireStudent()
    {
        if ($r = $this->requireAuth()) return $r;
        if (session('user_role') !== 'student') return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        return null;
    }

    private function requireCollegeAdmin()
    {
        if ($r = $this->requireAuth()) return $r;
        if (!(session('user_role') === 'admin' && session('user_college_id'))) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }
        return null;
    }

    private function requireRde()
    {
        if ($r = $this->requireAuth()) return $r;
        if (!(session('user_role') === 'super_admin' || (session('user_role') === 'admin' && !session('user_college_id')))) {
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

    public function index(Request $request)
    {
        if ($r = $this->requireStudent()) return $r;

        $research = Research::with(['college', 'category'])
            ->where('user_id', session('user_id'))
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('submissions.index', compact('research'));
    }

    public function collegeIndex(Request $request)
    {
        if ($r = $this->requireCollegeAdmin()) return $r;

        $research = Research::with(['user', 'college', 'category'])
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
        $categories = Category::orderBy('name')->get();
        $colleges = College::where('id', session('user_college_id'))
            ->orderBy('name')
            ->get();

        return view('admin.submissions', compact('research', 'pageTitle', 'pageSubtitle', 'approveRouteName', 'revisionRouteName', 'rejectRouteName', 'defaultPendingStatus', 'showRdeLabels', 'revisionFieldOptions', 'categories', 'colleges'));
    }

    public function rdeIndex(Request $request)
    {
        if ($r = $this->requireRde()) return $r;

        $research = Research::with(['user', 'college', 'category'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            }, function ($query) {
                $query->where('status', Research::STATUS_PENDING_RDE);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $pageTitle = 'RDE Research Approvals';
        $pageSubtitle = 'Review college-approved papers and decide final archive approval';
        $approveRouteName = 'submissions.rde-approve';
        $revisionRouteName = 'submissions.rde-revision';
        $rejectRouteName = 'submissions.rde-reject';
        $defaultPendingStatus = Research::STATUS_PENDING_RDE;
        $showRdeLabels = true;
        $revisionFieldOptions = Research::revisionFieldOptions();
        $categories = Category::orderBy('name')->get();
        $colleges = College::query()
            ->orderBy('name')
            ->get();

        return view('admin.submissions', compact('research', 'pageTitle', 'pageSubtitle', 'approveRouteName', 'revisionRouteName', 'rejectRouteName', 'defaultPendingStatus', 'showRdeLabels', 'revisionFieldOptions', 'categories', 'colleges'));
    }

    public function bulkUpdate(Request $request)
    {
        if ($r = $this->requireAuth()) return $r;

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
                    $research->update([
                        'status' => Research::STATUS_PENDING_RDE,
                        'revision_notes' => null,
                        'revision_fields' => null,
                        'revision_field_notes' => null,
                        'rejection_reason' => null,
                    ]);
                    $this->notifyResearchOwner($research, [
                        'type' => 'approval_decision',
                        'title' => 'College review passed',
                        'message' => '"'.$research->title.'" passed college review and is now waiting for RDE approval.',
                        'action_url' => route('research.show', $research->id),
                        'action_label' => 'View Submission',
                        'icon' => 'fa-building-columns',
                        'level' => 'success',
                    ]);
                    $this->logStatusEvent($research, 'bulk_approved_college', $fromStatus, $research->status);
                    $changed = true;
                } elseif ($isRde && $research->status === Research::STATUS_PENDING_RDE) {
                    $research->update([
                        'status' => Research::STATUS_APPROVED,
                        'approved_by' => session('user_id'),
                        'approved_at' => now(),
                        'revision_notes' => null,
                        'revision_fields' => null,
                        'revision_field_notes' => null,
                        'rejection_reason' => null,
                    ]);
                    $this->notifyResearchOwner($research, [
                        'type' => 'approval_decision',
                        'title' => 'Research approved',
                        'message' => '"'.$research->title.'" was approved by the RDE office and added to the archive.',
                        'action_url' => route('research.show', $research->id),
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
                    $research->update([
                        'status' => Research::STATUS_REJECTED_COLLEGE,
                        'revision_notes' => null,
                        'revision_fields' => null,
                        'revision_field_notes' => null,
                        'rejection_reason' => $reason,
                        'approved_by' => null,
                        'approved_at' => null,
                    ]);
                    $this->notifyResearchOwner($research, [
                        'type' => 'approval_decision',
                        'title' => 'Submission rejected by college',
                        'message' => '"'.$research->title.'" was rejected during college review. Open the submission to see the reason.',
                        'action_url' => route('research.show', $research->id),
                        'action_label' => 'Review Feedback',
                        'icon' => 'fa-circle-xmark',
                        'level' => 'danger',
                    ]);
                    $this->logStatusEvent($research, 'bulk_rejected_college', $fromStatus, $research->status, $reason);
                    $changed = true;
                } elseif ($isRde && $research->status === Research::STATUS_PENDING_RDE) {
                    $research->update([
                        'status' => Research::STATUS_REJECTED_RDE,
                        'revision_notes' => null,
                        'revision_fields' => null,
                        'revision_field_notes' => null,
                        'rejection_reason' => $reason,
                        'approved_by' => null,
                        'approved_at' => null,
                    ]);
                    $this->notifyResearchOwner($research, [
                        'type' => 'approval_decision',
                        'title' => 'Submission rejected by RDE',
                        'message' => '"'.$research->title.'" was rejected during final review. Open the submission to see the decision details.',
                        'action_url' => route('research.show', $research->id),
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
                $research->update([
                    'category_id' => $validated['category_id'],
                ]);
                $this->logStatusEvent($research, 'bulk_tagged_category', $fromStatus, $research->status, null, [
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
        if ($r = $this->requireCollegeAdmin()) return $r;

        $research = Research::findOrFail($id);

        if ($research->college_id !== session('user_college_id')) {
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        if ($research->status !== Research::STATUS_PENDING_COLLEGE) {
            return redirect()->back()->with('error', 'Only submissions pending college approval can be forwarded.');
        }

        $research->update([
            'status' => Research::STATUS_PENDING_RDE,
            'revision_notes' => null,
            'revision_fields' => null,
            'revision_field_notes' => null,
            'rejection_reason' => null,
        ]);

        $this->logStatusEvent($research, 'approved_by_college', Research::STATUS_PENDING_COLLEGE, Research::STATUS_PENDING_RDE);

        $this->notifyResearchOwner($research, [
            'type' => 'approval_decision',
            'title' => 'College review passed',
            'message' => '"'.$research->title.'" passed college review and is now waiting for RDE approval.',
            'action_url' => route('research.show', $research->id),
            'action_label' => 'View Submission',
            'icon' => 'fa-building-columns',
            'level' => 'success',
        ]);

        return redirect()->back()->with('success', 'Paper approved by college and forwarded to the RDE office.');
    }

    public function rejectByCollege(Request $request, $id)
    {
        if ($r = $this->requireCollegeAdmin()) return $r;

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

        $research->update([
            'status' => Research::STATUS_REJECTED_COLLEGE,
            'revision_notes' => null,
            'revision_fields' => null,
            'revision_field_notes' => null,
            'rejection_reason' => $validated['reason'],
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->logStatusEvent($research, 'rejected_by_college', Research::STATUS_PENDING_COLLEGE, Research::STATUS_REJECTED_COLLEGE, $validated['reason']);

        $this->notifyResearchOwner($research, [
            'type' => 'approval_decision',
            'title' => 'Submission rejected by college',
            'message' => '"'.$research->title.'" was rejected during college review. Open the submission to see the reason.',
            'action_url' => route('research.show', $research->id),
            'action_label' => 'Review Feedback',
            'icon' => 'fa-circle-xmark',
            'level' => 'danger',
        ]);

        return redirect()->back()->with('success', 'Paper rejected at the college review stage.');
    }

    public function requestRevisionByCollege(Request $request, $id)
    {
        if ($r = $this->requireCollegeAdmin()) return $r;

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

        $research->update([
            'status' => Research::STATUS_REVISION_COLLEGE,
            'revision_notes' => null,
            'revision_fields' => $selectedFields,
            'revision_field_notes' => $fieldNotes,
            'rejection_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->logStatusEvent($research, 'revision_requested_by_college', Research::STATUS_PENDING_COLLEGE, Research::STATUS_REVISION_COLLEGE, null, [
            'fields' => $selectedFields,
            'field_notes' => $fieldNotes,
        ]);

        $this->notifyResearchOwner($research, [
            'type' => 'revision_request',
            'title' => 'College revision requested',
            'message' => 'Your submission "'.$research->title.'" needs updates before it can move forward.',
            'action_url' => route('research.edit', $research->id),
            'action_label' => 'Revise Submission',
            'icon' => 'fa-rotate-left',
            'level' => 'warning',
        ]);

        return redirect()->back()->with('success', 'Revision request sent back to the student from the college review stage.');
    }

    public function approveByRde($id)
    {
        if ($r = $this->requireRde()) return $r;

        $research = Research::findOrFail($id);

        if ($research->status !== Research::STATUS_PENDING_RDE) {
            return redirect()->back()->with('error', 'Only submissions pending RDE approval can be approved.');
        }

        $research->update([
            'status' => Research::STATUS_APPROVED,
            'approved_by' => session('user_id'),
            'approved_at' => now(),
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
            'action_url' => route('research.show', $research->id),
            'action_label' => 'Open Research',
            'icon' => 'fa-circle-check',
            'level' => 'success',
        ]);

        return redirect()->back()->with('success', 'Paper approved by the RDE office and added to the archive.');
    }

    public function rejectByRde(Request $request, $id)
    {
        if ($r = $this->requireRde()) return $r;

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $research = Research::findOrFail($id);

        if ($research->status !== Research::STATUS_PENDING_RDE) {
            return redirect()->back()->with('error', 'Only submissions pending RDE approval can be rejected here.');
        }

        $research->update([
            'status' => Research::STATUS_REJECTED_RDE,
            'revision_notes' => null,
            'revision_fields' => null,
            'revision_field_notes' => null,
            'rejection_reason' => $validated['reason'],
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->logStatusEvent($research, 'rejected_by_rde', Research::STATUS_PENDING_RDE, Research::STATUS_REJECTED_RDE, $validated['reason']);

        $this->notifyResearchOwner($research, [
            'type' => 'approval_decision',
            'title' => 'Submission rejected by RDE',
            'message' => '"'.$research->title.'" was rejected during final review. Open the submission to see the decision details.',
            'action_url' => route('research.show', $research->id),
            'action_label' => 'Review Feedback',
            'icon' => 'fa-circle-xmark',
            'level' => 'danger',
        ]);

        return redirect()->back()->with('success', 'Paper rejected at the RDE review stage.');
    }

    public function requestRevisionByRde(Request $request, $id)
    {
        if ($r = $this->requireRde()) return $r;

        $validated = $request->validate([
            'revision_fields' => $this->revisionFieldRules(),
            'revision_fields.*' => $this->revisionFieldItemRules(),
        ]);
        $selectedFields = array_values($validated['revision_fields']);
        $fieldNotes = $this->validatedRevisionFieldNotes($request, $selectedFields);

        $research = Research::findOrFail($id);

        if ($research->status !== Research::STATUS_PENDING_RDE) {
            return redirect()->back()->with('error', 'Only submissions pending RDE approval can be sent back for revision.');
        }

        $research->update([
            'status' => Research::STATUS_REVISION_RDE,
            'revision_notes' => null,
            'revision_fields' => $selectedFields,
            'revision_field_notes' => $fieldNotes,
            'rejection_reason' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->logStatusEvent($research, 'revision_requested_by_rde', Research::STATUS_PENDING_RDE, Research::STATUS_REVISION_RDE, null, [
            'fields' => $selectedFields,
            'field_notes' => $fieldNotes,
        ]);

        $this->notifyResearchOwner($research, [
            'type' => 'revision_request',
            'title' => 'RDE revision requested',
            'message' => 'Your submission "'.$research->title.'" needs revision before final approval.',
            'action_url' => route('research.edit', $research->id),
            'action_label' => 'Revise Submission',
            'icon' => 'fa-file-pen',
            'level' => 'warning',
        ]);

        return redirect()->back()->with('success', 'Revision request sent back to the student from the RDE review stage.');
    }
}
