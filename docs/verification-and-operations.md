# Verification and operations plan

Updated 24 September 2026. Status: school release requirements and operational procedures remain proposed; the SP0 AI-boundary slice, SP4 fee-posting concurrency, receipt/allocation work and permanent Filament workspace routing have local verification. Synthetic Chrome checks cover core native attendance, communications, overview, learner, academic and fee actions, including trusted Enter activation of attendance register opening against isolated Docker PostgreSQL. After removing the panel-off switch, the full current-worktree PostgreSQL suite passed (222 tests, 976 assertions); SQLite passed (220 tests, 961 assertions; 2 PostgreSQL-only skips). A synthetic three-school PostgreSQL check with 600 learners per school recorded 151–180 ms batch-post durations and 189–219 ms waits for one competing roster write; the local NQ03 diagnostic completed 270 mixed requests with 2.33 s combined p95 and zero failures after tenant-scoped pagination. Hosted capacity remains unverified. GitHub CI is configured for both databases, but its latest observed run failed in both PHP test steps on 22 September 2026 (SHA `4b0ecbf`); job logs were unavailable, and no hosted run of the current worktree is claimed. The school-row lock serializes fee posting with same-school roster writes. Hosted NQ03 capacity remains open. Filament is permanent for platform administrators and eligible school staff; there is no panel-off switch. The NS/NQ requirements and SP0–SP9 plan take precedence over the historical adult-AI checks retained below.

## Local browser demo setup

On the local Docker environment, run `php artisan db:seed --force --no-interaction` to create or refresh the synthetic demo school and role accounts. The seeder is guarded to the `local` environment and uses `NOVA_DEMO_PASSWORD` when present, so the password is not committed to project documentation. Use the landing page's **Open local demo** link, sign in as the seeded school administrator, and begin from the dashboard workspace links.

## Active verification matrix

| Boundary | Required evidence | Gate |
| --- | --- | --- |
| AI deferral | School/managed-learner routes and delayed jobs cannot dispatch AI when disabled; no lost legacy records/reservations | SP0; every release smoke test |
| School isolation | Two schools, multiple roles, guardian across schools, revoked staff; deny cross-school lists, bound IDs, files, exports, cache and jobs | SP1 and every module; FI-08 synthetic Chrome check confirms tenant switch, separate concurrent tabs and 404 for a foreign import-batch ID. Broader role/resource/export/job isolation remains covered by feature tests and pending module gates |
| Registry and identity | Managed learner without email; verified guardian; no privilege inheritance; replayed import, duplicate names/numbers, transfer and account deactivation | SP2; synthetic Chrome covers import/replay and, on 24 September, native learner admission and guardian linking. Cross-school/two-tab browser evidence exists for imports; broader lifecycle, export and account-deactivation tasks remain open |
| Attendance and messaging | Unmarked vs absent, concurrent edits, authorized correction, duplicate notices, recipient revocation and stale status before send | SP3/SP7; synthetic Chrome verified admin register save, teacher correction blocked without a reason then saved with a reason, teacher communications denial, guardian corrected attendance visibility, admin draft/send and one linked-guardian delivery. At 390px, native overview/registry/detail/academic/attendance/fees/communications pages had no horizontal overflow. On 24 September, trusted Chrome Tab/ArrowDown/Enter input opened the teacher's roster and showed the success notice against isolated PostgreSQL; opening persisted no session, entries or audit events. Email/SMS, retries, read state and alerts remain SP3 work |
| Fee integrity | Preview hash freshness, charge batch replay/key-scope, sibling/partial allocation, unallocated credit, refunds/reversal, locked period, posting during a roster write and simultaneous allocation | SP4; prior SQLite suite passed (221 tests; 2 PostgreSQL-only skips); latest current-worktree PostgreSQL suite passed (222 tests). The direct fee-panel denial matrix now covers teacher and bursar roles. PostgreSQL races verify stale-preview rejection during concurrent admission and single-winner receipt allocation. Synthetic Chrome acceptance covers native schedule/preview/post/receipt/partial-allocation actions and 390px layout. A focused 600-learner/school benchmark recorded batch and one-writer lock timings; NQ03 mixed-load performance, reversal/other ledger flows, hosted CI and live cutover remain open; no unexplained reconciliation variance |
| Learning | Versioned publishing, recipient cohort, private file rights, due/cutoff/extension, retry and concurrent submission, hidden keys, pending manual marks and released feedback | SP5; works without AI credentials |
| School reports | Approved scale/weights, absent/exempt/unassessed/zero, moderation, publication snapshot and amendment; parent early-access denial | SP6 |
| Payments/cover | Wrong merchant/amount, forged reference, duplicate/missing/late callback, verification failure, renewal/refund and overlapping school/personal grants | SP7/SP9 |
| Boarding | Bed conflict, roll-call discrepancies, cancelled leave, approval vs release, actual return, overdue escalation, outage and audited back-entry | SP8 with school staff |
| Exit and retention | Authorized full export; no other-school/private personal material; login deletion preserves required school records; grace/exit policy applied | All modules; final R1–R3 gate |

Use existing PHPUnit feature tests/factories and established conventions. Test observable behavior; avoid assertions that simply repeat implementation declarations. Use fakes for email, notifications, storage, queues and external HTTP with deliberate assertions. Use real isolated PostgreSQL records for transactional invariants; separate processes/connections where necessary to expose races. Never refresh/reset the development or production school database as a test fixture.

Candidate feature-test groups: `SchoolIsolationTest`, `SchoolInvitationTest`, `LearnerGuardianAccessTest`, `LearnerImportTest`, `AttendanceTest`, `SchoolFeeLedgerTest`, `SchoolAssignmentTest`, `SchoolAssessmentPublicationTest`, `SchoolContractAccessTest`, `BoardingOperationsTest`, `IndependentLearningAccessTest`. Names are planned, not created. Keep relevant adult auth, entitlement, documents and quizzes regression coverage when touching reused code. Production-quality claims require evidence beyond earlier alpha test counts.

## Active operational and performance gates

Proposed initial workload: three schools of 600 learners, 30 simultaneous staff actions and 60 active learner sessions, declared endpoint mix and representative files. The local NQ03 diagnostic measured 2.33 s combined p95 with zero failures after pagination; verify p95 ordinary response <3 seconds, database query count, queue age, concurrent assignment submission and imports on representative hosted infrastructure. Record environment and cache state. This is a pilot workload hypothesis, not proof of capacity for every enrolled learner concurrently. The external monthly availability target remains 99.5%; AI latency is outside school acceptance.

Human task checks: class register and correction; admission/import error recovery; part-payment and amended statement; published assignment to released feedback; report approval and guardian retrieval; school-switch and shared-device logout. Test on low-end mobile screens and with keyboard navigation, readable labels, focus states, clear save/failure indicators and restricted network conditions. Target ≥30% time reduction on two observed school tasks; label it a pilot goal until measured.

Before production commitments, establish hosting support for database recovery point ≤1 hour and private-file recovery point ≤24 hours, with restore <2 hours. These proposed targets are stricter for the database than the legacy daily backup. Daily encrypted independent backups plus point-in-time recovery or suitably frequent database recovery points need a costed tested design; nothing is configured by writing this document. Attachment/object consistency and any unrecoverable newer files must be identified, quarantined from broken links and handled through an agreed re-upload/recovery process.

Restore drill: isolate environment and outbound channels → restore database/files → verify tenant counts, referential/object integrity, ledger balances and published content → reconcile provider events since recovery point → check lost file window → restore application functions → record elapsed time. Prevent old jobs from resending notices, granting access twice or repeating financial effects. Never restore over newer live receipts without a reconciliation and recovery plan.

## School onboarding and cutover runbook requirements

### Filament workspace entry

Filament is the permanent workspace for platform administrators and eligible school staff. After deployment, verify `/platform/login` and `/school/login`, then authenticate each role and confirm `/dashboard` routes platform admins to `/platform` and school staff to their authorized tenant. Keep guardian, managed-learner and personal-study paths on their separate intended interfaces. Preserve school and finance records and fix forward if a workflow issue appears. Do not remove canonical HTTP endpoints still used by shared services, portals or integrations.

## Support, safeguarding and boarding continuity

Define service hours and who is on call before quoting an SLA. Proposed routine service includes scheduled onboarding and follow-up; 24/7 vendor emergency support is not assumed. School staff own physical safety and emergency contacts. During an internet/power outage use agreed paper/phone registers, then enter actual event time and recording time with an audit. Rehearse this before R2. System alerts assist accountable staff and are not proof a child is safe or a guardian read a message.

Privacy readiness includes data-purpose/role mapping, guardian authority, age-appropriate notices, retention/export/correction, support access, provider processing/transfers and incident ownership; determine applicable obligations with the responsible adviser. No public child profiles, open adult–child chat or unreviewed health/discipline stores in the first release. Independent catalogue rights/review are a separate R3 gate.

## Release checklist and monitoring

- Verify current actor/school isolation, managed learner activation, background job scoping and secrets/configuration in staging.
- Test locked dependencies/assets, additive migration upgrade and forward-fix procedures against synthetic production-like data.
- Configure HTTPS, private files, queue supervision, scheduler, health checks, delivery providers and provider reconciliation before dependent live use.
- Smoke-test school switch, register, statement, assignment submission, released report and covered learning with AI unavailable; add boarding or direct purchase only for corresponding releases.
- Monitor HTTP latency/errors, queue failures/age, failed imports, unmarked work backlog, receipt mismatches/pending age, subscription expiry, delivery failure/cost caps, storage and backup freshness. Alerts must route to an assigned human.
- Prepare incident procedures for suspected disclosure, bad import, wrong balance, callback backlog, failed delivery, overdue boarding return, provider outage, recovery and cutover rollback. Separate Nova's software support from school emergency action.

R1/R2/R3 release evidence records actual test commands/results, manual participant counts, environment, unresolved defects, signed operational approvals and limitations. No critical access/financial defect is acceptable. No production deploy, real learner enrollment or payment transaction is performed by this planning task.

## Historical adult-AI verification plan — deferred where superseded

The remainder preserves the original FR/NFR checks for legacy modules and future AI work. AI evaluation, vector retrieval and AI latency do not block the current school releases. Current NQ recovery/workload targets supersede conflicting legacy proposals.

## Verification layers

- Unit tests: quota calculations, subscription periods, objective scoring, chunk boundaries, progress aggregation, and streak timezone rules.
- Feature tests: authentication, validation, ownership policies, private downloads, generation lifecycle, attempts, reminders, admin restrictions, and payment state transitions.
- Integration tests: PostgreSQL constraints/vector retrieval, Redis locks and concurrent reservations, private object storage, queued retries, provider adapter contracts. Use fakes in routine CI; never spend money or send real payment requests by default.
- Staging checks: controlled AI quality/cost evaluation, Daraja sandbox checkout and callback/reconciliation flow, mail delivery, representative file ingestion, scheduler and worker supervision.
- Browser/manual checks: landing → registration → verification → upload → grounded chat → quiz/cards → subscription → planner/progress, plus admin support. Check small screens, keyboard navigation, focus, labels, and understandable failures.

Use the [requirements matrix](requirements.md) to record evidence per FR/NFR in each phase's implementation record. Test existing behavior affected by a change as well as its new behavior.

## Critical failure cases

1. Student A cannot read, select, download, retrieve, delete, or generate from student B's data, including guessed identifiers and background jobs.
2. Missing verification, suspension, expired access, exhausted quota, and concurrent requests cannot bypass entitlement checks.
3. Corrupt/oversized/encrypted files, archive bombs, parser failure, embedding failure, retry exhaustion, and deletion during processing produce safe final states.
4. Prompt injection in uploaded notes cannot widen access or expose instructions/secrets. Missing evidence yields an honest response; citations resolve to authorized sources.
5. Provider timeout/rate limiting, invalid generated JSON, duplicate jobs, queue crashes, and stale reservations do not silently double-consume allowances or strand the UI.
6. Payment duplicates, spoofed/malformed inputs, wrong amounts, unknown references, missing callbacks, late success, status contradictions, and repeated renewals do not grant unverified access.
7. Quiz answer keys remain hidden until allowed; concurrent submission and repeated activity events do not inflate scores/time/streaks.
8. Admin actions require authorization and create useful audit records without logging passwords, full private prompts, files, or unnecessary phone/payment details.

## Performance and quality gates

Prepare a workload specifying concurrent users, requests per second, document sizes/counts, chat context lengths, provider/model, cache conditions, and worker resources. Proposed acceptance: normal request p95 below 3 seconds; average complete interactive AI response below 10 seconds. Also report p95 AI latency, queue age, error rate and saturation. Fast acknowledgement or first token alone does not satisfy complete-answer latency.

Use fixed representative questions with expected source passages, answer rubrics, unanswerable examples, and malicious document instructions. Set numeric grounding/citation/accuracy thresholds during P4 with the owner; run the same set when prompts, models, chunking or retrieval change. Fail launch on critical isolation failures regardless of quality averages.

Load-test the initial agreed active workload corresponding to 1,000 registered users. Document capacity limits and scaling triggers toward 100,000+; do not equate registered accounts to simultaneous AI calls. Include large document jobs while measuring tutoring latency.

## Deployment checklist

- Separate environment secrets, provider accounts and private buckets; HTTPS and debug disabled in production.
- Build assets and install locked production dependencies; keep development tooling out of the public runtime where feasible.
- Back up before migrations; test migrations and rollback/forward-fix procedures against production-like data.
- Supervise PHP/web processes, Redis-backed workers, and a single effective scheduler; restart workers on deployment and verify health.
- Check storage permissions, short-lived authorized downloads, upload limits, mail configuration, webhook reachability and reconciliation jobs.
- Smoke-test login, document readiness, an AI request, billing status, and admin access. Use controlled provider transactions only with appropriate production authorization.
- Assign an incident/support owner, alert destination, rollback trigger, and user communication process before launch.

## Monitoring and recovery

Monitor monthly 99.5% availability externally, HTTP error/latency rates, worker health, queue age, failed jobs, AI tokens/cost, payment pending age/mismatches, storage usage, database health, and reminder failures. Correlate events with internal request IDs and minimally necessary provider references.

Back up database and uploaded files daily and system snapshots weekly per SAD; encrypt backups, keep an independent destination, and monitor success. Proposed recovery point objective is at most 24 hours of loss, subject to owner approval; recovery time must be under 2 hours. Decide whether payment risk requires more frequent database recovery points.

Before launch, restore into an isolated environment and time the process: restore configuration securely, database, and files; validate record/object consistency; rebuild caches/indexes as needed; reconcile payment states; smoke-test; then record elapsed recovery time. Prevent restored jobs from sending old reminders or replaying charges. Repeat drills periodically and after infrastructure changes.

Create incident runbooks during P9 for provider outage, stuck ingestion, failed workers, callback backlog, suspected data exposure, database recovery, and deployment rollback. Specify actions, responsible owner, evidence to preserve, and recovery checks. The existence of a backup job alone is not proof of recoverability.
