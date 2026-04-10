<?php

namespace App\Http\Controllers;

use App\Models\Research;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
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
        $rejectRouteName = 'submissions.college-reject';
        $defaultPendingStatus = Research::STATUS_PENDING_COLLEGE;
        $showRdeLabels = false;

        return view('admin.submissions', compact('research', 'pageTitle', 'pageSubtitle', 'approveRouteName', 'rejectRouteName', 'defaultPendingStatus', 'showRdeLabels'));
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
        $rejectRouteName = 'submissions.rde-reject';
        $defaultPendingStatus = Research::STATUS_PENDING_RDE;
        $showRdeLabels = true;

        return view('admin.submissions', compact('research', 'pageTitle', 'pageSubtitle', 'approveRouteName', 'rejectRouteName', 'defaultPendingStatus', 'showRdeLabels'));
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
            'rejection_reason' => $validated['reason'],
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return redirect()->back()->with('success', 'Paper rejected at the college review stage.');
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
            'rejection_reason' => $validated['reason'],
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return redirect()->back()->with('success', 'Paper rejected at the RDE review stage.');
    }
}
