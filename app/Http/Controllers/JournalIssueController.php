<?php

namespace App\Http\Controllers;

use App\Models\JournalIssue;
use App\Models\Research;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class JournalIssueController extends Controller
{
    private function requireRde()
    {
        if (! session('user_id')) {
            return redirect()->route('login');
        }

        $isRdeAdmin = session('user_role') === 'admin' && ! session('user_college_id');
        if (session('user_role') !== 'super_admin' && ! $isRdeAdmin) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        return null;
    }

    public function index()
    {
        if ($response = $this->requireRde()) {
            return $response;
        }

        $journalIssues = JournalIssue::withCount('papers')
            ->orderByDesc('publication_year')
            ->orderByDesc('volume_number')
            ->orderByDesc('issue_number')
            ->paginate(15);

        return view('journal-issues.index', compact('journalIssues'));
    }

    public function create()
    {
        if ($response = $this->requireRde()) {
            return $response;
        }

        return view('journal-issues.form', ['journalIssue' => new JournalIssue]);
    }

    public function store(Request $request)
    {
        if ($response = $this->requireRde()) {
            return $response;
        }

        $validated = $request->validate($this->issueRules());
        $issue = DB::transaction(function () use ($validated) {
            $issue = JournalIssue::create($validated + [
                'status' => JournalIssue::STATUS_DRAFT,
                'created_by' => (int) session('user_id'),
            ]);
            $this->recordEvent($issue, 'created');

            return $issue;
        });

        return redirect()->route('journal-issues.show', $issue)->with('success', 'Journal issue draft created.');
    }

    public function show(JournalIssue $journalIssue)
    {
        if ($response = $this->requireRde()) {
            return $response;
        }

        $journalIssue->load([
            'papers' => fn ($query) => $query->select([
                'id',
                'title',
                'authors',
                'college_id',
                'journal_issue_id',
                'published_at',
            ])->with('college:id,name')->orderBy('title'),
            'creator:id,name',
            'finalizer:id,name',
            'events.actor:id,name',
        ]);
        $availablePapers = Research::query()
            ->where('status', Research::STATUS_APPROVED)
            ->whereNotNull('published_at')
            ->where(function ($query) use ($journalIssue) {
                $query->whereNull('journal_issue_id')
                    ->orWhere('journal_issue_id', $journalIssue->id);
            })
            ->orderBy('title')
            ->get(['id', 'title', 'authors', 'college_id', 'journal_issue_id']);

        return view('journal-issues.show', compact('journalIssue', 'availablePapers'));
    }

    public function edit(JournalIssue $journalIssue)
    {
        if ($response = $this->requireRde()) {
            return $response;
        }
        abort_unless($journalIssue->status === JournalIssue::STATUS_DRAFT, 403);

        return view('journal-issues.form', compact('journalIssue'));
    }

    public function update(Request $request, JournalIssue $journalIssue)
    {
        if ($response = $this->requireRde()) {
            return $response;
        }
        abort_unless($journalIssue->status === JournalIssue::STATUS_DRAFT, 403);

        $validated = $request->validate($this->issueRules($journalIssue));
        DB::transaction(function () use ($journalIssue, $validated) {
            $before = $journalIssue->only([
                'journal_title',
                'volume_number',
                'issue_number',
                'publication_year',
                'publication_date',
            ]);
            $journalIssue->update($validated);
            $this->recordEvent($journalIssue, 'updated', null, [
                'before' => $before,
                'after' => $journalIssue->only(array_keys($before)),
            ]);
        });

        return redirect()->route('journal-issues.show', $journalIssue)->with('success', 'Journal issue draft updated.');
    }

    public function updatePapers(Request $request, JournalIssue $journalIssue)
    {
        if ($response = $this->requireRde()) {
            return $response;
        }
        abort_unless($journalIssue->status === JournalIssue::STATUS_DRAFT, 403);

        $validated = $request->validate([
            'research_ids' => ['nullable', 'array'],
            'research_ids.*' => ['integer', 'distinct', 'exists:research,id'],
        ]);
        $paperIds = array_map('intval', $validated['research_ids'] ?? []);
        $papers = Research::with('journalIssue:id,status')->whereIn('id', $paperIds)->get();

        if ($papers->contains(fn (Research $paper) => $paper->status !== Research::STATUS_APPROVED
            || $paper->published_at === null
            || ($paper->journal_issue_id !== null && $paper->journal_issue_id !== $journalIssue->id))) {
            return back()->withErrors([
                'research_ids' => 'Only published, approved papers that are not assigned to another issue can be selected.',
            ]);
        }

        DB::transaction(function () use ($journalIssue, $paperIds) {
            $existingIds = $journalIssue->papers()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $removedIds = array_values(array_diff($existingIds, $paperIds));
            $addedIds = array_values(array_diff($paperIds, $existingIds));

            Research::where('journal_issue_id', $journalIssue->id)
                ->whereNotIn('id', $paperIds ?: [0])
                ->update(['journal_issue_id' => null]);
            if ($paperIds !== []) {
                Research::whereIn('id', $paperIds)->update(['journal_issue_id' => $journalIssue->id]);
            }

            if ($addedIds !== [] || $removedIds !== []) {
                $this->recordEvent($journalIssue, 'papers_updated', null, [
                    'assigned_research_ids' => $addedIds,
                    'removed_research_ids' => $removedIds,
                ]);
            }
        });

        return redirect()->route('journal-issues.show', $journalIssue)->with('success', 'Journal issue paper assignments updated.');
    }

    public function finalize(Request $request, JournalIssue $journalIssue)
    {
        if ($response = $this->requireRde()) {
            return $response;
        }
        abort_unless($journalIssue->status === JournalIssue::STATUS_DRAFT, 403);

        $request->validate([
            'confirmation' => ['accepted'],
        ]);
        if ($journalIssue->publication_date === null) {
            return back()->withErrors(['publication_date' => 'Set a publication date before finalizing this issue.']);
        }
        if (! $journalIssue->papers()->exists()) {
            return back()->withErrors(['research_ids' => 'Assign at least one published paper before finalizing this issue.']);
        }

        DB::transaction(function () use ($journalIssue) {
            $journalIssue->update([
                'status' => JournalIssue::STATUS_FINALIZED,
                'finalized_at' => now(),
                'finalized_by' => (int) session('user_id'),
            ]);
            $this->recordEvent($journalIssue, 'finalized');
        });

        return redirect()->route('journal-issues.show', $journalIssue)->with('success', 'Journal issue finalized.');
    }

    public function reopen(Request $request, JournalIssue $journalIssue)
    {
        if ($response = $this->requireRde()) {
            return $response;
        }
        abort_unless($journalIssue->status === JournalIssue::STATUS_FINALIZED, 403);

        $validated = $request->validate([
            'revision_reason' => ['required', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($journalIssue, $validated) {
            $journalIssue->update([
                'status' => JournalIssue::STATUS_DRAFT,
                'finalized_at' => null,
                'finalized_by' => null,
            ]);
            $this->recordEvent($journalIssue, 'revision_started', $validated['revision_reason']);
        });

        return redirect()->route('journal-issues.show', $journalIssue)->with('success', 'Issue reopened for controlled revision.');
    }

    private function issueRules(?JournalIssue $journalIssue = null): array
    {
        $uniqueIssue = Rule::unique('journal_issues', 'issue_number')
            ->where(fn ($query) => $query
                ->where('journal_title', request('journal_title'))
                ->where('publication_year', request('publication_year'))
                ->where('volume_number', request('volume_number')));
        if ($journalIssue) {
            $uniqueIssue->ignore($journalIssue->id);
        }

        return [
            'journal_title' => ['required', 'string', 'max:255'],
            'volume_number' => ['required', 'integer', 'min:1', 'max:65535'],
            'issue_number' => ['required', 'integer', 'min:1', 'max:65535', $uniqueIssue],
            'publication_year' => ['required', 'integer', 'between:1900,2100'],
            'publication_date' => ['nullable', 'date'],
        ];
    }

    private function recordEvent(JournalIssue $journalIssue, string $action, ?string $notes = null, ?array $meta = null): void
    {
        $journalIssue->events()->create([
            'actor_id' => (int) session('user_id'),
            'action' => $action,
            'notes' => $notes,
            'meta' => $meta,
        ]);
    }
}
