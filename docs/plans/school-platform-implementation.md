# School administration and e-learning implementation plan

## 1 October 2026 — Trial usability implemented and locally verified

Owner approved the [school trial usability plan](school-trial-usability.md). UT-01–05 are implemented and locally verified: shared role entry/chooser, daily task summaries, focused Learning task URLs, draft/final safeguards and calculated setup guidance. Final suites pass: SQLite 310 tests (303 passed, 7 skipped; 1,464 assertions) and PostgreSQL 310 passed (1,512 assertions). Synthetic browser acceptance covers role entry, refresh/Back, dirty feedback navigation, zero-score release, attendance and mixed guardian/bursar access. Technical UT-06 acceptance is locally exercised; intended-host readiness and actual school trial inputs remain open. UT-07 formal report design remains gated on a school sample/policy and separate approval. No deployment, new schema/dependency or commercial billing was added.

## 30 September 2026 — School trial MVP checkpoint

Owner direction: build a working school demo that staff and learners can try over time before discussing commercial finance. Commercial billing, payment integrations and pricing are deferred from this trial milestone. Existing school fee functionality is preserved; trial participation does not require using it. This trial is distinct from the complete commercial R1 release below.

Status: owner approved the teacher review/feedback slice on 30 September 2026; implemented and locally verified. Final current-checkout suites pass: SQLite 300 tests (293 passed, 7 skipped), 1,384 assertions; PostgreSQL 300 passed, 1,432 assertions. Synthetic Chrome acceptance passes at 390px/1024px. School trial deployment and intended-host operations remain open.

Trial journey: administrator sets up staff, classes and learners; teacher records attendance and publishes lessons/text assignments; learner saves/submits work; teacher reviews responses and releases feedback; learner reads released feedback. Guardians retain existing linked-child attendance and notices. Start with synthetic demonstration records; validate intended-host operation and school onboarding before sustained real-data use.

### Page-flow study follow-up — proposed

The [30 September comparison](../case-study-page-flow-report.md) records current reference/source and synthetic browser evidence. Proposed trial priorities are correct role entry and landing fit, teaching/feedback actions on role home pages, persistent scoped review context, and guided school setup. No application fix or new module is implemented by this study. School report design still requires a pilot-approved template and grading rules.

### Approved and implemented slice: teacher review and feedback

- Reuse existing assignment/submission services, policies, Filament learning workspace and learner assignment page.
- Add an additive review table linked to the submitted response, with school, reviewer, draft feedback, optional whole-number score and positive maximum score, and release actor/time. No review means awaiting review; zero is a real marked result. Assignment feedback is separate from official reports and competency conversion.
- Assigned teachers and authorized school administrators can open submitted responses, save review drafts and explicitly release feedback. Recheck current school, membership and teaching assignment. Unrelated teachers and foreign-school staff are denied.
- Learners see only their own released review. Released reviews and submitted responses remain immutable in this slice. Formal amendments, rubrics, resubmissions, attachments and report cards remain later work.
- Save/release transitions and audit events are transactional; release is replay-safe and scores are validated server-side.

Affected areas: additive migration; review model/factory; submission relationships; policies and school services; existing SchoolLearning Filament page/view; learner assignment controller/view; focused PHPUnit feature and concurrency coverage; architecture/data-model/operations notes and implementation log. Exact filenames follow siblings. No dependency changes are proposed.

Acceptance: teacher can open a submitted response, save private feedback and release it; learner sees feedback only after release; unmarked and zero are distinct; invalid scores are rejected; cross-school, unassigned and revoked actors cannot read or mutate reviews; repeated release creates one released review and audit event; draft responses cannot be marked; withdrawn work retains its existing access restrictions. Verify focused SQLite and isolated PostgreSQL tests, concurrent release behavior, Pint, frontend build and representative teacher/learner mobile journeys.

PostgreSQL prerequisite discovered during implementation: the uncommitted SP5 recipient/submission create migrations generate constraint identifiers longer than PostgreSQL's 63-byte limit, causing collisions on fresh installation. Give the affected unique constraints short explicit names without changing columns or integrity rules. These create migrations are not tracked in the current Git baseline and their PostgreSQL verification was previously pending. Existing SQLite installations retain their constraints; no data migration, dropped table, or production reset is proposed.

Browser prerequisite discovered: the synthetic demo learner ID exceeds the existing 12-character login validation. Update only the demo seeder to a valid ID, reuse an existing legacy demo account when refreshing, and exercise actual login in the demo feature test. Production login rules remain unchanged.

### Remaining trial preparation

1. Completed locally: verified assignment/submission and review/release on an isolated PostgreSQL 16 instance without touching development data.
2. Completed for the approved slice: implemented review/release and verified teacher/learner browser journeys. Broader administrator/guardian trial rehearsal remains open.
3. Agree trial duration, school champion, grade/class scope and feedback routine with the owner/school; these remain unresolved and do not block reusable code work.
4. Validate intended-host sessions, private storage, queue/scheduler, monitoring, backup restoration and onboarding before sustained school use. Demonstrate text-only learning while file release remains gated on verified scanning.
5. Prioritize extensions, objective practice, progress summaries and one school-approved report format from trial feedback. Formal reporting requires school input; commercial discussion follows the trial.

Approval and verification are recorded in the implementation log. Review functionality is implemented; trial deployment remains open.

Date: 29 September 2026. Status: SP3-01 through SP3-04 first slices are implemented and locally verified; SP4-03 charge-credit, full-allocation reversal, and cash-refund behavior have focused Docker PostgreSQL verification and synthetic Chrome acceptance. SP4-04 statement and reconciliation behavior passed focused Docker PostgreSQL tests under the approved school-admin-only statement/reconciliation access scope and synthetic Chrome acceptance at 390px. The full current-worktree suites passed on 28 September against isolated Docker PostgreSQL (253 tests, 1,162 assertions) and SQLite (253 tests, 1,127 assertions; 5 skipped); the focused refund suite passed after the cash payout-reference reactivity fix (4 tests, 38 assertions). SP0 is in progress, SP1 has implemented provisioning/audit, protected school context, invitations, role lifecycle services, validated web flows, active-school navigation, and an overview policy, and SP2 has implemented learner registry, verified guardian-link, academic year/term, class, subject, teaching-assignment, staged-import, dated-placement, restricted learner-access, promotion, transfer, and deactivation foundations. SP5-01 lessons/private resources are implemented and locally verified; SP5-02 assignment publication/recipient snapshots and SP5-03 text draft/final submission are implemented with focused SQLite acceptance (8 tests, 56 assertions), while PostgreSQL verification remains pending. VPS scanner/storage/queue/scheduler validation is deferred for the MVP. Durable selected-school preference and broader scoped resources remain open. The owner has accepted school administration plus e-learning and deprioritized AI. The owner confirmed a one-developer estimating baseline and no existing pilot partner. This plan supersedes the P0–P9 delivery order, retained in the [legacy roadmap](legacy-study-assistant-roadmap.md).

## Planning scope and dependencies

Deepen the workflow/service study, inspect the existing application, define a usable non-AI school product and an independent learning offer for students at non-subscribing schools, and replace the active adult-AI roadmap. Preserve existing code and historical implementation records. Update requirements, architecture, proposed data model, verification/operations, decisions and the documentation index in the same documentation change.

Dependencies: existing three studies and implementation plans, installed-package evidence, relevant Laravel/testing skills, official competitor/LMS/curriculum/payment/privacy sources, and owner clarification of team/partner access where available. Use planning assumptions if optional clarification does not arrive; distinguish these from accepted direction.

## Planning acceptance criteria

- Define release boundaries for day schools, boarding/mixed schools and independent learners, with AI outside their critical path.
- Specify task IDs, dependencies, candidate files/entities, responsible roles, acceptance tests, migration/rollout steps and delivery estimates with stated capacity assumptions.
- Make the first implementation slice executable without requiring approval of the direction again.
- Identify external readiness gates separately from coding tasks; unknown partners or merchant access must not block synthetic-data foundation work.
- Preserve the P0–P9 history and existing individual records; do not imply school workflows or AI-disable controls are already implemented.
- Verify documentation links, traceability, arithmetic, status consistency and whitespace; record actual results in the implementation log.

## 1. Product commitment and release boundaries

Deliver one school platform for day, boarding and mixed schools, plus learning-only subscriptions for students at non-subscribing schools. School contracts include the agreed learning access for their enrolled learners. AI tutoring, generation, embeddings, semantic retrieval and AI marking are deferred: none is required for school admission, finance, lessons, assignments or reports. Existing AI code/data are preserved; provider dispatch is now disabled by default for explicit school and managed-learner job contexts, while school routes and context authorization remain SP1+ work.

| Release | Included | Explicitly not promised |
| --- | --- | --- |
| R0: internal demo | Synthetic schools, scoped staff access, roster and one demonstrated administration/learning journey | Real child data, live financial records, production readiness |
| R1: controlled day-school pilot | SP0–SP7: records/imports, attendance, fees/reconciliation, parent access, lessons/assignments/manual grading, selected report templates, school subscription and operational readiness | All grades' catalogue content, full accounting/payroll, unattended official assessment submissions |
| R2: boarding/mixed pilot | R1 plus SP8 residence, roll call, leave/release/return, staff escalation and outage procedures | Building-safety certification, clinic records, biometric attendance, GPS, 24/7 vendor emergency response |
| R3: independent Learn beta | SP9: guardian-led onboarding, licensed catalogue, purchases/expiry and overlap handling, no school purchase required | Private customer-school content, unlimited educator marking, AI-dependent features |
| Initial commercial scope complete | R1–R3 acceptance evidence plus one reporting/billing cycle and paid continuation evidence for the applicable pilots | Nationwide fit or unrestricted scaling |

Administrative records may support multiple grades, but each pilot lists the supported assessment template and learning-content coverage. Default recruitment target: primary day schools around 150–600 learners, plus one boarding/mixed partner. Grade 5 mathematics is a content hypothesis to validate with a recruited educator, not a hard-coded schema limitation. One campus per initial customer; keep organization/campus expansion out of the first UI.

## 2. Ownership and estimates

**Capacity baseline confirmed by owner:** one developer. Estimate in focused developer-days including implementation, feature tests and technical documentation. Owner-led school recruitment, educator authoring/review, school staff verification, legal/privacy advice and provider waiting time are separate. These roles are needed, not assumed hired. If the developer also performs them, increase calendar time and cost.

| Phase | Dependency | Developer-days | Status and accountable roles |
| --- | --- | --- | --- |
| SP0 Baseline and non-AI product boundary | None | 3–5 | In progress; AI boundary and demo fixture implemented, hosted PostgreSQL CI passed on the recorded CI commit; developer, owner |
| SP1 School tenancy and permissions | SP0 | 8–12 | In progress; provisioning, context, invitations/roles, validated web flows, navigation and overview policy implemented; broader scoped resources remain; developer |
| SP2 Learners, guardians and academic structure | SP1 | 10–15 | In progress; learner/enrolment, guardian-link, academic year/term, class, subject, teaching-assignment, staged-import, dated-placement, restricted learner-access, promotion, transfer, and deactivation foundations implemented; developer, school operations adviser |
| SP3 Attendance, parent portal and notices | SP2 | 8–12 | In progress; SP3-01 through SP3-04 first slices implemented and locally verified; external delivery, retries, read state and alert confirmation remain; developer, school champion |
| SP4 Fee subledger and reconciliation | SP2 | 15–22 | In progress; SP4-01 through SP4-04 behavior implemented with focused PostgreSQL and synthetic Chrome verification; opening balances and live-provider evidence remain; developer, bursar/reviewer |
| SP5 Non-AI lessons and assignments | SP2 | 15–22 | SP5-01 implemented and locally verified; SP5-02 publication/snapshot and SP5-03 text draft/final submission slices in progress; PostgreSQL and VPS operations deferred; developer, educator |
| SP6 School assessment and report publication | SP3, SP5 | 10–15 | Planned; developer, academic reviewer |
| SP7 Payments, school contracts and R1 readiness | SP3–SP6; provider access for live channel | 15–22 | Planned; developer, owner, provider/contact |
| SP8 Boarding extension | SP2–SP4, SP7 readiness; boarding partner | 10–15 | Planned; developer, boarding lead |
| SP9 Independent Learn | SP5, SP7; licensed content/guardian readiness | 8–12 | Planned; developer, content lead, owner |

Totals: SP0–SP7 = **84–125 developer-days**; all phases = **102–152 developer-days**. Add a planning contingency of 25%, rounded up: **105–157 days** for R1 and **128–190 days** for all phases. At five fully available development days/week these are roughly **21–32 weeks** and **26–38 weeks** respectively, before external waiting and a school-cycle observation period. These are initial sizing judgments, not fixed-price bids or calendar commitments. Re-estimate after SP2 using completed work and partner samples; do not halve them mechanically for a second developer.

Recommended one-developer order: SP0 → SP1 → SP2 → SP3 → SP4 → SP5 → SP6 → SP7, then SP8 and SP9 in the order partner/content readiness allows. SP4 and SP5 have parallelizable dependencies but remain sequential for this staffing assumption. Do not make independent learning wait for a boarding partner if SP9's own prerequisites are satisfied.

## 3. Discovery and recruitment workstream

No partner is confirmed. The owner owns recruitment; the developer provides a synthetic demo and structured checklist. Research and this plan do not send outreach or enroll anyone.

| Task | Timing | Deliverable and evidence |
| --- | --- | --- |
| REC-01 | First 5 working days alongside SP0 | Owner builds a private list of 12 reachable schools, including day, boarding/mixed and existing-software users; identify decision-maker and contact route. Keep contacts outside committed docs |
| REC-02 | First 2–3 weeks, subject to responses | Conduct 8 adult staff interviews across at least 5 schools; include principals, bursars, teachers and boarding lead. Record anonymized recent workflow problems, current spend, devices and switching obstacles |
| REC-03 | Before SP4/SP6 schema freeze | Obtain permissioned/redacted or synthetic roster, statement and report examples; agree one fee policy and one assessment template for initial pilot. No private originals in repository |
| REC-04 | Before R1 | Select 2 day-school partners with a named champion, accountable data owner, testing time and written pilot scope; disclose price hypothesis and exit terms |
| REC-05 | Before live SP8 | Select 1 boarding/mixed partner and rehearse leave/return and outage processes with actual responsible staff |
| REC-06 | Before SP9 public sale | Recruit 1 qualified author plus a separate reviewer; test pack clarity and price with 12 guardians at non-customer schools; obtain licensed, reviewed content |

If recruitment has not yielded a credible partner by the end of SP2, demonstrate the foundation and review product assumptions before investing in school-specific finance/report variants. Continue reusable tested core work where justified; lack of a partner blocks real-data pilot claims, not tenant isolation or synthetic workflows. A new partner's request is ranked against the bounded release rather than accepted as unlimited customization.

## 4. SP0 — establish baseline and contain deferred AI

Requirements: NS01, NS18, NQ01–NQ05. Estimate: 3–5 days.

| Task | Implementation target | Done when |
| --- | --- | --- |
| SP0-01 | Existing routes, migrations, tests, `.github/workflows/ci.yml` and environment documentation | Local SQLite baseline recorded and PostgreSQL integration lane configured; hosted PostgreSQL execution and schema-tool reconciliation remain open |
| SP0-02 | Product configuration, existing chat/generation routes/jobs, dashboard/navigation | New school release defaults to no AI actions; server-side guards prevent provider dispatch even by direct requests or old queued work. Preserve legacy records and tests; explicitly configured adult development use can remain isolated |
| SP0-03 | `docs/requirements.md`, acceptance fixtures and demo brief | Schema-neutral synthetic two-school fixture created for SP1; application seeder remains intentionally deferred until school tables exist |

Boundary tests: AI unavailable to school/managed-learner contexts; disabled jobs do not call a provider and release any pending reservation safely; existing personal records are not deleted or assigned to a fictional school. Verify legacy behavior intentionally retained. No infrastructure deletion or key rotation is implied by feature deferral.

## 5. SP1 — tenant isolation, staff roles and audit foundation

Requirements: NS02, NS17, NQ01. Estimate: 8–12 days. Proposed entities: `schools`, `school_memberships`, `school_role_assignments`, `school_invitations`, `audit_events`.

| Task | Implementation target | Done when |
| --- | --- | --- |
| SP1-01 | School/member models, factories, migrations; `app/Services/Schools` | Platform operator can provision a synthetic school and first administrator through a controlled action; no public self-promotion; memberships support multiple schools and multiple scoped roles |
| SP1-02 | School-context middleware, route group and policies | School context comes from an authorized membership, not an untrusted submitted school ID; nested records, lists, downloads, exports and jobs are scoped; removal takes effect on next protected action |
| SP1-03 | Invitations and role management | Expiring one-use invitations bind the correct recipient/school; school admin cannot grant platform powers; last school administrator cannot be accidentally removed |
| SP1-04 | Audit writer, school navigation, and role-specific home screens | Audit critical invitations, roles and school changes using minimal metadata; active school links are visible without exposing removed/inactive memberships; one user can switch school contexts without carrying another school's permissions |

Candidate files follow existing `app/Models`, `app/Http/{Controllers,Requests,Middleware}`, `app/Policies`, `app/Services`, `resources/views` and `tests/Feature` conventions. Use controllers/Form Requests and focused services; no new tenancy/permission dependency by default. Do not introduce a global admin policy bypass for private school records. Platform support uses explicit, time-limited audited access.

Acceptance: two-school negative tests for read/write/search/download/export; insufficient roles; invitation replay/expiry; removed membership during queued work; role escalation through extra payload keys; actor/audit attribution. Include a test that an authentic user without school membership cannot infer the existence of another school's learner record.

## 6. SP2 — registry, guardians, terms and managed learner access

Requirements: NS03–NS05, NS17. Estimate: 10–15 days. Proposed entities: learner profiles and verified guardian links, enrolments, academic years/terms, class groups, subjects, teaching assignments, import batches/rows.

| Task | Implementation target | Done when |
| --- | --- | --- |
| SP2-01 | School setup and learner/enrolment forms | Staff configures term dates and classes, admits a learner with a school-scoped admission number, and assigns teacher subjects/classes |
| SP2-02 | Guardian invitations/relationship verification | Multiple children/guardians supported; payer/contact alone grants no access; revoked or disputed relationships deny future reads and notifications |
| SP2-03 | Managed learner login/activation | Implemented restricted learner identity with nullable email, generated login ID, one-time hashed activation token, password setup, learner-only dashboard, logout/login, and adult-route rejection. Email/SMS delivery, recovery, and guardian-led activation remain open |
| SP2-04 | CSV staging/preview/commit and exports | Staged CSV headers/types/duplicates are validated; partial invalid rows stay uncommitted; confirmed valid rows commit predictably; replay does not duplicate learners. Synthetic browser acceptance confirms partial commit and repeat-upload idempotency. Authorized exports and spreadsheet formula escaping remain open |
| SP2-05 | Promotions, transfers, deactivation and account deletion handling | History retained; class/boarding changes use effective dates; transferring schools cannot fetch old private files; deleting a login does not cascade institutional ledger/report history |

Authentication implementation: adult `users` retain email verification and password-reset behavior; managed learner users use `account_type`, nullable email, a generated unique login identifier, and activation timestamps. Learner profiles link optionally to these identities. One-time activation tokens are hashed and expiry-bound; learner routes use dedicated middleware and cannot enter adult profile, school, guardian, personal-study, or verified-email workflows. Email/SMS delivery, learner recovery, and guardian-led activation remain open.

Acceptance: adult authentication regressions pass; learner cannot use adult admin/profile endpoints; guardian cannot switch into a child with school-admin privileges; identical names/admission numbers across schools remain separate; repeat import and concurrent duplicate admission are safe; term promotion leaves past results unchanged. Use synthetic CSVs until data handling is ready.

## 7. SP3 — attendance, guardian portal and communication

Delivery status, updated 27 September 2026: the owner resumed the roadmap in staged slices while [Filament integration](filament-integration.md) continues. Full school-staff workflow migration is the active priority. Native Filament attendance, communications, overview, learner, academic and fee pages use shared services and pass local regression coverage. Synthetic Chrome acceptance includes admin register save and guardian notice send, teacher reasoned correction and role denial, guardian visibility, learner admission/guardian linking, staff invitation create/revoke and role assign/remove, complete academic setup, learner promotion/deactivation and transfer to a provisioned second school, class-targeted notice send/replay to a linked guardian, trusted Space activation of attendance register opening, and 390px no-overflow checks. A focused role/access rerun passed (27 tests, 136 assertions); Chrome confirmed bursar denials at 390px and platform-admin, school-admin, teacher and bursar navigation/overflow at 1024×768. Wider role parity and cutover remain open. Before permanent panel routing was finalized, SQLite passed (221 tests, 956 assertions, 2 PostgreSQL-only skips) and PostgreSQL passed (221 tests, 971 assertions). A local synthetic three-school/600-learner fee benchmark recorded 151–180 ms posts and 189–219 ms for one same-school roster write to wait; NQ03 local mixed-load timing is 2.33 s p95 across 270 requests with zero failures after tenant-scoped registry pagination; hosted capacity validation remains open. Prior browser evidence covers CSV stage/review/partial commit/replay, tenant switching, simultaneous schools and foreign-record denial; FI-03 provisioning and native finance actions. SP4-02 receipt/allocation and FI-06 posting have local PostgreSQL concurrency evidence. Corrective GitHub Actions run `36009434351` passed SQLite tests, PostgreSQL tests and the frontend build on `93229c4`; the earlier failure was on older SHA `4b0ecbf`. The owner directed full Filament migration without a panel-off switch. Local PostgreSQL HTTP checks verified panel and Livewire routing with panels on, and the rerun full PostgreSQL suite passes (224 tests, 977 assertions), including direct fee-panel denial for teachers and bursars, with SQLite at 222 passed and 2 PostgreSQL-only skips (962 assertions). The 27 September focused PostgreSQL fee run passed cash-refund lifecycle/race and SP4-04 statement/reconciliation coverage (12 tests, 93 assertions); 28 September synthetic Chrome acceptance now covers those flows at 390px. Hosted capacity and remaining workflow/device parity remain open. Opening balances and hosted NQ03 capacity validation remain open.

Requirements: NS06, NS08; depends on SP2. Estimate: 8–12 days.

| Task | Implementation target | Done when |
| --- | --- | --- |
| SP3-01 | Registers and dated entries | Implemented attendance session/register slice: assigned teacher or school administrator records a dated class register; missing/unmarked is distinct from absent; retries are idempotent and stale versioned edits are rejected |
| SP3-02 | Attendance exception/correction workflow | Implemented and locally verified: status changes require a reason and create immutable correction history; later alert confirmation/suppression remains open |
| SP3-03 | Guardian home/child switch | Implemented and locally verified: verified guardian sees only the selected active linked child's attendance; notices and later published statements/results remain open; relationships are rechecked server-side |
| SP3-04 | Announcements and delivery ledger | Implemented and locally verified first slice: school-admin draft/send with school/class guardian targeting and idempotent in-app delivery ledger; email/SMS, retries, and read/acknowledged state remain open |

Use existing notification facilities where appropriate, durable jobs after commit and idempotent recipient/event keys. Show save/retry state on weak connections. Native offline synchronization is excluded. Acceptance includes unauthorized class, sibling leakage, duplicate register/save, changed guardian before delivery, email failure and revoked notices. Printable attendance is an explicit authorized export, not public storage.

## 8. SP4 — fees and reconciliation before automation

Requirements: NS07, NS17; depends on SP2. Estimate: 15–22 days.

| Task | Implementation target | Done when |
| --- | --- | --- |
| SP4-01 | Fee schedules, invoices/charges and opening balances | First slice implemented and locally verified: school/class/term fee schedules preview and post idempotently with immutable charge snapshots; opening-balance import/sign-off and residence rules remain open |
| SP4-02 | School receipts, payment evidence and allocations | Manual cash/bank/M-Pesa receipt entry, idempotency, tenant-scoped partial/sibling charge allocation and both remaining-balance checks are implemented through shared services and native Filament/canonical workflows; SQLite and PostgreSQL suites, synthetic browser acceptance and a PostgreSQL single-winner allocation race pass. No live payment verification is implemented |
| SP4-03 | Credits, reversals, refunds and approvals | Credit/reversal/refund services and PostgreSQL feature/race coverage pass. Synthetic Chrome acceptance covers credit review, allocation reversal, and cash refund request/review/payout; the optional cash reference is verified |
| SP4-04 | Statements, balances and reconciliation view | Behavior implemented and verified by focused Docker PostgreSQL tests and synthetic Chrome at 390px for school/guardian statements, print/CSV, child scoping, and reconciliation. |

Keep finance domain separate from `subscriptions` and Nova payment orders. Lock affected receipt/account rows in a transaction, verify same-school relationships, store currency and integer minor units, and apply unique external references within merchant/provider scope. School tuition is not Nova revenue. No full general ledger, payroll, tax filing, fee loans or automatic suspension of learning for school arrears.

SP4-03 first-slice scope: add a school-scoped fee-adjustment lifecycle record for a requested charge credit, with an idempotency key, reason, requester, reviewer, status, and review timestamps. Only active school administrators may request or review; the reviewer must be a different user from the requester. Recheck the charge's remaining balance inside a transaction at both request and approval, including posted receipt allocations and previously approved credits. Approval records a credit against the charge and leaves its posted amount unchanged. Rejection records the reviewer and reason without creating a credit. This is a finance-control implementation assumption for the synthetic-data slice; schools with only one active administrator cannot complete a request until another administrator is assigned. It does not return money or reverse a receipt allocation. Cash refunds, allocation reversals, school-specific approval roles, and one-admin exception handling remain unresolved and out of scope for this slice.

Acceptance for this slice: foreign-school charge IDs are rejected; inactive memberships and non-admin roles cannot request/review; requester cannot approve their own request; replay with the same key and payload is idempotent while changed payload reuse is rejected; approval cannot exceed the currently outstanding receivable; concurrent approvals cannot over-credit; approved/rejected records are immutable; every transition has a minimal audit event; school fee screens display the charge's net outstanding amount separately from its original posted amount. No live-school finance use is implied.

Verification for this slice is complete against the dedicated Docker PostgreSQL database: focused service/policy/idempotency tests, additive migration, concurrent approval check, and synthetic Chrome request/self-review denial/approval/rejection/net-balance display acceptance passed. No SQLite verification is in scope.

Acceptance cases: part-payment; payment for two siblings; overpayment/unallocated credit; scholarship; backdated entry in an open period; locked-period amendment; duplicate reference; simultaneous allocation; reversal after allocation; fee change mid-term; mixed day/boarding charges. School/bursar must sign off opening balances and test statements before finance cutover. No unexplained variance is acceptable.

## 9. SP5 — teacher-authored e-learning without AI

Requirements: NS09–NS12; depends on SP2. Estimate: 15–22 days.

| Task | Implementation target | Done when |
| --- | --- | --- |
| SP5-01 | Courses, lesson versions and private resources | Assigned teacher can draft, preview and publish ordered text/resources for one teaching assignment; private uploads are validated/scanned before publication and every download rechecks access; school rights and Nova catalogue rights remain separate |
| SP5-02 | Assignments and recipient snapshots | Teacher sets instructions, publish/due/cutoff times and allowed submission types; publishing freezes the assessed version and records the eligible cohort; later enrolment requires explicit assignment |
| SP5-03 | Submission lifecycle and attachments | First host-independent text slice implemented: learner saves a draft, submits once, receives durable acknowledgement, and can submit late only before the configured cutoff. Attachments, extensions, retry/resubmission history and marking remain open; PostgreSQL verification pending |
| SP5-04 | Manual question authoring and objective practice | Author defines question/options/key/points without provider access; released question versions cannot change historic scoring; answer keys stay out of learner HTML/JSON until policy permits |
| SP5-05 | Marking, moderation and released feedback | Teacher marks open responses/rubrics; ungraded work stays pending rather than zero; feedback is visible only after release; correction/regrade has a reason and audit |
| SP5-06 | Learner/teacher progress views | Display assigned, started, submitted, awaiting marking and completed separately; teacher follows up missing work; guardian sees released summaries |

### SP5-02 MVP slice — assignment publication

Status: implemented with focused SQLite coverage; PostgreSQL verification remains pending. This host-independent demo slice builds on the verified SP5-01 course/lesson flow. A teacher creates an assignment for a published lesson version, adds instructions and due/cutoff times, then publishes it. Publication records an immutable version and a snapshot of learners with active enrolments and dated class placements in the teaching assignment's class on that date. Managed learners can view assignments addressed to their snapshot recipient record. Later enrolments are not added automatically. The SP5-03 text draft/final-submit slice is now implemented separately; attachments, retry/resubmission handling, marking, and feedback remain later work. VPS ClamAV/storage/queue/scheduler validation stays deferred and does not block the text-only demo.

Acceptance for this slice: only the assigned teacher or an authorized school administrator can create and publish within the selected school; the source lesson version must be published and belong to the same school's teaching assignment; due time must follow publication and cutoff must not precede due time; publish is atomic and freezes the assignment content/deadlines and recipient cohort; publish replay cannot duplicate recipients; learners see only assignments whose recipient snapshot references their own learner profile and enrolment; learners added to the class after publication are excluded unless a later explicit assignment action is added; foreign-school, nonrecipient, inactive learner, and unpublished-draft access is denied. PostgreSQL and SQLite feature coverage plus synthetic browser acceptance should cover teacher publish and learner visibility with synthetic data.

### SP5-03 MVP slice — text response submission

Status: implemented with focused SQLite coverage; PostgreSQL verification remains pending. A learner can save one editable text draft and submit it once for a published assignment addressed to their recipient snapshot. Final submission returns a stored acknowledgement reference and timestamp; replaying the final-submit request returns the same acknowledgement. A submission after the due time but no later than the configured cutoff is accepted and marked late. When no cutoff is configured, the due time is the final cutoff. Draft saves and final submissions after the effective cutoff are rejected. A submitted response is immutable in this first slice; resubmission, teacher extension controls, file attachments, malware scanning for learner uploads, marking, and released feedback remain deferred. File uploads remain blocked until the VPS private-storage/scanner path is validated.

Acceptance for this slice: only the active managed learner on the assignment's recipient snapshot can save or submit; draft save persists the exact escaped-as-text response; final submit transitions once and stores one acknowledgement; duplicate finalization does not create a second submission or acknowledgement; the late rule and hard cutoff are enforced server-side; a submitted record cannot be edited; learner status and acknowledgement are shown after refresh; teacher view can distinguish draft from submitted and late work without exposing other schools' records. Focused SQLite coverage is required for the local demo; PostgreSQL verification remains open until connection configuration is resolved.

Create school lesson/assignment records separately from private legacy AI document/chat records. Evaluate reuse of quiz scoring concepts after addressing ownership, publication, concurrent submission and unmarked-score semantics. Do not weaken the existing private-owner policy to make a whole school a quiz owner. A short bounded refactor may extract deterministic scoring, with legacy regression tests; no requirement to finish AI generation work.

SP5-01 includes private classroom-resource uploads. Owner-approved lesson-resource baseline: private PDF, DOCX, TXT and sanitized/re-encoded JPEG/PNG, max 10 MB each. The separate max 3 submission attachments remains proposed for SP5-03. Also owner-confirmed: 500 retained resources and 2 GiB per school, with an 80% warning; private ClamAV `clamd` subject to hosting validation; uploader rights attestation; and 90-day recovery followed by file-byte purge while retaining history metadata. The schema/state design and implementation scope were approved on 28 September 2026. Files remain quarantined until validation and malware scanning succeed, and the application fails closed when scanning is unavailable. Block user-supplied archives, executable/active formats and public links. No converter/viewer or scanning package was added.

Acceptance: teacher A cannot publish to teacher B's unassigned class; withdrawn material cannot be newly downloaded; assignment edition remains stable; duplicate/concurrent final submission settles once; learner attempts never expose hidden answers; unmarked/manual items produce pending totals; all flows work with AI provider credentials absent. Expiry of a personal plan cannot disable school-assigned work.

Reference study and adaptation notes: [App-School-Management, Skuul, and LAVSMS findings](../case-study-code-findings.md#cross-repository-conclusion) and the [case-study-informed implementation sequence](case-study-informed-implementation.md). Their grade-entry and result screens inform workflow review; Nova keeps tenant scoping, server-side scoring, versioning, and personal-quiz ownership in its existing service/policy boundaries.

## 10. SP6 — assessment records and report publication

Requirements: NS13 plus NS08; depends on SP3 and SP5. Estimate: 10–15 days.

| Task | Implementation target | Done when |
| --- | --- | --- |
| SP6-01 | Assessment periods, rubrics/scales and markbook | Qualified reviewer configures a selected pilot template; absent, exempt, not-assessed and zero are distinct; official-school mark records are separate from practice |
| SP6-02 | Moderation and reports | Teacher submits marks, authorized academic lead reviews and publishes a frozen report version; arithmetic/weights use documented rules and reject incomplete/invalid totals |
| SP6-03 | Guardian release and amendments | Guardian downloads/views released report only; corrections create a new approved version with old version retained/revoked visibly; exports support the school's process |

Do not hard-code all Kenyan grade bands into a percentage-to-CBE conversion. No claim of official KNEC/KEMIS integration. Support one reviewed report format first and record curriculum/version/grade applicability. Use printable HTML initially; a server PDF dependency is a separately evaluated implementation choice, not a prerequisite for a browser print-to-PDF pilot. Acceptance covers grade boundary values, incomplete marks, concurrent publication, amended rubrics, unauthorized early access and term promotion.

Reference study and adaptation notes: [case-study code findings](../case-study-code-findings.md#1-app-school-management) and the [case-study-informed implementation sequence](case-study-informed-implementation.md). The repositories show weighted detail rows, tabulation, and printable reports, but none supplies the moderation and immutable publication lifecycle required by NS13.

## 11. SP7 — money integration, school contracts and R1 release

Requirements: NS14, NS16–NS18 and all NQ requirements; depends on SP3–SP6. Estimate: 15–22 days. Begin merchant/email/SMS onboarding during SP0; external readiness can take longer than this engineering allowance.

| Task | Implementation target | Done when |
| --- | --- | --- |
| SP7-01 | Payment adapter/events/reconciliation | Fake adapter then supported provider sandbox; merchant identity is server-controlled, callbacks persist durably, verification and replay handling are exercised; wrong/late/missing references enter review |
| SP7-02 | School billing account/contracts/access grants | Authorized operator invoices school and records verified subscription payment; dated package/seat grants cover school learners; renewal, grace, restriction and export behavior are explicit and auditable |
| SP7-03 | School merchant receipt integration | School fees continue to settle to school merchant; matched events enter SP4 reconciliation; no subscription entitlement granted from tuition receipts |
| SP7-04 | Email/SMS delivery and cost controls | Provider onboarding complete, per-school cap and billable-unit visibility tested; failures and stale-recipient suppression surfaced; SMS cost not silently included without limit |
| SP7-05 | Production-like verification and operational runbooks | Isolation suite, PostgreSQL concurrency, migration upgrade, backups/restore, queue recovery, accessibility/mobile and load checks pass; support owner and alert route assigned |
| SP7-06 | R1 onboarding and controlled cutover | Two partners imported with sign-off; roles trained; privacy/contracts ready; short parallel operation reconciles; cutover/rollback criteria signed; one reporting/billing cycle observed |

Fallback when a school merchant integration is delayed: scope the pilot contract explicitly to SP4's authorized manual recording against school evidence, with daily reconciliation and no “automatic M-Pesa” claim. Nova's own school subscription can be settled by a verified bank/M-Pesa transfer with audited operator confirmation before self-serve checkout exists. This does not permit fabricated payments or unverified references. The live pilot still needs a payment and refund operating procedure; an unavailable automated channel cannot silently pass its gate.

Proposed school expiry policy to settle before sale: contract specifies grace; during grace, current educational/safety workflows continue; after grace provide controlled read/export and a staff-led exit, not abrupt deletion. Exact days and ongoing boarding access are commercial/operational decisions with owner and school. Restriction cannot grant public access or bypass safeguarding. No automatic renewal/debit assumed.

R1 go/no-go: no critical access/payment defects; all migrated balances reconcile; real delivery/support available; tested restoration; independently completed staff workflows; privacy readiness; scope disclosed. R1 is controlled pilot status, not general availability.

## 12. SP8 — boarding and mixed-school operations

Requirements: NS15; R1 core plus a boarding partner. Estimate: 10–15 days.

| Task | Implementation target | Done when |
| --- | --- | --- |
| SP8-01 | Houses/dorms/beds and dated allocations | Capacity and overlapping assignments checked atomically; learner day/boarding changes preserve past residence and fee entries |
| SP8-02 | Roll calls and discrepancies | Authorized duty staff records expected resident cohort; leave/return modifies expected presence; unmarked/absent/off-site remain distinct; handover shows unresolved cases |
| SP8-03 | Leave approval, release, return | Responsible approver, authorized collection details, actual release and actual return recorded separately; cancellation/repeated action/revised expected return are safe |
| SP8-04 | Overdue escalation and outage rehearsal | School owns contact/escalation; alerts are deduplicated and failure visible; printable register and phone/paper fallback tested; back-entry retains actual event and recording times |

Acceptance: simultaneous bed allocation, return before release, repeated gate action, withdrawn guardian/collector authority, overdue return, correction, term rollover, fee change and outage drill. No health/discipline dossier or GPS required. Boarding automation is not permitted to become the school's sole emergency process. No live R2 until partner staff validate these flows.

## 13. SP9 — paid learning for non-subscribing schools

Requirements: NS10–NS12, NS14, NS16–NS17; depends on SP5, SP7 and content readiness, not SP8. Estimate: 8–12 days excluding author/reviewer time.

| Task | Implementation target | Done when |
| --- | --- | --- |
| SP9-01 | Independent family onboarding and licence catalogue | Guardian creates/verifies learner relationship without a customer-school membership; self-reported school is metadata, never permission; only licensed Nova catalogue is visible |
| SP9-02 | Learning purchase/access lifecycle | Payer, benefiting learner, product/version and dates are explicit; verified payment grants exact access; expiry/renewal/refund/reversal/failed payment tested |
| SP9-03 | School coverage transition | Existing learner joining a paying school is linked through verification; checkout shows overlap and prevents duplicate purchase; owner-approved credit/pause/period-end policy applied without rewriting receipts |
| SP9-04 | Content and demand gate | First bounded pack independently reviewed/licensed, prices and limits disclosed, guardian journey tested; collect purchase and renewal evidence before expansion |

Self-check/objective practice is included; individually marked work is not promised to independent learners without a separately funded service. Track content review and delivery support costs even with zero AI calls. Existing KES 299/699 plans and history are preserved; new school/Learn prices need a separate catalogue decision before paid sale. No seed update in this documentation task.

## 14. First ten working days: executable starting backlog

This is a focus window, not a promise that all of SP1 fits inside two weeks. No repeated approval of the school direction is needed.

| Order | Developer work | Owner work | Demonstrable result |
| --- | --- | --- | --- |
| Days 1–2 | SP0-01: inspect baseline, run existing tests/build in isolated environment, reconcile schema visibility; write result and unresolved failures | REC-01 partner shortlist; assign school-operations reviewer if available | Reproducible baseline and synthetic demo cases |
| Days 3–4 | SP0-02/03: implement provider-independent release boundary and documented fixtures | Begin interview invitations; specify availability for demos | New school contexts cannot invoke AI; legacy data preserved |
| Days 5–7 | SP1-01: school/member/role tables, factories, controlled provisioning and policy contracts | REC-02 interviews using recent tasks, not feature wish lists | Two synthetic schools with separate staff membership |
| Days 8–10 | SP1-02: context middleware, first scoped route/view and cross-school tests | Review first demo; request redacted template samples | One authorized school page and demonstrated denied cross-school access |

Then finish SP1 invitation/audit coverage before learner imports. Any baseline failures discovered on days 1–2 adjust the window explicitly; do not spend it decorating a dashboard before permissions exist.

## 15. Verification and release evidence

Use existing PHPUnit feature-test conventions and factories, Laravel fakes at external boundaries, and real database behavior for local invariants. Follow the project's existing `RefreshDatabase` convention rather than mixing a new test setup merely for this plan. No new browser testing dependency is assumed. Manual mobile/keyboard/shared-device checks have named task scripts and recorded outcomes.

Each ticket should record implementation paths, request behavior, negative roles/tenant cases, failure/retry behavior, relevant PostgreSQL concurrency evidence and actual verification. For PHP changes run the narrowest `php artisan test --compact <path>` coverage, affected legacy regressions, then `vendor/bin/pint --dirty --format agent`; build assets when frontend files change. For migrations, test fresh and upgrade paths against an isolated PostgreSQL database. Run broader release regression at R1/R2/R3, not on every copy-only edit. Never point destructive test reset traits at real school data.

Final phase checks are in [verification and operations](../verification-and-operations.md). Test the product working with AI unavailable. Record unrun tests as pending; passing legacy generation tests does not validate school finance or tenancy.

## 16. Migration, rollout and control of scope

Use additive migrations. Existing private quizzes/documents/chats and accepted subscription periods keep their owner and remain outside school sharing. Introduce new school and publication tables by phase; backfill new account metadata in bounded restartable batches before enforcing constraints. Audit self-service account deletion and existing cascades before any institutional linkage. Do not reset a database or rewrite shared migration history to simplify the pivot.

School onboarding: authorize source → stage/import preview → resolve duplicates → school signs counts/opening balances → role training → short parallel trial → reconcile → controlled cutover. Define a rollback point and post-cutover forward-fix path; returning to old software after new receipts/submissions exist requires exporting/reconciling those deltas. Do not restore a stale backup over new payments.

Initial exclusions: full payroll/accounting, transport GPS, clinic/welfare dossiers, stores/library ERP, automatic timetable optimization, SCORM/LTI integration, live video classes, public tutor/resource marketplace, native mobile apps, offline sync, nationwide government integrations and AI expansion. Add only through a scoped change with cost and acceptance impact. Boarding and independent learning are scheduled scope, not excluded indefinitely.

## 17. Decisions and readiness gates

| Decision/work item | Current position | Owner and latest safe point |
| --- | --- | --- |
| Direction, AI priority and personal subscription audience | Accepted by owner | Do not re-request strategic approval |
| One-developer baseline; no existing partner | Confirmed by owner during this task | Recruitment starts alongside SP0 |
| Initial pilot school band/content grade | Primary day/Grade 5 maths planning assumption; boarding follows | Owner + educator + partner, before school-specific SP4/SP6 templates |
| Staff/guardian identity and child-data process | Technical path proposed; operational review needed | Owner/privacy adviser, before real child data |
| Fee/refund rules and opening balance authority | Template in SP4; validate with bursar | School finance lead, before live ledger |
| Commercial prices, grace/exit, overlap and content rights | Not approved by prior example prices | Owner/content lead, before corresponding paid release |
| Merchant, email and SMS access | Not confirmed | Owner/provider, start in SP0; block only dependent live capabilities |
| Support staffing, response window, recovery hosting | Not contracted or tested | Owner/developer, before R1; boarding-specific readiness before R2 |

No external gate prevents a developer from starting baseline, AI containment, tenant isolation and synthetic-data tests. No amount of completed code substitutes for the live-data and school-readiness gates.

## Planning verification record

Documentation-only checks passed across 23 Markdown files and 68 local links; 18 NS functional requirements, 5 NQ quality requirements, 10 phases and 43 unique implementation tickets were checked. Seventeen effort/content/cost calculations were independently recalculated, including 25% contingency totals. `git diff --check -- docs` passed. Readiness/status and source limitations were reviewed; the implementation log records the outcome. Application tests and provider integration tests were not run in this planning turn. Skills informed conventional Laravel boundaries and behavior-focused test gates; they did not install packages or change application code.
