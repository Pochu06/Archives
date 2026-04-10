<?php

namespace App\Http\Controllers;

use App\Models\Research;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubmissionController extends Controller
{
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

        return view('admin.submissions', compact('research', 'pageTitle', 'pageSubtitle', 'approveRouteName', 'revisionRouteName', 'rejectRouteName', 'defaultPendingStatus', 'showRdeLabels', 'revisionFieldOptions'));
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

        return view('admin.submissions', compact('research', 'pageTitle', 'pageSubtitle', 'approveRouteName', 'revisionRouteName', 'rejectRouteName', 'defaultPendingStatus', 'showRdeLabels', 'revisionFieldOptions'));
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

        return redirect()->back()->with('success', 'Revision request sent back to the student from the RDE review stage.');
    }
}
