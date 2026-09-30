# Verification and operations plan

## 1 October 2026 — Usability acceptance and trial boundary

UT-01–05 provide workspace routing/chooser, role tasks, focused Learning URLs, draft/final safeguards and calculated setup guidance. Synthetic local browser closeout verifies representative platform/admin/teacher/guardian/learner/personal/bursar paths, confirmation cancellation, saved-review refresh/Back, dirty navigation and released zero-score feedback. Narrow landing widths and representative task captures have no document overflow. The [usability plan](plans/school-trial-usability.md) and [implementation log](implementation-log.md) record actual suite results and synthetic evidence.

Use `/workspace` for neutral entry, `/study` for explicit adult personal study, and existing school/learner/guardian routes for authorized direct entry. A user with several eligible school/family spaces chooses explicitly. Setup completion describes one connected demo path, not complete roster reconciliation or host readiness. Draft warnings require supported browser behavior; save drafts explicitly and do not assume offline storage/autosave. Guardian recent lists are capped at twenty; learner assignments are paginated.

No deployment or real school data was used. Before sustained trial operation, confirm school/class/contact, account delivery, duration/support and intended host; validate applicable private files/scanner, workers/scheduler and backup/restore/account revocation there. Formal reports need a school-approved template/policy and separate SP6 design approval. Commercial finance remains deferred from this trial priority.

Updated 28 September 2026. Status: school release requirements and operational procedures remain proposed; the SP0 AI-boundary slice, SP4 fee-posting concurrency, receipt/allocation work, charge-credit, full-allocation reversal, cash-refund behavior, SP4-04 behavior, and permanent Filament workspace routing have local verification. The combined focused Docker PostgreSQL fee run passed (12 refund/statement/reconciliation tests, 93 assertions). Synthetic Chrome at 390px verified school reconciliation, school statement/print/CSV, guardian linked-child statement/CSV and foreign-enrolment denial, plus cash refund request, separate-admin review, and payout with a blank cash reference after the reactive-field fix. The full current-worktree PostgreSQL suite (253 tests, 1,162 assertions) and SQLite suite (253 tests, 1,127 assertions, 5 skipped) passed before that small fix; the focused PostgreSQL refund suite passed after it (4 tests, 38 assertions). The browser payout created only a synthetic record in the isolated Docker PostgreSQL database; no real payment was made. GitHub Actions run 36009434351 passed the PostgreSQL test job and frontend build on 93229c4. A synthetic three-school PostgreSQL check with 600 learners per school recorded 151–180 ms batch-post durations and 189–219 ms waits for one competing roster write; the local NQ03 diagnostic completed 270 mixed requests with 2.33 s combined p95 and zero failures after tenant-scoped pagination. Hosted capacity remains unverified. The school-row lock serializes fee posting with same-school roster writes. Filament is permanent for platform administrators and eligible school staff; there is no panel-off switch. The NS/NQ requirements and SP0–SP9 plan take precedence over the historical adult-AI checks retained below.

## 30 September 2026 — School trial learning acceptance

The review/release slice is implemented and locally verified. Focused isolated PostgreSQL coverage passed (36 tests, 203 assertions), including concurrent release; final current-checkout full-suite results are in the implementation log. Synthetic Chrome at 390px and 1024px verified learner login, draft/final text response, teacher private feedback, explicit release, learner feedback display and a score of zero. No horizontal overflow or JavaScript page errors were observed. An isolated synthetic PostgreSQL demo database exercised the full migration chain. No regular development or production database was migrated or seeded.

For the demo: refresh the local-only `DemoSchoolSeeder`, sign in using its configured `NOVA_DEMO_PASSWORD`, and use learner ID `DEMOLEARN001`. Learner: assignments → save draft → submit final. Teacher: Learning → course → Review submissions → Open response → Save feedback draft → Release feedback. Learner: refresh assignment to read feedback. Scores are optional; if used, supply both score and maximum. Released feedback cannot be edited in this slice. Use the host setup's normal migration process before running the updated application; the review table is additive. These steps do not establish trial hosting readiness.

Trial commercial billing is deferred. Sustained real-data use still requires intended-host sessions/storage/queue/scheduler, scanning for file release, monitoring, tested restore and school onboarding. Start the synthetic learning demo with authored text; uploads retain their existing scanner gate.

## SP5-01 local acceptance update

On 29 September 2026, focused SP5-01 tests passed against isolated PostgreSQL 17 (19 tests, 97 assertions) and SQLite (18 tests, 91 assertions; the PostgreSQL-only quota race was skipped). Two independent PostgreSQL upload processes left the school at exactly 500 retained resources. Synthetic headless Chrome verified teacher course creation, draft save and unpublished preview; managed-learner sign-in, published lesson visibility and private attachment download. A temporary ClamAV service and Nova's actual scan job classified clean content as clean and rejected/deleted EICAR test content. These checks used synthetic data and local infrastructure; intended-host scanner, storage, queue, scheduler, monitoring and recovery checks remain open.

## Local browser demo setup

On the local Docker environment, run `php artisan db:seed --force --no-interaction` to create or refresh the synthetic demo school and role accounts. The seeder is guarded to the `local` environment and uses `NOVA_DEMO_PASSWORD` when present, so the password is not committed to project documentation. Use the landing page's **Open local demo** link, sign in as the seeded school administrator, and begin from the dashboard workspace links.

## Active verification matrix

SP4-04 behavior verification is complete locally: school-admin-only statement/CSV routes, active verified-link guardian statement/CSV routes, and a read-only tenant reconciliation page are present. Focused Docker PostgreSQL tests pass (12 combined fee tests, 93 assertions), covering statement authorization/calculations, guardian-child isolation, CSV safety, reconciliation totals/discrepancies, refund lifecycle/race, and reconciliation page access. Synthetic Chrome at 390px verified school and guardian statement rendering/CSV, the print action, child isolation, and reconciliation balances. The focused follow-up after fixing cash payout null-reference handling and the conditional payout-reference reactivity passed (4 refund tests, 38 assertions). Pint, syntax checks, and route registration passed. SQLite was not used for the focused follow-up.

| Boundary | Required evidence | Gate |
| --- | --- | --- |
| AI deferral | School/managed-learner routes and delayed jobs cannot dispatch AI when disabled; no lost legacy records/reservations | SP0; every release smoke test |
| School isolation | Two schools, multiple roles, guardian across schools, revoked staff; deny cross-school lists, bound IDs, files, exports, cache and jobs | SP1 and every module; FI-08 synthetic Chrome check confirms tenant switch, separate concurrent tabs and 404 for a foreign import-batch ID. Broader role/resource/export/job isolation remains covered by feature tests and pending module gates |
| Registry and identity | Managed learner without email; verified guardian; no privilege inheritance; replayed import, duplicate names/numbers, transfer and account deactivation | SP2; synthetic Chrome covers import/replay and, on 24 September, native learner admission and guardian linking. Cross-school/two-tab browser evidence exists for imports; broader lifecycle, export and account-deactivation tasks remain open |
| Attendance and messaging | Unmarked vs absent, concurrent edits, authorized correction, duplicate notices, recipient revocation and stale status before send | SP3/SP7; synthetic Chrome verified admin register save, teacher correction blocked without a reason then saved with a reason, teacher communications denial, guardian corrected attendance visibility, admin draft/send and one linked-guardian delivery. At 390px, native overview/registry/detail/academic/attendance/fees/communications pages had no horizontal overflow. On 24 September, trusted Chrome Tab/ArrowDown/Enter input opened the teacher's roster and showed the success notice against isolated PostgreSQL; opening persisted no session, entries or audit events. Email/SMS, retries, read state and alerts remain SP3 work |
| Fee integrity | Preview hash freshness, charge batch replay/key-scope, sibling/partial allocation, unallocated credit, credits, refunds/reversal, locked period, posting during a roster write and simultaneous allocation | SP4; PostgreSQL suites/races cover fee posting, receipt allocation, credit review, full-allocation reversal, and refund authorization/lifecycle/idempotency/race. Synthetic Chrome at 390px now covers cash refund request/separate-admin review/payout, school and guardian statements, CSV/print actions, unrelated-enrolment denial, and reconciliation. The cash payout reference is optional after selecting cash. Broader FI-08 parity/cutover, NQ03 hosted capacity, opening balances, and live cutover remain open. |
| Learning | Versioned publishing, recipient cohort, private file rights, due/cutoff/extension, retry and concurrent submission, hidden keys, pending manual marks and released feedback | SP5-01 focused PostgreSQL suite passes (19 tests, 97 assertions) for course/lesson access, private upload lifecycle, scanner states, retention and the 500-resource race. Synthetic Chrome teacher/learner lesson acceptance and real local ClamAV scan-job transitions pass. SP5-02 publication/snapshot and SP5-03 text draft/final submit, acknowledgement replay, escaped rendering, late cutoff, and local demo seeder pass focused SQLite coverage (8 tests, 56 assertions). Text assignment/submission and private-review/released-feedback acceptance now have isolated PostgreSQL and synthetic Chrome evidence (see the 30 September checkpoint). Attachments, resubmissions, extensions, rubrics/amendments and VPS storage/scanner/queue/scheduler validation remain open. |
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

### SP5-01 private lesson resources

- Keep classroom resources on the configured private `local` disk (`storage/app/private`); do not run `storage:link` for these files or expose storage keys/URLs.
- Before enabling uploads in a hosted environment, provision a private ClamAV `clamd` Unix socket, set `CLAMAV_SOCKET` and `CLAMAV_TIMEOUT_SECONDS`, enable signature updates and health monitoring, and confirm `StreamMaxLength` accommodates the 10 MB application limit plus protocol framing. Do not expose unauthenticated ClamAV TCP.
- Run a supervised queue worker and the Laravel scheduler. The `school:purge-lesson-resources` schedule must run daily; queue age/failure alerts need an assigned operator. Pending resources are intentionally unavailable until scanning succeeds.
- Verify private storage capacity, temporary upload storage, backup/restore coverage, and deletion retries. The application currently uses local private storage; production object-storage migration is not configured.
- Pilot the allowlisted file types with synthetic samples and verify clean, infected, malformed, missing-scanner, retry, download authorization, quota warning/limit, withdrawal, and purge behavior before accepting real school materials.

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
