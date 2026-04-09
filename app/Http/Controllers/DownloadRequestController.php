<?php

namespace App\Http\Controllers;

use App\Models\DownloadRequest;
use App\Models\Research;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadRequestController extends Controller
{
    private function authCheck()
    {
        if (!session('user_id')) {
            return redirect()->route('login');
        }
        return null;
    }

    // User submits a download request
    public function store(Request $request, $researchId)
    {
        if ($r = $this->authCheck()) return $r;

        $research = Research::findOrFail($researchId);

        $validated = $request->validate([
            'purpose' => 'required|string|max:500',
        ]);

        // Check if user already has a pending or approved request
        $existing = DownloadRequest::where('user_id', session('user_id'))
            ->where('research_id', $researchId)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            if ($existing->status === 'pending') {
                return redirect()->back()->with('error', 'You already have a pending request for this paper.');
            }
            if ($existing->status === 'approved') {
                return redirect()->back()->with('error', 'You already have an approved request. You can download the paper.');
            }
        }

        DownloadRequest::create([
            'user_id' => session('user_id'),
            'research_id' => $researchId,
            'purpose' => $validated['purpose'],
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Download request submitted! Please wait for RDE Office approval.');
    }

    // RDE admin views all requests
    public function index(Request $request)
    {
        if ($r = $this->authCheck()) return $r;

        $role = session('user_role');
        $collegeId = session('user_college_id');

        // Only RDE (admin with no college) and super_admin can manage requests
        if (!($role === 'super_admin' || ($role === 'admin' && !$collegeId))) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        $query = DownloadRequest::with(['user', 'research', 'reviewer']);

        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        } else {
            $query->where('status', 'pending');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%$search%"))
                  ->orWhereHas('research', fn($r) => $r->where('title', 'like', "%$search%"));
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('download_requests.index', compact('requests'));
    }

    // RDE approves a request
    public function approve($id)
    {
        if ($r = $this->authCheck()) return $r;
        $this->requireRDE();

        $downloadRequest = DownloadRequest::findOrFail($id);
        $downloadRequest->update([
            'status' => 'approved',
            'reviewed_by' => session('user_id'),
            'reviewed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Download request approved.');
    }

    // RDE rejects a request
    public function reject(Request $request, $id)
    {
        if ($r = $this->authCheck()) return $r;
        $this->requireRDE();

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $downloadRequest = DownloadRequest::findOrFail($id);
        $downloadRequest->update([
            'status' => 'rejected',
            'reviewed_by' => session('user_id'),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()->back()->with('success', 'Download request rejected.');
    }

    // User's own requests
    public function myRequests()
    {
        if ($r = $this->authCheck()) return $r;

        $requests = DownloadRequest::with(['research', 'reviewer'])
            ->where('user_id', session('user_id'))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('download_requests.my_requests', compact('requests'));
    }

    private function requireRDE()
    {
        $role = session('user_role');
        $collegeId = session('user_college_id');

        if (!($role === 'super_admin' || ($role === 'admin' && !$collegeId))) {
            abort(403, 'Unauthorized.');
        }
    }
}
