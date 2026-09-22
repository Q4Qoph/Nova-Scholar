# School platform delivery study: workflows, service and non-AI learning

Date: 21 September 2026. Status: desk research complete. The owner has accepted school administration plus e-learning as the direction and moved AI out of the priority scope. The detailed release sequence, estimates and commercial assumptions below are planning recommendations. This report follows the [combined business study](school-management-learning-strategy-study.md); implementation work is defined in the [active plan](plans/school-platform-implementation.md).

## 1. What the product should now deliver

Nova Scholar should help a school maintain reliable learner records, handle attendance and fees, communicate with guardians, distribute teaching material, collect work and publish reviewed results. Day and boarding schools share the academic and administrative core; boarding adds residence and leave/return workflows. Students at non-subscribing schools buy access to a separately licensed learning catalogue. Students covered by a school contract receive the agreed learning offer through that contract.

E-learning does not require an AI tutor. Its first complete journey is **lesson → assignment → submission → marking → released feedback → further practice**. A teacher can write questions, reuse licensed material, score objective questions with fixed answer keys and mark open responses manually. The system's value is reliable delivery and follow-up. Generated lessons, tutoring, embeddings, OCR for AI retrieval and AI grading are deferred and are not release prerequisites.

The product direction is decided. Discovery now refines workflows, prices, first cohort and service delivery; it is no longer a reason to reopen whether schools belong in scope. Safe foundation implementation can proceed with synthetic data while school recruitment, privacy review and provider access are arranged.

## 2. Further evidence and its implications

Sources were examined on 21 September 2026. Official product pages describe advertised functionality, not independently established quality. No competitor account, customer contract or live school data was accessed.

| Evidence | Consequence for the plan |
| --- | --- |
| Zeraki Analytics advertises attendance, contact records, assessment entry, report exports, parent access and on-site training. Its finance/learning pages were examined in the preceding study | These are baseline competitor expectations. Measure task completion, corrections and training needs; do not position “one dashboard” as unique. [Analytics](https://www.zeraki.app/zeraki-analytics) |
| Moodle's assignment documentation distinguishes due dates from submission cutoffs and supports text/file submissions and feedback. Its features catalogue includes learning progress and course organization | Specify these states deliberately in Nova's smaller LMS. A file library without submission and feedback is not a complete learning workflow. Moodle remains a build-versus-integrate benchmark, not an installed dependency. [Assignment settings](https://docs.moodle.org/502/en/Assignment_settings), [features](https://docs.moodle.org/502/en/Features) |
| LaiEduHub advertises academic, finance and digital-learning suites together; its term/annual and modular pricing distinctions need quotation confirmation | Keep software breadth modest and cost service separately. Reusing its public price as proof of Nova's viable price would be unsound. [Pricing](https://www.laieduhub.com/pricing) |
| KNEC's indexed 2026 Grades 4–5 practical/project circular directs headteachers to its CBA portal. Direct PDF retrieval timed out | Nova must distinguish internal assignments/results from official assessment submission. No KNEC integration, accreditation or automatic submission is claimed. [Official circular](https://www.knec.ac.ke/wp-content/uploads/2026/02/SBA-Assessment-Schedule-2026-Grade-4-5-and-SNE-SBA-Practicals-and-projects.pdf) |
| KICD's official downloads page lists standards for online supplementary materials, educational mobile applications and OER/user-generated content | Assign a qualified content lead to inspect the applicable current standards and licensing before selling catalogue content. Listing a curriculum outcome is not approval of the product. The linked standards were not fully audited in this study. [Downloads](https://kicd.ac.ke/downloads/page/3/) |
| MoE describes its 2024 boarding-school safety assessment against school safety and registration standards | Boarding software must support accountable staff and fallback procedures; software cannot certify premises or replace physical safety management. The announcement is not a complete current legal-compliance audit. [MoE assessment](https://www.education.go.ke/node/802) |

Retain the prior study's ODPC and Safaricom findings and retrieval limits. Before real child records, assess the service against complete current education/children's guidance, including rights, retention, guardian authority and processor responsibilities. Before payment integration, confirm the school's merchant access and supported verification flow. [ODPC education guidance](https://www.odpc.go.ke/wp-content/uploads/2024/02/ODPC-Guidance-Note-for-the-Education-Sector.pdf), [ODPC children's guidance](https://www.odpc.go.ke/wp-content/uploads/2025/11/ODPC-%E2%80%93-Guidance-Note-for-Processing-Childrens-Data.pdf), [Safaricom](https://developer.safaricom.co.ke/).

No national market forecast is necessary to begin this implementation. The useful next evidence is a school's current register, redacted statement structure, report template, teaching workflow, authority to buy and available devices. Public statistics cannot supply those details.

## 3. Build a narrow native learning module; keep an integration option

| Option | Benefit | Cost/risk | Recommendation |
| --- | --- | --- | --- |
| Native school core and focused LMS in existing Laravel app | Shared authorized rosters, guardian links, billing and support; one coherent workflow | Nova must implement publishing, submissions and marking correctly | Plan baseline for the bounded first release |
| School core plus Moodle integration | Access to an established, broader LMS | Identity/roster synchronization, grade ownership, hosting, upgrades and two support surfaces | Reconsider if pilot requirements demand mature LMS breadth |
| Management only; external lesson links | Lower initial learning build | Cannot deliver the requested assignment/submission/reporting or independent subscription alone | Useful supplementary links, insufficient as the product |
| Full custom ERP plus full LMS immediately | Broad catalogue of modules | Excessive delivery and support burden before validation | Excluded from first release |

This recommendation is engineering judgment, not a completed cost benchmark against a Moodle deployment. No Moodle, tenancy, permission, spreadsheet or PDF package is being installed. If schools require SCORM, LTI, exam proctoring, complex group assessment or rich media authoring before purchase, pause expansion of the native LMS and perform a bounded integration proof before committing to build equivalents.

## 4. Workflows that should earn a school renewal

### Learner admission and term changes

Import a CSV into staging → show errors and possible duplicates → staff resolves them → authorized operator confirms → learner is enrolled in the correct year/class → verified guardian receives access. A repeat import must not duplicate learners. Import errors must identify the source row without exposing another school's records. Never merge children by name alone.

At term change, create a new enrolment/class history and new fee charges. Preserve last term's attendance, results, published reports and balances. A learner can change day/boarding status on a date without rewriting history. School names and admission numbers cannot grant personal-account access on their own.

### Attendance with useful exceptions

Teacher opens the assigned class → records present/absent/late/excused → sees save confirmation → unresolved absences enter an exception list → designated staff validates and contacts guardians → corrections retain actor, time and reason. Absence alerts need a confirmation policy to avoid sending a false emergency message before the register is complete.

Measure time for a full class register and correction, not just page load. The guardian should see a clear date/status, and delivery failure must remain visible to school staff. No attendance biometrics or automated discipline scores are needed.

### Fees with explainable balances

Bursar configures term charges → reviews draft batch → posts once → records or imports independently verified receipts → allocates payments → reviews unmatched/overpaid cases → releases statements. Receipts can cover siblings; allocating money must not create additional money. Use explicit adjustments and reversals rather than editing a posted transaction invisibly.

The first finance module is a fee subledger, not payroll, tax filing or full school accounting. Support cash/bank/M-Pesa records with source and reconciliation status. Manual recording is allowed only through authorized staff against evidence; a parent's uploaded screenshot or typed reference must never automatically settle a charge. School fees settle to the school's account; Nova subscription income has separate records.

### Teaching and assessment

Teacher creates a course for an assigned class and subject → adds ordered lessons/resources → drafts an assignment or fixed-answer practice → previews as learner → publishes a frozen version → learners submit text, approved files or objective responses → teacher reviews open responses → results are released.

Preserve submission receipts, late status, teacher-authorized extensions and revision history. Retrying after a connection failure must not create duplicate attempts. Show “submitted, awaiting marking” rather than zero for unmarked work. A fixed-answer practice score is separate from the school's official term assessment. Changes to published report marks require reviewed amendments, not silent recomputation from a changed rubric.

### Boarding operations

School allocates a learner to a bed for a date range → responsible staff completes dormitory roll call → discrepancies appear for human review. For leave: request → approval → actual gate release → expected return → actual return; overdue status triggers the school's contact/escalation procedure. Approval and actual departure are different events.

Concurrency matters: two staff cannot reserve the same bed or release the same learner twice. Record cancelled leave, changed return dates and day/boarder transitions. Provide a printable duty register and phone/paper procedure for outages, followed by audited back-entry. Native offline synchronization and 24-hour vendor emergency dispatch remain out of scope.

## 5. Service design and measurable differentiation

The following are proposed targets for the pilot, not claims that competitors fail them or promises backed by a staffed SLA.

| Promise to test | Demonstration/evidence | Initial target |
| --- | --- | --- |
| Staff get productive quickly | Time an administrator, bursar and teacher on their own tasks after training | At least 4 of 5 representative adults finish each key task without developer intervention |
| Balances are understandable | Compare statements against signed opening balances, receipts and adjustments | Zero unexplained discrepancies before finance cutover |
| Less repeated work | Observe register entry, statement creation and assignment follow-up before/after | At least 30% less time on two recurring tasks, without higher error rate |
| Useful parent access | Guardian finds the right child's statement and released feedback | At least 4 of 5 tested guardians complete both tasks; no sibling/account leakage |
| Reliable classwork | Trace published assignment through submission and released feedback | Every scripted retry, late submission and unmarked-work case resolves correctly |
| Predictable support | Log contact reason, minutes, resolution and repeated problems | Target routine support below 2 hours/school/month after onboarding; validate staffing/cost before contracting |
| Schools can leave cleanly | Export and reconcile roster, finance, reports and owned content | Authorized export contains all scoped records and no other school's data |

Proposed service bundle: a data-readiness checklist, one controlled import, two role-based training sessions, named school champion, a scheduled check-in during the first month, and a written support window. Quote extra data cleanup, additional training and SMS separately. Do not advertise 24/7 support because an incumbent does; commit only to a service the team can deliver.

Select two day-school partners and one boarding/mixed partner if available, with a school champion and authority to adopt the scoped workflow. Grade 5 mathematics remains a content-production assumption; confirm it with the first partner. The administration core can roster other grades, but supported reports and teaching content must be listed explicitly. Senior-school pathways and legacy curricula require templates and qualified review before coverage claims.

## 6. Content operations without AI

School-authored material can make the institutional LMS useful quickly. The independent learner product needs additional Nova-owned or licensed content, and cannot depend on access to a customer's private files.

Proposed first independent pack: one grade/subject, four reviewed topic units, each containing three short lessons, ten objective practice items and one manually specified review activity. This gives 12 lessons, 40 objective items and four review activities. Treat it as a bounded four-week pack and state its coverage, not as a complete year's curriculum. For continuing subscriptions, publish a credible expansion schedule or offer a fixed-duration pack until repeat value exists; do not sell unlimited human marking at a small automated-service price.

Workflow: commissioned author → separate educator review → rights check → accessible format check → publication → error report → correction with versioning. Learner attempts reference the published version. Manual subject-expert feedback for school assignments is the school's responsibility; direct-catalogue open activities include worked answers/self-check unless paid human review is explicitly staffed and priced.

No-AI delivery removes model inference as a prerequisite, but content, storage, bandwidth, marking and support still cost money. Prefer text and small downloadable resources. Permit approved external video links with a privacy/content policy before considering video hosting; do not promise video streaming or automatically embed tracking-heavy third-party content in child pages.

## 7. Updated economic test

The earlier KES 3,000/5,000 school and KES 99/199 personal experiments remain unapproved hypotheses. They are not changed in seed data. Separate the software fee from onboarding, messaging and custom services. Removing AI costs does not validate any price.

Illustrative one-school monthly service economics at KES 5,000: KES 600 hosting/storage allocation + 2 support hours × KES 500/hour + KES 400 messaging allowance = KES 2,000 delivery provision, leaving KES 3,000 before content allocation, payment/refund/tax effects, sales and fixed development costs. If support takes 6 hours, that provision becomes KES 4,000, leaving KES 1,000. These are invented planning inputs, not supplier quotes or measured margins. Record founder support time as cost.

Illustrative direct learner at KES 149: assume KES 30 for variable distribution, payment and support provision and KES 40 for amortized content maintenance; KES 79 remains before other deductions/fixed costs. At 100 paying learners that is KES 7,900; at 1,000 it is KES 79,000, only if the assumed costs and renewals hold. Upfront commissioning requires cash before these contributions exist. Reprice if limited active subscribers cannot cover the catalogue.

Institutional use and independent learning need separate dashboards: contracted schools, active school learners, collected institutional subscriptions, independently paying learners, repeat purchases, outstanding invoices, support hours and content cost. Do not count school-included accounts as personal subscription conversions or count school tuition receipts as Nova revenue.

## 8. Technical findings that change delivery order

Installed-package inspection reports PHP 8.4, Laravel 13.32.0, Breeze 2.4.2, Livewire 4.4.5, Tailwind 4.3.3 and PHPUnit 12.5.35. Dependency constraints alone are not the installed versions. No upgrade is required by this plan.

The owner confirmed that estimates should assume one developer and that no pilot partner is yet confirmed. Recruitment is therefore an explicit parallel owner workstream. Current CI prepares SQLite; SP0 includes a separate isolated PostgreSQL lane so tenant/financial constraints are not validated solely against a different database engine.

The route/model inspection establishes these planning gaps:

- `UserRole` has Student/Admin only; school-scoped staff roles and guardianship are absent. A global role expansion alone cannot represent a teacher at one school and guardian at another.
- Current user creation/schema assumes an email, and school-age access requires a separate managed-learner path with no fake verified email.
- `EntitlementService` selects a user's subscription period. It cannot represent a school contract granting learning to enrolled children without a new access boundary.
- Quiz creation dispatches AI generation, and quiz viewing/attempts are owner-based. Reuse concepts and tests, but teacher-authored shared quizzes need a separate publication/assignment design.
- Quiz submission checks submitted status before its transaction and has no observed attempt-row lock in the inspected method. Existing sequential duplicate coverage does not establish concurrency safety. New shared assessments require concurrent-submission testing before reuse.
- The current short-answer path awards no points while the maximum includes all questions. A manually marked school task needs an explicit ungraded state and separate completion/release, rather than presenting pending marks as a final zero.
- Self-service profile deletion currently deletes the account. Institutional records need retention/deactivation rules before linking school histories to accounts.

These are bounded inspection findings, not a full security audit or newly executed test results. The schema tool returned a PostgreSQL engine with an empty table list; this result does not verify live schema state and does not overturn the recorded migration history. SP0 must reconcile connection/schema visibility using read-only checks before any migration. No database was changed.

Use existing controllers, Form Requests, policies and focused services. For the proposed implementation, verify scope with route bindings and policies; protect finance/attempt invariants with database transactions and locks; dispatch durable side effects after commit. Installed-version documentation supports these framework primitives. They do not by themselves establish tenant isolation. [Laravel routing](https://github.com/laravel/docs/blob/13.x/routing.md), [queries](https://github.com/laravel/docs/blob/13.x/queries.md), [queues](https://github.com/laravel/docs/blob/13.x/queues.md).

## 9. Research outcome and limits

Proceed with a modular school platform and a narrow native learning workflow. Build institution isolation and people relationships before business modules, deliver the day-school core before its live pilot, add boarding against a named operational partner, and launch independent learning once licensed content and direct billing are ready. The [implementation plan](plans/school-platform-implementation.md) turns this into task IDs, dependencies, effort ranges and release tests.

No interviews, timed usability tests, school contract review, provider sandbox transactions or application test execution occurred in this study. KNEC PDF content was available only through official indexed text; KICD standards were identified, not audited; prior ODPC retrieval limitations remain. Financial examples, support targets, team assumptions and content counts are proposals. The owner-approved direction and AI deferral are distinct from these unvalidated assumptions.
