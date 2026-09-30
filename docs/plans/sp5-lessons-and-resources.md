# SP5-01 lessons and private resources

## 30 September 2026 — Approved teacher review and feedback slice

The owner approved the [school trial checkpoint](school-platform-implementation.md#30-september-2026--school-trial-mvp-checkpoint). Teacher response review, private feedback drafts, optional whole-number scores, explicit release and learner-visible released feedback are implemented. The original SP5-01 boundary below remains historical scope for lessons/resources; this new slice extends the existing text assignment/submission workflow.

An additive review table stores one review per final submitted response. School/assignment policy checks run on every staff read and save/release; assigned teachers and active school administrators are eligible. Score/maximum must be supplied together, maximum is positive, and score is between zero and maximum; both are capped at 1,000,000. Feedback is required, trimmed and capped at 10,000 characters. Saving never releases. Learners see only released reviews; no review is awaiting review, zero is a real score, and feedback-only reviews have no score. Submitted text and released reviews are immutable. Audit events exclude response/feedback text.

Acceptance covers save/edit/release, hidden drafts, score bounds, release replay, release of unsaved changes, role/school/revocation boundaries, inactive learner access, draft-response denial and escaped learner rendering. Independent PostgreSQL workers releasing the same review return the same row and produce one release event. Synthetic Chrome completed learner draft/final submission and teacher draft/release at 390px and 1024px with no horizontal overflow or page errors. Final full-suite results are recorded in the implementation log.

PostgreSQL verification also exposed generated constraint-name collisions in the uncommitted recipient/submission create migrations; short explicit unique names preserve the same integrity rules and permit fresh PostgreSQL installs. Browser verification exposed an invalid synthetic learner login ID; the demo seeder now uses a valid 12-character ID and refreshes an existing legacy demo account rather than replacing it. No production data was changed.

Formal school reports, feedback amendments, rubrics, attachments, extensions, resubmissions and intended-host operating validation remain open.


Date: 29 September 2026. Status: the SP5-01 application slice is implemented and locally verified under the owner-approved scope, operating baseline, and schema/state design. Focused PostgreSQL concurrency checks, synthetic Chrome acceptance, and a real ClamAV scan-job check pass locally. Intended-host validation of ClamAV and private storage/queue/scheduler operations remains outstanding. Until the intended host has a validated scanner, files must remain unavailable to learners and publication must fail closed.

## Purpose and release boundary

Define the first school learning slice: a teacher can prepare and publish an ordered set of lessons and private file resources for an authorized school teaching assignment, and eligible learners can read or download the published material. This implements the first part of NS09 and establishes the publication boundary needed by later SP5 assignment/submission work.

This slice does not include assignments, submissions, quizzes, marking, formal assessment, guardian progress summaries, independent catalogue sales, AI, text extraction, or live content licensing. Existing personal quizzes and private AI documents remain separate and unchanged.

## Requirements and dependencies

- NS09: teacher-authored courses, lessons and authorized resources.
- NS17 and NQ01: audited access boundaries, current membership/assignment checks, school isolation.
- NQ05: clear draft/published/withdrawn states and usable learner access.
- Depends on SP1 school context and roles, SP2 subjects/classes/terms/teaching assignments and dated class placements, and the permanent school Filament workspace.
- No new package is proposed.

Use the existing Laravel services, Form Requests, policies, school context and Filament 5 patterns. Use the [case-study findings](../case-study-code-findings.md) and [case-study-informed sequence](case-study-informed-implementation.md) for page-journey comparisons only. Do not copy reference code or adopt another school's grading/content rules.

## Approved behavior

1. An assigned teacher opens the school learning workspace and creates or selects a course associated with one active school teaching assignment (school, teacher, subject, class and term). Course ownership and teacher authorization come from the current school and assignment, not submitted IDs. This first slice does not create a cross-class course catalogue.
2. The teacher creates ordered lessons with a title and authored text, saves drafts, and previews them as a learner would see them. Drafts are not visible to learners.
3. Publishing creates an immutable lesson version. Later edits create a new draft/version; they do not rewrite the published version. An authorized withdrawal prevents future learner access while preserving the audit/history record.
4. Eligible learners can view only published, non-withdrawn lessons for an active class placement and school assignment that they are currently authorized to access. Re-check the learner, school, placement and publication state on every request.
5. The teacher may attach private resources to a lesson draft. Store them under generated opaque keys on private storage, associate each file with the immutable lesson version, and record uploader, safe display name, detected media type, size, checksum, rights basis, and scan status. Never accept a client-selected storage path.
6. Accepted upload baseline (28 September 2026): PDF, DOCX, TXT, and JPEG/PNG images re-encoded by the server; maximum 10 MB per file. Reject unsupported/active formats, user-supplied archives, malformed, encrypted or unscannable documents, and files that fail validation or malware scanning. Text extraction and OCR are out of scope. A resource must be validated and scan-clean before its lesson can be published.
7. Serve each download through a controller that rechecks current authorization and resource state for every request. An assigned teacher may preview clean resources attached to their own draft; learners may read/download only resources on a published, non-withdrawn version and current eligible class placement. Do not expose public object URLs or reusable storage URLs. Default documents to attachment downloads; only sanitized image types may be rendered inline.
8. Record the resource's school-use rights basis and uploader in the school's audit trail without logging file contents or storage URLs. School-authorized materials remain separate from the independent catalogue; no resource is sold or reused across schools without separately verified rights.

The file types and 10 MB per-file ceiling are accepted for the initial implementation baseline; confirm against pilot samples before real school materials are used. Keep uploads private and unavailable to learners until validation and scanning succeed; fail closed if scanning is unavailable. Do not add an upload, conversion, preview, or scanning dependency without approval. No resource is processed by Tika, embeddings, AI, or another text-extraction path.

## Implemented touchpoints (verification pending)

The implementation adds additive migrations and models for courses, lessons, immutable lesson versions, and private resource metadata/state; focused services under `app/Services/Schools`; quarantine validation and ClamAV scanning; policies; a native page under `app/Filament/School`; and learner-facing views/routes following managed-learner authorization. It reuses `TeachingAssignment`, dated class placements, school audit events, and tenant context. Private classroom files do not use personal-document ownership or AI extraction.

The active files are in those Laravel areas, the learner routes/views, additive migrations, and the documentation listed in the implementation log. Focused PHPUnit coverage is in `tests/Feature/SchoolCoursePolicyTest.php`, `tests/Feature/LearnerSchoolCourseControllerTest.php`, `tests/Feature/SchoolLessonResourceLifecycleTest.php`, and `tests/Feature/FilamentSchoolLearningTest.php`.

## Approved schema and state design

Owner approved this schema/state design on 28 September 2026. Additive migrations and constraints have been exercised through the focused PostgreSQL test migrations. Cross-connection locking/concurrency behavior remains unverified.

| Table | Proposed columns and relationships | State/invariants |
| --- | --- | --- |
| `school_courses` | `id`, `school_id`, `teaching_assignment_id`, `created_by_user_id`, `title`, timestamps | One course per teaching assignment; same-school assignment and active assigned teacher required at create/update/publish. Unique `teaching_assignment_id`; school and assignment must agree. |
| `school_lessons` | `id`, `school_course_id`, `position`, timestamps | Stable ordered lesson identity. Unique course/position; reorder is an authorized course operation. |
| `school_lesson_versions` | `id`, `school_lesson_id`, `version_number`, `title`, `body`, `status`, `created_by_user_id`, `published_at`, `withdrawn_at`, timestamps | Statuses: `draft`, `published`, `superseded`, `withdrawn`. Unique lesson/version. Draft is editable; published content is immutable. A transaction locking the parent lesson allows at most one current `published` version; publishing a new version supersedes the prior one, and withdrawal removes the learner-visible version while keeping history. |
| `school_lesson_resources` | `id`, `school_id`, `school_lesson_version_id`, `uploaded_by_user_id`, `display_name`, `storage_disk`, opaque `storage_key`, detected `media_type`, `byte_size`, `sha256`, `status`, `rights_basis`, nullable `rights_reference`, `rights_attested_at`, `validated_at`, `scanned_at`, scanner/signature metadata, `purge_after`, `bytes_purged_at`, timestamps | Statuses: `quarantined`, `validating`, `scan_pending`, `clean`, `rejected`, `purged`. Only `clean` resources on the current published version are learner-downloadable. Store bytes only on a private disk; never persist a client path or public URL. Rights bases: `educator_created`, `school_owned`, `licensed`, `permission_granted`. |

Keep `school_id` on resource rows to support bounded quota queries and explicitly validate it matches the course/assignment school. Where practical, add composite unique/foreign constraints for same-school relationships; retain service and policy checks even when constraints exist. Use existing `audit_events` for upload, publish, withdrawal, rejection, and purge events; metadata must exclude content, storage keys, and signed URLs.

State flow: upload to private quarantine → validate type/content/size → `scan_pending` → `clean` or `rejected`. Scanner outage leaves the resource pending and blocks lesson publication/download. A rejected object is removed promptly; clean resources are retained with active/superseded course versions. On withdrawal or deletion, deny access immediately and set `purge_after` to 90 days; purge object bytes after that period and retain the required metadata/history. Count retained objects (including quarantine and superseded versions still inside retention) toward both the 500-resource cap and 2 GiB quota; warn at 80% of either limit; purged bytes no longer count. Serialize quota reservation/accounting with a lock on the stable school row and reconcile failed object-store writes/deletions.

Publication and withdrawal update the version state and audit event in one database transaction. Storage operations remain outside long database transactions and use an idempotent state transition/cleanup path so retries cannot publish missing or unscanned bytes. Downloads resolve the resource through its version, lesson, course and assignment, then recheck current membership, placement and publication state on every request.

Implementation details fixed by approval: one course per teaching assignment; superseded versions are retained but learner-inaccessible (teacher access is through authorized course history); and quarantined objects count toward the resource cap while bytes are stored. Rejected bytes are removed promptly and no longer count after confirmed deletion. Keep these invariants unless a new owner decision changes them.

## Acceptance criteria

- An active assigned teacher can save and preview a draft; an unassigned teacher, teacher from another school, or inactive/revoked membership cannot create, publish, edit, withdraw, or access another assignment's content.
- A published version is readable by an eligible learner only through the learner's authenticated account and active dated class placement. Foreign-school, inactive, transferred, or unrelated learner IDs reveal no content.
- Draft content is never returned to learner routes. Withdrawn lessons and resources are denied on subsequent requests. A later draft cannot silently alter a previously published version.
- An authorized teacher can upload an allowlisted resource into private quarantine; invalid, oversized, unsupported, malicious, and cross-school files are rejected. Resource metadata and rights basis are bound to the correct school and lesson version.
- A lesson cannot be published while an attached resource is pending validation/scanning or has been rejected. Only scan-clean published-version resources can be downloaded.
- A teacher's draft preview is available only to the currently assigned teacher and only for resources that passed validation/scanning; it does not expose a learner URL or grant learner access.
- Private resource download rechecks school, lesson, version, current learner/teacher authorization, and resource state on every request; guessed storage paths and foreign IDs are denied. No public or reusable URL bypasses authorization, and a transfer, membership revocation, withdrawal, or resource quarantine blocks the next request.
- Storage allowance is checked transactionally against the approved per-school quota; upload/withdrawal/deletion transitions retain enough metadata for audit and configured retention without logging content or paths.
- Important publication and withdrawal transitions produce minimal school audit events. No provider call, embedding, extraction, grading, or personal subscription is involved.
- Focused feature coverage exercises permitted teacher and learner journeys, revoked membership, wrong assignment/school, draft denial, version preservation, withdrawal and resource-download denial. PostgreSQL feature coverage and an independent-connection race at the 500-resource quota boundary pass locally. Synthetic Chrome verifies teacher course/draft/preview actions, managed-learner course/lesson access, and private attachment download. These local checks do not establish intended-host storage, queue, scheduler, backup or recovery behavior.

## Decisions and operational prerequisites

| Decision | Proposal to review | Input needed |
| --- | --- | --- |
| Course grouping | One course per active `TeachingAssignment`; no cross-class catalogue in this slice | Accepted 28 September 2026 |
| Publication review | The assigned teacher publishes directly; a school review gate is deferred unless the pilot requires it | Accepted 28 September 2026 |
| Learner eligibility | Re-resolve active dated placement, school membership, assignment and publication state at every read/download; do not snapshot lesson-only readers | Accepted 28 September 2026 |
| First-release content | Authored text plus private lesson uploads; no assignments/submissions, independent catalogue, extraction, or AI in this slice | Private uploads accepted 28 September 2026; boundary as above |
| Resource types and per-file limit | PDF/DOCX/TXT and sanitized JPEG/PNG, 10 MB per file | Accepted 28 September 2026; validate with pilot samples before real materials |
| Resource count and per-school quota | Maximum 500 active resources and 2 GiB stored per school; warn at 80%, block new uploads at the hard limit, and count retained versions toward storage | Owner-confirmed 28 September 2026; validate operational accounting with school IT during implementation |
| Malware scanning | Use ClamAV `clamd` behind a private service/socket, if the hosting environment supports it. Keep content quarantined until clean, fail closed, and never expose unauthenticated TCP publicly. Provide signature updates, monitoring and recovery | Owner-confirmed 28 September 2026; school IT/hosting validation remains a prerequisite to scanner integration |
| Content rights | Require uploader attestation and record whether material is educator-created, school-owned, licensed, or shared with permission; record source/license reference where applicable. Never infer resale/other-school rights | Owner-confirmed 28 September 2026; validate any school-specific policy before real materials are used |
| Retention and deletion | Keep active and superseded versions while the course is active; after withdrawal/deletion provide a 90-day recovery window then purge file bytes, preserving history metadata. Align backup expiry and school exit/export behavior | Owner-confirmed 28 September 2026; verify backup/exit behavior before production use |
| Withdrawal history | Preserve published versions and audit history while denying future learner access and downloads immediately | Accepted 28 September 2026 |

## Verification and rollout

Use existing PHPUnit conventions and factories. Focused PostgreSQL 17 verification passed on 29 September 2026 (19 tests, 97 assertions); SQLite passed the 18 database-agnostic tests (91 assertions), with the PostgreSQL-only race skipped. Coverage includes policy boundaries, learner visibility/download, course/version lifecycle, upload validation/quota, scanner states, purge retention and Filament teacher actions. A separate connection race confirmed that competing uploads cannot exceed 500 retained resources. Synthetic Chrome verified the teacher course/draft/preview flow and managed-learner lesson/private-download flow. A temporary ClamAV service passed clean and EICAR scan-job transitions, including deletion of infected bytes. Pint, route registration, schedule registration, view caching and frontend build also passed. Use synthetic accounts only; do not use real learner data or school materials without an agreed rights and privacy basis. Intended-host ClamAV, storage, queue, scheduler, backups and recovery remain open release gates.

ClamAV is the selected scanner, conditional on validating that the intended hosting environment can run and maintain the private service. The local temporary service was removed after validation; the app's regular configuration still has no scanner socket. Until intended-host validation is complete, the implementation must remain fail-closed and cannot be declared ready for real school uploads.

## Current implementation status

Application code and additive schema are present for course/lesson/version management, private uploads, content validation, quota enforcement and warning, scan queue/retry, learner/staff downloads, publishing/withdrawal, and 90-day byte purging. The configured filesystem `local` disk maps to `storage/app/private`; uploads use only this non-public disk. Focused PostgreSQL 17 and SQLite results are recorded above; the independent-connection quota race passed. Synthetic Chrome acceptance and actual scanner/job state transitions passed using a temporary local ClamAV service. The app's regular `CLAMAV_SOCKET` remains unset, so real scanning and learner release are not available in this workspace outside that temporary check. Intended-host private-storage/queue/scheduler setup, backup/restore and monitoring remain unverified.
