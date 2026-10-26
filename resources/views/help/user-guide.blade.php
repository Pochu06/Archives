@extends('layouts.app')

@section('title', 'ARCHIVES User Guide')
@section('page-title', 'User Guide')
@section('page-subtitle', 'Step-by-step help for using the research archive')

@section('content')
<div class="mx-auto max-w-5xl space-y-6 text-gray-800 dark:text-gray-100">
    <div class="rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50 to-orange-50 p-6 dark:border-gray-700 dark:from-gray-800 dark:to-gray-800">
        <p class="text-sm font-semibold uppercase tracking-wide text-blue-800 dark:text-blue-300">Welcome to ARCHIVES</p>
        <h2 class="mt-2 text-2xl font-bold">Find, submit, review, and preserve campus research</h2>
        <p class="mt-2 max-w-3xl text-sm text-gray-700 dark:text-gray-300">Choose the section for your role. The instructions on this page do not grant access to papers or actions that your account is not permitted to use.</p>
        <div class="mt-5 flex flex-wrap gap-2" aria-label="Guide sections">
            <a href="#getting-started" class="rounded-full bg-white px-3 py-2 text-sm font-semibold text-blue-800 shadow-sm dark:bg-gray-700 dark:text-blue-200">Getting started</a>
            <a href="#submitters" class="rounded-full bg-white px-3 py-2 text-sm font-semibold text-blue-800 shadow-sm dark:bg-gray-700 dark:text-blue-200">Students &amp; faculty</a>
            <a href="#reviewers" class="rounded-full bg-white px-3 py-2 text-sm font-semibold text-blue-800 shadow-sm dark:bg-gray-700 dark:text-blue-200">Reviewers</a>
            <a href="#search-download" class="rounded-full bg-white px-3 py-2 text-sm font-semibold text-blue-800 shadow-sm dark:bg-gray-700 dark:text-blue-200">Search &amp; download</a>
            <a href="#analytics" class="rounded-full bg-white px-3 py-2 text-sm font-semibold text-blue-800 shadow-sm dark:bg-gray-700 dark:text-blue-200">Reports &amp; analytics</a>
            @if($role === 'super_admin' || ($role === 'admin' && ! session('user_college_id')))
            <a href="#administration" class="rounded-full bg-white px-3 py-2 text-sm font-semibold text-blue-800 shadow-sm dark:bg-gray-700 dark:text-blue-200">Administration</a>
            @endif
        </div>
    </div>

    <section id="getting-started" class="scroll-mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-xl font-bold"><i class="fas fa-right-to-bracket mr-2 text-blue-600"></i>Getting started</h2>
        <ol class="mt-4 grid list-none gap-4 pl-0 md:grid-cols-2">
            <li class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900"><strong>1. Register.</strong> Select Register and complete the form with your campus details. Verify your email if asked. Wait for account approval when registration is pending.</li>
            <li class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900"><strong>2. Log in.</strong> Use your registered email and password. If you cannot sign in, use Forgot Password or contact your campus administrator.</li>
            <li class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900"><strong>3. Open your dashboard.</strong> The dashboard shows work and actions available to your current role. Use the navigation menu to open submissions, the archive, or this guide.</li>
            <li class="rounded-xl bg-gray-50 p-4 dark:bg-gray-900"><strong>4. Protect your account.</strong> Sign out on shared computers. ARCHIVES staff will not ask you to send your password.</li>
        </ol>
    </section>

    <section id="submitters" class="scroll-mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-xl font-bold"><i class="fas fa-file-arrow-up mr-2 text-blue-600"></i>Students and faculty: submit research</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Faculty members follow the same submission steps as students. If you cannot register as faculty, ask a super administrator to provision your account.</p>
        <ol class="mt-4 list-none space-y-3 pl-0">
            <li><strong>1. Start a submission.</strong> Sign in and choose <em>Archive Paper</em>. You may save a draft and return to it before submitting.</li>
            <li><strong>2. Enter accurate details.</strong> Provide the exact approved title, author names, abstract, keywords, college, academic program, and publication year. Select BS for a bachelor's degree or GS for graduate studies.</li>
            <li><strong>3. Choose the matching category.</strong> BS submissions may be Thesis or Capstone Project. GS submissions may be Thesis or Dissertation. The category list changes with the academic level, and the server rejects mismatched combinations.</li>
            <li><strong>4. Attach the document.</strong> Upload the correct final file and wait for the upload-complete message. Check that the document belongs to this submission and opens correctly.</li>
            <li><strong>5. Review before submitting.</strong> Check spelling, metadata, authors, category, year, and file. Submit when all information is correct; the system records the submission time and account.</li>
            <li><strong>6. Follow revisions.</strong> Open <em>My Submissions</em> to see the current stage, reviewer comments, decision history, and timestamps. Edit the requested items and resubmit as instructed.</li>
        </ol>
        <div class="mt-5 rounded-xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/40">
            <h3 class="font-semibold">Typical review path</h3>
            <p class="mt-2 text-sm">Submission <span aria-hidden="true">→</span> Adviser or college review (when applicable) <span aria-hidden="true">→</span> Campus Review Board <span aria-hidden="true">→</span> RDE review <span aria-hidden="true">→</span> Acceptance and publication.</p>
            <p class="mt-2 text-sm">Each available stage shows its date, time, responsible reviewer, and comments. Metadata edits do not replace the original workflow timestamps.</p>
        </div>
    </section>

    <section id="reviewers" class="scroll-mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-xl font-bold"><i class="fas fa-clipboard-check mr-2 text-blue-600"></i>Reviewers: evaluate submissions</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Only review papers assigned or routed to your role. Open the matching approvals page or dashboard queue; do not share restricted submissions outside the review process.</p>
        <ol class="mt-4 list-none space-y-3 pl-0">
            <li><strong>1. Open the queue.</strong> Advisers review assigned submissions. College reviewers use College Approvals. Board members use Campus Review Board. RDE personnel use RDE Approvals.</li>
            <li><strong>2. Check the paper and metadata.</strong> Review the title, authors, academic level, matching category, program, academic content, and attached file. Adviser recommendations do not replace formal college or board decisions.</li>
            <li><strong>3. Record a decision.</strong> Approve when requirements are met, request revisions with specific actionable comments, or reject with a clear reason. Confirm before submitting the decision.</li>
            <li><strong>4. Respect stage order.</strong> The Campus Review Board must approve a paper before RDE can grant final approval. Only fully approved papers can be published.</li>
            <li><strong>5. Check the audit history.</strong> The paper details page records decisions, comments, responsible accounts, and timestamps.</li>
        </ol>
        @if(in_array($role, ['admin', 'super_admin'], true))
        <p class="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-900 dark:bg-amber-950/40 dark:text-amber-100">Authorized administrators can open Classification Review to correct legacy records with missing or inconsistent academic-level/category metadata. Confirm the correct values before saving; each correction is added to the audit history.</p>
        @endif
        <div class="mt-5 grid gap-3 md:grid-cols-3">
            <div class="rounded-xl bg-emerald-50 p-4 dark:bg-emerald-950/40"><strong>Approve</strong><p class="mt-1 text-sm">Record that the paper meets this stage’s requirements.</p></div>
            <div class="rounded-xl bg-amber-50 p-4 dark:bg-amber-950/40"><strong>Return</strong><p class="mt-1 text-sm">List the exact corrections needed and the stage to revisit.</p></div>
            <div class="rounded-xl bg-red-50 p-4 dark:bg-red-950/40"><strong>Reject</strong><p class="mt-1 text-sm">Explain the decision in the reason field before submitting.</p></div>
        </div>
    </section>

    <section id="journal-issues" class="scroll-mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-xl font-bold"><i class="fas fa-book-open mr-2 text-blue-600"></i>RDE personnel: manage journal issues</h2>
        <ol class="mt-4 list-none space-y-3 pl-0">
            <li><strong>1. Open Journal Issues.</strong> Create a draft with journal title, volume, issue, publication year, and publication date.</li>
            <li><strong>2. Assign eligible papers.</strong> Select accepted or published research that belongs in the issue. Check the assigned-paper list before saving.</li>
            <li><strong>3. Review and finalize.</strong> Edit the issue while it is a draft. Confirm the contents before finalizing; finalized issues require the controlled revision process.</li>
            <li><strong>4. Browse the issue.</strong> View papers grouped by journal, volume, issue, and year. Finalized issue details appear on eligible research details and PDF reports.</li>
        </ol>
    </section>

    <section id="search-download" class="scroll-mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-xl font-bold"><i class="fas fa-magnifying-glass mr-2 text-blue-600"></i>Search, view, and download research</h2>
        <ol class="mt-4 list-none space-y-3 pl-0">
            <li><strong>1. Search the archive.</strong> Filter by title, abstract, keywords, college, academic level, category, year, or status where available. Use saved searches to reuse filters.</li>
            <li><strong>2. Open a record.</strong> Select View Research to see its metadata, approved public content, journal details, and permitted workflow history.</li>
            <li><strong>3. Request restricted access.</strong> If a download requires approval, use Request Download and wait for the decision. The file is available after approval, subject to your access rights.</li>
            <li><strong>4. Download several reports.</strong> Select the papers and choose Download selected. A ZIP contains generated research PDFs. Unauthorized selections are excluded and listed in the archive notice. Limits are enforced by the server.</li>
            <li><strong>5. Check your requests.</strong> Use My Requests to see request decisions. A download never grants access to unrelated private papers.</li>
        </ol>
    </section>

    <section id="analytics" class="scroll-mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <h2 class="text-xl font-bold"><i class="fas fa-chart-column mr-2 text-blue-600"></i>Reports and analytics</h2>
        <ol class="mt-4 list-none space-y-3 pl-0">
            <li><strong>1. Open your dashboard.</strong> Statistics and paper lists are limited to your role and permitted records.</li>
            <li><strong>2. Filter the results.</strong> Use available date, college, category, year, and status filters. Date ranges on general dashboards refer to submission dates; RDE acceptance/publication metrics use those lifecycle dates.</li>
            <li><strong>3. Export when allowed.</strong> Choose CSV export for the records you are authorized to see. Keep exported files secure and use them only for approved work.</li>
            <li><strong>4. Print reports.</strong> Authorized administrators can use the archive print view. A finalized research PDF includes applicable lifecycle and journal information.</li>
        </ol>
    </section>

    @if($role === 'super_admin' || ($role === 'admin' && ! session('user_college_id')))
    <section id="administration" class="scroll-mt-6 rounded-2xl border border-orange-200 bg-white p-6 shadow-sm dark:border-orange-900 dark:bg-gray-800">
        <h2 class="text-xl font-bold"><i class="fas fa-user-shield mr-2 text-orange-600"></i>Authorized administrators: accounts and settings</h2>
        <ol class="mt-4 list-none space-y-3 pl-0">
            <li><strong>1. Account requests.</strong> Open Pending Registrations, verify the request, and approve or reject it. Super administrators can manage users and provision adviser or faculty accounts.</li>
            <li><strong>2. Campus setup.</strong> Authorized administrators can maintain colleges, categories, research thrusts, and campus-wide workflow records according to their permissions.</li>
            <li><strong>3. Dashboard settings.</strong> Super administrators can control AI features and set bulk-download limits. Changes are recorded in system activity.</li>
            <li><strong>4. Review logs.</strong> Use Admin Logs to investigate recorded administrative actions. Do not share log exports or private research details unnecessarily.</li>
        </ol>
    </section>
    @endif

    @if($role === 'super_admin')
    <section id="backup-restore" class="scroll-mt-6 rounded-2xl border border-red-200 bg-white p-6 shadow-sm dark:border-red-900 dark:bg-gray-800">
        <h2 class="text-xl font-bold"><i class="fas fa-database mr-2 text-red-600"></i>Super administrators: backup and restore</h2>
        <ol class="mt-4 list-none space-y-3 pl-0">
            <li><strong>Create a backup.</strong> Open Backup &amp; Restore and choose Create Backup. The archive contains database records, workflow history, settings, and uploaded files, encrypted with the current Laravel APP_KEY.</li>
            <li><strong>Download safely.</strong> Backups are stored outside public document access. Download them only to an approved secure location. Retain the matching APP_KEY separately; the backup cannot be decrypted without it.</li>
            <li><strong>Automatic backups.</strong> The application schedules a daily backup at 02:00 server time. The deployment must run Laravel’s scheduler (for example, `php artisan schedule:work` or a once-per-minute `schedule:run` cron task).</li>
            <li><strong>Restore carefully.</strong> First verify the intended installation and version. Select a trusted ARCHIVES backup, upload it, and type RESTORE exactly. The system checks encryption, database driver, migration history, and schema, then creates a safety backup before replacement.</li>
            <li><strong>After restoring.</strong> Verify representative accounts, documents, settings, and workflow records. Keep the safety backup until verification is complete. Restore only backups from a trusted source and the same APP_KEY.</li>
        </ol>
        <p class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-900 dark:bg-red-950/40 dark:text-red-200">A restore replaces current database records and research files. Do not use it as a test on the live system. Test the full procedure in a separate development environment first.</p>
        @if($activities->isNotEmpty())
        <div class="mt-6 overflow-x-auto">
            <h3 class="mb-3 font-semibold">Recent backup and restore activity</h3>
            <table class="w-full min-w-[36rem] text-left text-sm">
                <thead><tr class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-700"><th class="py-2 pr-3">Date and time</th><th class="py-2 pr-3">Action</th><th class="py-2 pr-3">Administrator</th><th class="py-2">Result</th></tr></thead>
                <tbody>
                    @foreach($activities as $activity)
                    <tr class="border-b border-gray-100 dark:border-gray-700">
                        <td class="py-2 pr-3">{{ $activity->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="py-2 pr-3">{{ str_replace('.', ' ', $activity->action) }}</td>
                        <td class="py-2 pr-3">{{ $activity->user?->name ?? ($activity->role === 'system' ? 'Scheduled task' : 'Unknown') }}</td>
                        <td class="py-2">{{ $activity->response_status >= 400 ? 'Failed' : 'Completed' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </section>
    @endif

    <p class="text-center text-sm text-gray-500 dark:text-gray-400">Need help? Contact your campus administrator or RDE office.</p>
</div>
@endsection
