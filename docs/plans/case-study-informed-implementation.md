# Case-study-informed school implementation plan

Date: 24 September 2026. Status: proposed implementation sequence; no application code changed in this study.

## Purpose

Turn the three static repository studies into practical guidance for Nova Scholar's open school workflows. This plan elaborates SP5–SP6 and connects them to SP3–SP4 and SP7. It does not replace the [school platform implementation plan](school-platform-implementation.md), change its estimates or requirements, or authorize dependency and architecture changes.

## Inputs and constraints

- Requirements: NS08–NS13, NS17, and NQ01–NQ05 in [`requirements.md`](../requirements.md).
- Existing roadmap: SP0–SP7 sequence and pilot gates in [`school-platform-implementation.md`](school-platform-implementation.md).
- Findings: [`case-study-code-findings.md`](../case-study-code-findings.md).
- Existing Nova foundations to build on: school context/memberships, `TeachingAssignment`, dated class membership, verified guardian links, attendance, fee schedule/charge/receipt/allocation services, current Filament school pages, and private personal quizzes.
- Reference-only constraints: do not copy code; App-School-Management's license text is missing; Skuul/LAVSMS code is licensed MIT but carries patterns that do not match Nova's tenant and finance guarantees. Nova's Laravel 13, Livewire 4, Filament 5, service, request, policy, and test conventions remain authoritative.

## Recommended delivery order

Follow the existing one-developer order: finish current FI-08 and planned SP3/SP4 work, then complete SP5, SP6, and SP7. SP5 depends on SP2; SP6 depends on SP3 and SP5; SP7 depends on SP3–SP6. Do not advertise R1 readiness while SP7 operational and partner gates remain open.

## Proposed page and data-flow contract

The case-study page/login/navigation traces are recorded in [`case-study-code-findings.md`](../case-study-code-findings.md). Use the references to validate the user journey, while Nova's current Breeze session, separate Platform and School Filament workspaces, tenant-aware school pages, guardian portal, and managed-learner access remain the architecture. These flows are proposed SP5–SP6 behavior; they have not been implemented by this study.

| Actor and page sequence | Data operation and state change | Required boundary and completion check |
| --- | --- | --- |
| School staff signs in → opens the School workspace → selects/enters an authorized school → sees the overview and permitted navigation | Resolve the authenticated user and the school tenant before loading any school record; derive menu items from the existing role/policy checks | Test a school admin and teacher landing path, a user with multiple memberships, a stale/inactive membership, and a user without school access. Hiding a menu item must not replace page/action authorization. Preserve Platform workspace routing for platform admins. |
| Teacher opens assigned classes → chooses subject/class/term → saves lesson draft → previews and publishes | Create a draft lesson and versioned resources linked to the school, teaching assignment, subject, class, and term; publication makes a defined version visible to its intended cohort | Reject a foreign-school class/subject, unassigned teacher, and unauthorized resource download. Show draft/published status and the next action. |
| Learner opens assigned work → views instructions/resources → saves draft → submits | Create an immutable assignment edition and recipient snapshot; save submission versions, server-recorded final-submission time, and the resulting on-time/late state | Only a current eligible learner recipient can read or submit; retry is idempotent; answer keys/private resources stay hidden. Show assigned, draft, submitted, late, and awaiting-marking states distinctly. |
| Teacher opens submissions → marks with the approved rubric → saves feedback → releases feedback | Persist criterion scores and feedback against a submission version; release is an explicit state transition | Only an assigned teacher/authorized reviewer may mark; validate against the approved rubric; learner access to feedback begins only after release. Keep unmarked separate from zero. |
| Teacher submits official marks → academic reviewer moderates → authorized publisher releases reports → guardian opens a linked learner report | Store official assessment records separately from practice, record moderation decisions, then create a frozen report version with the calculation/template version and release timestamp; amendments create a new version | Cross-school, unlinked-guardian, pre-release, revoked-version, and stale-membership requests are denied. Concurrent publish creates one valid released version. |
| Bursar opens Fee Operations → reviews charges → records receipt → allocates it → checks balance/statement | Continue the existing fee schedule, charge, receipt, and allocation services; add later reversals/statements/reconciliation as separate audited actions | Keep the reference receipt screens as UX comparisons only. Preserve immutable receipt history, school scope, idempotency, and concurrency guarantees. |

For each journey, implement the service and authorization boundary before connecting the page. Add the navigation item only after the destination page and policy exist. Verify the full role path from login to the persisted result, including a denied path, instead of treating a visible menu entry or a successful form response as completion.

| Step | Work and concrete output | Dependencies and reference | Acceptance evidence |
| --- | --- | --- | --- |
| 0. Confirm pilot rules | Obtain one redacted or synthetic report example and educator/bursar review. Record the target grade band, subjects, term, mark categories, weights, rounding, missing/exempt states, moderation roles, correction policy, and guardian release rules in the decision register before schema freeze. | REC-03; needed before SP6. App-School-Management shows a configurable-looking weighted result but embeds Indonesian KKM/grade bands; do not adopt those rules. | One approved pilot template and a written calculation example with zero, absent, exempt, and incomplete cases. |
| 1. Close shared prerequisites | Continue the remaining FI-08 work and SP3 delivery/read-state gaps; carry SP4 opening balances as an explicit live-ledger gate. SP4 corrections, statements and reconciliation now have local behavior and synthetic browser evidence. Keep school tuition separate from Nova subscriptions. | Existing SP3/SP4 tasks; Skuul/LAVSMS offer workflow comparisons only. | Existing acceptance and reconciliation evidence recorded in the implementation log; no unexplained finance variance. |
| 2. Design SP5 around Nova's current models | Map courses/lessons to school, subject, class, term, and authorized teaching assignments. The SP5-01 design includes private lesson uploads, immutable versions, per-request authorization and fail-closed scanning. The owner accepted PDF/DOCX/TXT/sanitized JPEG/PNG and 10 MB per file. Resolve per-school quotas, scanner operations, rights/retention, and assignment due/cutoff, late work, extension, resubmission, and acknowledgement behavior before the affected migrations. | SP2 foundations; use App Filament form organization and Skuul/LAVSMS teacher journeys as UX references. | Owner/educator/IT review of the domain sketch and state transitions; tenant, storage and ownership invariants written before code. |
| 3. Build lesson authoring and publication | Add additive migrations and school-scoped models/services/requests/policies for draft and published lesson versions and private resources. Add Filament 5 pages for assigned teacher authoring, preview, and publish. Serve downloads only after current membership/assignment authorization. | SP5-01; existing school service and Filament page conventions. | Teacher can publish to an assigned class; another teacher/school is denied; withdrawn resources are no longer downloadable; resource policy and upload constraints are documented. |
| 4. Build assignments and submissions | Publish an immutable assignment edition with a recipient snapshot. Add learner draft/final submission versions, server-recorded times, late/extension state, durable acknowledgement, and idempotent retry behavior. Keep answer keys and private attachments out of unauthorized responses. | SP5-02/03; references have exams/marks but no equivalent recipient-snapshot submission lifecycle. | Cross-school and unassigned-class denials; duplicate and concurrent submit behavior; deadline boundary, extension, retry, and resubmission tests pass on PostgreSQL. |
| 5. Add marking, practice, and progress | Add manual rubric/question marking and feedback release states; distinguish not-yet-marked from a score of zero. Review existing personal quiz scoring before extracting any shared deterministic scorer; preserve private quiz ownership and keep AI disabled. Show assigned, started, submitted, awaiting marking, released, and completed states separately. | SP5-04/05/06; App and Skuul scoring screens are flow references, not official policy. | Hidden answer keys remain private; concurrent finalization is safe; feedback is hidden until release; school assignment access does not depend on a personal subscription. |
| 6. Build SP6 formal assessment and reports | Store official school assessment marks separately from practice. Implement the approved pilot scale/rubric and missing-result states. Let teachers submit marks, an authorized academic reviewer moderate them, and an authorized publisher create a frozen report version. Deliver only released versions to verified guardians; amendments create a new audited version. Start with printable HTML; assess server PDF only if the pilot requires it. | SP3 and SP5; use App's component/detail relationships and Skuul/LAVSMS tabulation/print journeys for comparison. | Weighted totals recalculate server-side; boundary and incomplete cases are correct; cross-school and premature guardian access are denied; publication, amendment, and revocation retain history; concurrent publish has one valid result. |
| 7. Complete R1 release gates | Join learning/report workflows to the existing notices, fees, support, contracts, provider onboarding, recovery, and onboarding runbooks. | SP7; no clone substitutes for provider or school evidence. | Required NS/NQ gates, restore and reconciliation evidence, role training, partner sign-off, and one reporting/billing cycle are recorded before R1. |

## Proposed code touchpoints

These are likely areas, not a frozen file list. Confirm exact names and sibling patterns during SP5 design.

- **Existing models to relate:** `School`, `TeachingAssignment`, `ClassGroup`, `Subject`, `Term`, `Enrolment`, `LearnerClassMembership`, `GuardianLink`, `Quiz`, `Question`, and `QuizAttempt`.
- **Likely additions:** additive migrations and models for school courses/lesson versions/resources, assignment editions/recipients, submissions/attachments/feedback, assessment periods/mark records, moderation decisions, and published report versions.
- **Application boundaries:** focused services under `app/Services/Schools`; Form Requests under `app/Http/Requests`; policies under `app/Policies`; native school Filament pages under `app/Filament/School`; learner/guardian views and routes following current access boundaries.
- **Verification:** focused PHPUnit feature tests under `tests/Feature` for actor/tenant/class access, invalid state changes, lifecycle transitions, and guardian visibility; PostgreSQL concurrency tests for assignment finalization and report publication where simultaneous writes can occur. Add UI tests using existing project conventions. Run Pint for modified PHP files and record actual results; none of these future checks has been run for this plan.
- **Documentation:** update the data model and architecture when the schema and publication behavior are approved, then update operational instructions for file access, review/release, corrections, and school support.

## Unresolved decisions before the affected implementation

| Decision | Needed by | Owner/input |
| --- | --- | --- |
| Default post-login destination and school selection for users with multiple roles, multiple school memberships, or no active membership | Before SP5 page/navigation acceptance | Owner, with current Platform/School panel rules preserved |
| Initial grade band, report layout, subjects, mark categories, weight/rounding, and competency language | Before SP6 schema freeze | Owner, educator, pilot school |
| Meaning and display of absent, exempt, not assessed, incomplete, and zero | Before assessment calculation | Educator and school academic lead |
| Who may author, submit, moderate, publish, revoke, and amend | Before SP5/SP6 policies | Owner and school leadership |
| Late work, extension, resubmission, and durable acknowledgement rules | Before SP5 submission lifecycle | Educator and pilot school |
| Guardian progress detail and released-report retention | Before guardian views | Owner/privacy adviser and school |
| Per-school resource count/quota, scanner operations, school-use rights metadata, and retention/deletion/backup behavior | Before SP5-01 implementation or use of real school materials | Owner, pilot school IT, educator/content owner, privacy adviser; accepted formats and 10 MB per-file baseline are recorded in the SP5-01 plan |
| App-School-Management license text | Before copying any code from that repo | Verify the upstream repository's actual license; source reuse is not needed for the plan |

## Planning status and next action

No application implementation has started from this plan. The [SP5-01 lesson/resource design](sp5-lessons-and-resources.md) now includes the owner-approved private-upload scope and records the initial course/publication/access defaults. Synthetic browser closeout for SP4 refunds, statements and reconciliation is complete; broader FI-08, opening balances, hosted capacity, and SP3 delivery/read-state remain open. Next, resolve the resource quota/type, scanning, rights and retention decisions, obtain the redacted or synthetic report example under REC-03, and approve the SP5-01 schema/state design. SP5 code begins only after that design is approved under the repository's major-module workflow.
