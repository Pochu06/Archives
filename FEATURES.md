# ARCHIVES Features

ARCHIVES (Academic Research Collection Harnessing Intelligence for Virtualized Educational Storage) is a centralized repository for collecting, reviewing, organizing, and discovering academic research. It supports the complete lifecycle of a research record, from drafting and submission to approval, publication, controlled access, and long-term retrieval.

## 1. User Roles and Access

ARCHIVES uses authenticated, role-based access to keep responsibilities separate:

- **Student**: Creates research records, saves drafts, submits work for review, responds to revision requests, follows submission status, and requests access to restricted downloads.
- **Adviser**: Supports research monitoring and student-related review activities where assigned by the system.
- **College Admin**: Reviews submissions belonging to the administrator's college and performs the first approval, rejection, or revision decision.
- **RDE Reviewer/Admin**: Performs the institution-level review after college approval and manages download requests that require authorization.
- **Super Admin**: Manages users and research organization data, controls system-wide AI availability, views administrative audit logs, and oversees the platform.

Public visitors can browse the public research showcase and author pages. Actions that create, modify, review, or download protected content require authentication and the appropriate permission.

## 2. Authentication and Profile Management

The application provides login, registration, and logout flows backed by Laravel session authentication. Registration creates an account that can be associated with a college and student information where applicable.

Authenticated users can update their profile information, including personal details, student ID, and college assignment. User management screens allow authorized administrators to create, view, edit, and remove accounts while assigning the appropriate role.

## 3. Role-Based Dashboards

The dashboard changes according to the signed-in user's role instead of presenting one generic workspace:

- Students see their research, drafts, submission activity, and notifications.
- College reviewers see the college submission queue and pending college decisions.
- RDE reviewers see the institution-level review queue.
- Super Admins see system-level summaries, governance tools, and configuration controls.

This keeps the most relevant actions visible and limits administrative controls to users who need them.

## 4. Research Creation and Metadata

Students can create structured research records using an IMRAD-oriented form. A record can contain:

- Title and abstract
- Authors and author attribution
- Keywords
- College and research category
- Publication year
- Introduction
- Methodology
- Results and discussion
- Conclusion and recommendations
- References and supporting content
- Research thrust assignments
- Research document and supporting images

The structured metadata improves search, attribution, filtering, and future preservation. Research content can also include custom table design information so formatted tables remain part of the saved document structure.

## 5. Draft Autosave and Resumable Uploads

Research creation supports drafts so unfinished work does not need to be submitted immediately. Draft data can be saved, loaded when the user returns, and deleted when it is no longer needed.

Large research files use chunked upload endpoints. The upload can be resumed by checking which chunks are already present, then completing the file once all chunks have arrived. This reduces the chance that a long upload must restart because of a temporary connection or browser interruption.

## 6. Duplicate Detection

Before submission, the system can compare a proposed title and abstract with existing research records. It calculates a similarity score and returns possible matches as a warning. This helps authors discover related work and helps reduce accidental duplicate submissions without silently blocking the author.

## 7. Multi-Level Submission Workflow

Research moves through a controlled review process rather than becoming public as soon as it is uploaded:

1. A student submits a research record for college review.
2. The College Admin approves it, rejects it, or requests revisions.
3. A college-approved record moves to RDE review.
4. The RDE reviewer approves it for publication, rejects it, or requests revisions.
5. An approved record becomes available through the appropriate archive and public views.

The workflow distinguishes states such as `pending_college`, `pending_rde`, `revision_college`, `revision_rde`, `approved`, `rejected_college`, and `rejected_rde`. These states make it clear who currently owns the next decision.

### Revision Requests

Reviewers can request changes to specific fields instead of returning an unexplained general rejection. Revision areas can include the title, authors, keywords, abstract, introduction, methodology, results, references, conclusion, and recommendations. Review notes are stored with the selected fields so the student has actionable feedback.

### Bulk Review Actions

Authorized reviewers can apply an approval, rejection, or revision action to multiple submissions when the same decision applies to a group. This is useful for queue management while retaining the normal permission checks and status tracking.

### Submission Timeline

Status changes are recorded as timeline events with the action, previous and next status, actor, timestamp, notes, and supporting metadata. Students can follow the history of their own submissions, while reviewers can use the timeline to understand how a record reached its current state.

## 8. Research Search and Discovery

The authenticated archive provides search and filters for finding research by:

- Title or abstract text
- College
- Category
- Publication year
- Submission status
- Research thrust and related metadata

The search page also provides smart presets for common views such as recently published work, approved work from the current year, pending review, and records needing revision.

### Saved Searches

Users can save a combination of filters under a custom name, apply it later, or delete it. Saved searches are personal shortcuts that make recurring research or review tasks faster without requiring users to rebuild the same filter set.

### Public Showcase

Unauthenticated visitors can browse the public research showcase. The landing experience highlights selected research, trending records, and highly downloaded work. Public detail pages expose approved research intended for discovery while protected operations remain behind authorization.

### Author Profiles

Author pages collect a researcher's published work in one place. Visitors can review an author's profile information and browse the associated publications, which improves attribution and makes related work easier to find.

### Topic and Thrust Suggestions

The system provides topic suggestions based on archive filters, gaps, and trends. During research creation, thrust suggestions can also be generated from the research content so authors can select a relevant strategic research area.

## 9. Controlled Downloads and Document Preview

Research access is separated into viewing and downloading. Authorized users can preview research in the browser without immediately downloading a file.

For protected records, a user submits a download request with a stated purpose. Reviewers can approve or reject the request, and users can view the status of their own requests. Approved downloads are generated as watermarked PDFs, adding traceability to distributed documents.

Approved research owners can also generate a research certificate when the record meets the required approval conditions.

## 10. Notifications

The notification center gives authenticated users a single place to see important events, including:

- Submission approvals, rejections, and revision requests
- Download request decisions
- Completion of AI-generated summaries or related research
- Draft-related reminders
- Relevant administrative actions

Notifications can be marked individually or all at once as read. The user model also contains notification digest settings for optional daily or weekly summaries where email delivery is configured.

## 11. AI-Assisted Features

AI capabilities are optional and controlled by a global Super Admin setting. The application integrates with a local Ollama service, so AI behavior depends on the configured model and service availability. When AI is disabled or unavailable, non-AI archive functions remain usable and supported fallback behavior can be used where implemented.

### Archive Chatbot

The archive chatbot helps users navigate the system, discover research, and answer basic research-writing questions. It keeps a bounded conversation history and can include relevant archive records in its responses. A reset action clears the current conversation.

### Research Summaries

The system can generate a concise summary for an approved research record. Summary generation runs through a background job, and the result is cached on the research record or related storage so the same work does not need to be processed repeatedly. A notification can inform users when processing is complete.

### Related Research

Related research recommendations use the paper's keywords and content to identify similar records. AI-assisted matching is supported by fallback matching based on title, abstract, or extracted terms. Generation is queued and cached for efficient display.

### AI Topic and Thrust Assistance

AI can help suggest possible research topics and recommend suitable thrust labels from the supplied research context. These suggestions assist the author but do not replace the author's or reviewer's final decision.

## 12. Archive Organization

Super Admins can maintain the controlled vocabulary used throughout the archive:

- **Colleges** group authors and research records by academic unit.
- **Categories** classify the subject or type of research.
- **Thrusts** represent strategic research focus areas and include descriptions, keywords, and an active/inactive state.

Maintaining these entities centrally keeps filters, forms, reports, and public discovery pages consistent.

## 13. Administration and Governance

Administrative actions are recorded in an audit trail. Entries can capture the acting user, role, action, HTTP method, route, path, IP address, user agent, request data, response status, and timestamp. Super Admins can search and inspect these logs for accountability and troubleshooting.

System settings use a key-value configuration model for values that need to be managed without hard-coding them into views or controllers. The AI feature toggle is one example of a system-wide setting exposed to authorized administrators.

## 14. Help and Submission Guidance

An in-application tutorial explains the research submission process and the expected IMRAD-style structure. It gives users a starting point for preparing metadata and research sections before they enter the review workflow.

## 15. Current Scope and Deferred Enhancements

The following items are identified in the project backlog and should be treated as planned work unless their implementation has been completed separately:

- Citation export in APA, MLA, IEEE, and BibTeX formats
- Download and view analytics by paper, college, and topic
- Versioned submissions with side-by-side comparison
- Soft-delete restoration bin for research records
- CSV/Excel data export center
- Queue and background-job monitoring dashboard
- Time-limited access links for restricted documents

The implemented feature set described above is the current product behavior; backlog items should not be presented to users as available functionality until their routes, business logic, and interface are complete.
