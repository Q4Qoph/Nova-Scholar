# School trial entry, tasks and workflow implementation plan

Date: 1 October 2026. Status: **UT-01–05 implemented and locally verified**.

Approval record: owner instructed “approved code now.” UT-01–05 and their stated defaults are authorized. Verification and delivery outcomes will be recorded below and in the implementation log.

## Outcome and scope

Make the working school demo easy to enter and repeat: each person reaches the right workspace, sees their next tasks, retains their place while teaching/marking, and can follow school setup without guessing. Implement the recommendations from the [page-flow comparison](../case-study-page-flow-report.md) in small verified slices.

This plan covers entry/navigation, role home pages, learning-page organization and state, submission safeguards, setup guidance and trial acceptance. It does not add commercial billing, public school self-provisioning, AI, formal report calculations, unread-message tracking, offline support or new packages. Existing school fees remain available and optional. Formal school reporting gets a separate design gate below.

Approval of this plan authorizes UT-01 through UT-05 in sequence, subject to their acceptance checks. UT-06 describes owner/school/hosting-dependent trial work; deployment and real school data are not authorized by approving this UI plan. UT-07 authorizes preparation of a reporting design only after a school example is available, not implementation of its schema or grading policy.

Requirements: NS02/03/04 (roles, learner/guardian access and setup), NS06/08 (attendance and notices), NS09/10/12 (teaching, submissions and progress), NS17/NQ01 (authorization/auditing) and NQ05 (usable workflows). NS13 formal reporting remains a separate module.

## Study performed and reuse decisions

Read current requirements, school/trial roadmap, SP5 plan, comparison report, authentication/middleware, User tenant access, GuardianPortalController, learner assignment/login controllers, SchoolOverview, SchoolLearning, academic setup, relevant policies and existing route/guardian/learning tests. No `.ai/rules` directory exists. No application code was edited or reference app executed in this planning work.

Installed versions checked: Laravel 13.32.0, Filament 5.8.4, Livewire 4.4.5, Breeze 2.4.2 and PHPUnit 12.5.35; package.json specifies Tailwind ^4.3.3 and Alpine ^3.4.2. Laravel Boost documentation search confirmed tenancy/navigation, intended redirects and Livewire URL/history behavior. Exact implementation syntax will be checked again where needed during coding.

| Recommendation/evidence | Current implementation | Best fit for Nova |
| --- | --- | --- |
| School CTA opens personal registration | Registration sets `UserRole::Student`; school provisioning/invitations already exist separately | Honest assisted-trial instructions, using existing provision/invite paths; avoid inventing signup automation or a lead-capture service |
| Guardian gets personal study; learner workspace link returns 403 | `/dashboard` dispatches platform/staff and is adult-only; GuardianLink defines real family access | Shared destination resolver and neutral `/workspace` route; retain child/adult boundary on protected pages |
| Overview has no Learning card or pending tasks | Existing Filament shell and policies; submission/review/attendance records exist | Authorized Learning action and bounded scoped task summaries |
| Refresh loses teacher review | Public Livewire course/review IDs have no URL state | Explicit URL context and mode; reauthorize every read/write |
| Learning is a 3,071px mobile page | One SchoolLearning component already calls shared business services | Focused modes in that component before a larger resource/page migration |
| Feedback discovery is weak | Released-review relationship exists on submissions | Eager-load only released same-school reviews; show “Feedback available,” not “unread” |
| Setup dependencies are unclear | Existing year/term/class/subject/staff/import/placement services | Read-only setup checklist linking existing workflows; no duplicate wizard writes |
| Reports have clear outputs in references | Nova has assignment feedback, but formal assessment/publication is still planned | Agree a school template and policy before a separate SP6 design |

Reference lessons: App-School-Management's consistent resource actions, Skuul's school/filter context and LAVSMS's explicit next-step/output links. Adapt these ideas; copy no reference source or grading rules. See the comparison report for pinned commits and source/screenshot evidence.

## Proposed user journeys

- **School administrator:** school sign-in → school home → setup checklist or daily work → existing setup form → success → next checklist step.
- **Teacher:** sign-in → school home → Attendance / Courses / Responses awaiting feedback → focused authorized task → save/release → return to the same list/context.
- **Learner:** learner sign-in → learner home → assignments needing a response or feedback available → draft → confirm final submission → acknowledgement → released feedback.
- **Guardian:** adult sign-in → linked-child portal → child selection → attendance/notices. Personal study is a separate optional destination.
- **Several workspaces:** neutral workspace chooser → selected authorized school or family portal. School tenant switcher stays available.

## UT-01 — Correct entry destinations and landing page

Dependency: existing authentication, panel access and verified guardian relationships. Estimated effort: 1–2 developer-days, including targeted verification.

### Design

Add a small `ResolveWorkspaceDestination` service under `app/Services` and a thin `WorkspaceController`. Resolve current eligible destinations from relationships and existing panel access rules, not global school-role strings. Introduce authenticated GET `/workspace`, named `workspace`, as the public Open workspace and authenticated-guest fallback destination.

Keep the existing `/dashboard` named route and adult/verified protection for compatibility; delegate its adult dispatch to the same resolver. Add a separately named adult/verified personal-study destination, proposed `/study`, so someone with school/family access can still deliberately open their existing personal dashboard without a redirect loop. Existing data and personal tools are preserved.

`/workspace` is a dispatcher, not a source of school/child data. Its controller must explicitly handle active/inactive managed accounts before adult verification: active learners go to the protected learner dashboard, inactive managed accounts receive an access-unavailable outcome and never fall into an adult destination. Adults who are unverified follow the existing verification flow. Target routes retain their own authorization and membership checks.

| Account situation | Proposed default |
| --- | --- |
| Active managed learner | Learner dashboard |
| Inactive managed account | Access unavailable; no personal/panel fallback |
| Verified platform admin | Platform home, preserving current precedence |
| One active authorized staff school, no active verified guardian links | That school home |
| Active verified child links, no eligible staff school | Guardian portal |
| Multiple eligible staff schools, or staff plus guardian access | Workspace chooser listing only authorized destinations |
| Verified adult with neither staff nor active linked-child access | Existing personal-study home, with school access/invitation guidance |

The chooser uses ordinary named links and does not grant or store roles. No remembered-workspace column/session preference is required for this slice; never select an arbitrary “first” school when several are eligible. Recompute eligibility after suspension/revocation. Provide a Switch workspace link for adults; personal-study access is explicit rather than another mandatory chooser step for every user.

Preserve intended protected URLs and their authorization. Do not broadly replace `redirect()->intended()` or let an untrusted return URL bypass access checks. Check Breeze, guest middleware and Filament entry separately; their login paths differ. A guardian-only user should use adult/guardian sign-in, not gain staff-panel access.

Landing changes: primary school CTA “How to start a school trial” links to a clear on-page assisted-setup section; explain that Nova provisions the school and invites staff. Offer separate School staff, Parent/guardian, Learner and Personal study links. Label registration as a personal-study account. Local demo remains local-only. Do not publish a trial request button with no recipient or backend. Constrain the decorative negative-inset glow within its visual container rather than masking the whole document's overflow. Replace deferred-AI/internal authorization copy with relevant school-user language.

### Files and acceptance

Existing: `routes/web.php`, `bootstrap/app.php`, `AuthenticatedSessionController`, `resources/views/landing.blade.php`, adult navigation/dashboard views, `User`, `GuardianLink`, panel routing/access tests. Proposed additions: resolver, controller, workspace chooser Blade view and feature tests. Extract a reusable eligible guardian-link query only if needed to prevent resolver/portal drift.

Accept when every row in the matrix is exercised; signed-in learner Open workspace avoids 403; unverified/inactive accounts and revoked links cannot reach protected data; chooser excludes suspended schools and revoked memberships; no redirect loops; personal registration still creates no school; landing has no document-wide overflow at 320/390/768/1280px; mobile role links and keyboard focus are usable.

## UT-02 — Role home pages show work to do

Dependency: UT-01; deep links initially target existing pages and become focused URLs in UT-03. Estimated effort: 1–2 days.

Keep school identity and tenant switching prominent. Move membership/type details below primary actions. Add Learning only where `SchoolCoursePolicy::viewAny` permits it; retain appropriate admin, teacher and bursar workflows.

Introduce focused read services under `app/Services/Schools` for school task summaries, and a learner summary service if its logic is shared between home and assignment list. Reuse Eloquent relations and policy-consistent scopes. Do not load all response/lesson/resource bodies to compute counts. No caching in the first slice: revocation must take effect immediately. Use bounded lists, stable ordering and aggregate queries; inspect query volume with several classes, not only one demo learner.

Define counters before writing UI:

| Surface | Data definition |
| --- | --- |
| Teacher responses awaiting release | Submitted responses within current authorized active teaching assignments/classes whose review is absent or has no `released_at`; includes saved private feedback drafts |
| Teacher attendance entry | Direct action to the existing assignment/date selector; do not count “missing sessions” until expected sessions are defined |
| Learner response needed | Published assignment with active recipient/enrolment and no final response; show due date and whether the existing due/cutoff rules still permit submission |
| Learner feedback available | Same-school released review on an authorized submitted assignment; draft feedback never affects the badge/count |
| Guardian recent activity | Existing verified child list, recent linked-child attendance and already sent notices; do not imply new/unread tracking |
| School admin | Setup progress and authorized school workflow links; fee actions remain optional |

Treat expired unsubmitted assignments separately as Closed, not actionable “to do.” Use the school timezone. Zero score remains a real score; feedback-only reviews do not fabricate a maximum. Do not broaden guardian access to learner responses/grades as a navigation shortcut.

Files: SchoolOverview/view, LearnerSessionController/learner home, LearnerSchoolAssignmentController/list, GuardianPortalController/guardian view, proposed task-summary service(s), SchoolLearning submission/review models and existing feature tests. Changes to queries must preserve existing child/enrolment and teacher scopes.

Accept with two schools, two teachers and siblings: counts/links include only permitted records; changing membership or guardian verification removes data; saving private feedback does not expose learner feedback; release updates counts and badges; zero and no-score feedback render correctly; empty states tell the user what to do next. Summaries are bounded and do not fetch authored content merely to count it.

## UT-03 — Focused learning modes and persistent task context

Dependency: UT-02. Estimated effort: 2–3 days.

Retain SchoolLearning and its transactional write services. Add clear modes: Courses, Lessons, Assignments and Review. Courses is the default; render only the active task's forms and relevant navigation. A review destination opens the assignment roster/response editor without showing course creation, lesson authoring and assignment creation above it. Use existing Blade partial/component conventions to separate the view; avoid a new parallel set of policies or business actions.

URL context proposal: `?view=review&course=<id>&assignment=<id>&response=<id>&reviewPage=<n>`. Other modes require only applicable IDs. Bind presentation state through Livewire 4 URL properties with history for meaningful task changes; do not generate history entries on every text keystroke. Preserve roster pagination and explicit Back to assignments/responses links. IDs are locators, never permissions.

Validate an allow-listed mode and positive integer selections. Resolve course → assignment → response within the active authorized school; verify each parent relationship and current policy. Invalid/unavailable locators get a generic not-found outcome. Switching course resets child selections/pagination. Never include learner names, response text, feedback or private file paths in URLs. Do not put draft fields into query state.

On first load/refresh/history navigation, hydrate the selected editor from saved server state. Rehydrate when selection actually changes, not on every render, so typing is not overwritten. Before switching away from dirty editor state, offer Stay or Discard changes; saving remains an explicit action. Re-check authorization on Livewire updates and write services after revocation, not only in mount.

Files: SchoolLearning/view and new sibling view partials as needed; SchoolPanelProvider only if navigation URLs need changes; overview deep links; FilamentSchoolLearningTest and SchoolLearningReviewTest. Existing course/lesson/assignment/review services and schema remain authoritative.

Accept: overview deep link, reload, browser Back/Forward and pagination restore the correct authorized response/list; saved draft is loaded; unsaved changes are guarded; cross-school/course/assignment IDs and revoked teaching assignment access are denied; authored text never appears in URL; save/private/release and immutable publication behavior still pass; review is the first task content on mobile and inactive authoring forms are absent.

## UT-04 — Submission safeguards, labels and feedback discovery

Dependency: UT-02/03. Estimated effort: 0.5–1 day.

Learner final response gets clear finality text and an explicit confirmation. Implement a small Alpine behavior for the existing ordinary POST form; Livewire's `wire:confirm` is not applicable to this Blade form. Cancel performs no POST; accept uses the existing request/service. JavaScript-disabled users still receive clear finality wording and existing server validation, idempotency and acknowledgement.

Track dirty text against the last rendered/saved value. Warn when leaving a changed response/feedback draft, including relevant in-page task switches and browser navigation where supported. Clear guards only after successful save or intentional confirmed submit/discard; validation errors preserve text. Do not introduce localStorage for child content or claim autosave/offline recovery. Browser unload prompts have platform limitations; include the visible Save draft instruction.

Add a learner-specific accessible label to every attendance status select; preserve the correction-reason labels. Check focus order, visible keyboard focus, error association and touch controls on the changed forms. Coordinate assignment feedback indicators with UT-02 rather than duplicate state logic.

Files: learner assignment show/list, attendance-register Blade, SchoolLearning view/partials, narrowly scoped existing JS/Alpine entry or component behavior. Acceptance: draft survives server validation errors; cancel final action leaves response editable; final acknowledgement/retry stays durable; editing final responses/released feedback remains denied; dirty guards do not trigger on unchanged forms or successful saves; keyboard labels identify each learner's control.

## UT-05 — Guided school setup using existing workflows

Dependency: UT-01/02. Estimated effort: 1–2 days.

Add an admin-only setup checklist on school home, calculated from current authorized school records rather than stored checkboxes. Link to the existing relevant workflow and show the next action and prerequisites. Do not make a new wizard that duplicates the setup write services or prevents staff using an already configured school.

Suggested order: school provisioned → academic year/term and class → subjects → active teacher membership → teaching assignment → learner admission/import and dated class placement → managed learner access → optional verified guardian link → first course/lesson/assignment. Staff invitation can happen earlier; explain which steps can proceed independently.

Important source constraint: `TeachingAssignment` currently connects school, class, subject and teacher **without a term_id**. Show term setup as academic context, not a nonexistent foreign-key requirement. Do not add a current-term selector/schema under this plan.

For the demo checklist, completion means at least one internally consistent usable path exists, not that all learners/staff or real-school onboarding are complete. Validate a connected class → teacher assignment → eligible learner placement → published assignment path rather than unrelated “some record exists” checks. Distinguish optional guardian linking and resource-upload readiness. Lack of a validated host scanner must not be hidden by a lesson-publication check.

Add section anchors or allow-listed view context to existing academic/staff/registry pages where useful, so checklist links open the intended form. Success messages explain the resulting state and useful next step. No automatic creation of records/default sections; no assumptions copied from LAVSMS.

Files: SchoolOverview/view, proposed `BuildSchoolSetupChecklist` read service, AcademicStructure/view, SchoolStaffDirectory, LearnerRegistry/Detail and existing setup/access tests. Accept: empty/partial/complete synthetic schools show correct next steps; teachers/bursars do not see admin actions; revoked or disconnected records do not satisfy completion; links reach an authorized form; a fresh demo school can follow the sequence through one published assignment without hidden dependencies.

## UT-06 — Integrated demo and school trial acceptance

Dependency: UT-01 through UT-05. Engineering closeout allowance: 1–2 days; school/host waits are separate and unestimated.

Run one synthetic journey from landing to its final outcome for platform admin, school admin, teacher, guardian, learner and personal-study account. Include multi-school, staff-plus-guardian, inactive/revoked, zero-score and empty-state cases. Review changed pages at 320/390/768/1280px, keyboard operation, confirmation cancel, refresh/back, task switching and private/released visibility. Reuse existing available browser tooling; add no browser dependency. Do not regenerate the earlier reference screenshots as though those apps were executed.

Proposed school feedback exercise: administrator completes setup; teacher records attendance and publishes one text assignment; learners save/submit; teacher reviews/releases; learner reads feedback; guardian finds a child's attendance/notice. Observe task completion, confusing steps, recovery from mistakes and repeat use. No task-time claims before measurement. Document only anonymized findings.

Before sustained real-data trial, agree school contact, grade/class/subject, roster/account delivery, trial length, support and host. Validate queue/scheduler/private storage/scanner as applicable, backups and restore, account revocation and operational recovery against the intended host. Real file uploads remain unavailable until scanner readiness is verified. These are release gates; completing UI work alone does not establish them. Commercial pricing discussions stay after trial feedback.

## UT-07 — Separate formal-report design gate

Status: deferred pending school input. No implementation estimate until the sample/policy is agreed.

Obtain a redacted or synthetic report example and agree subject/competency fields, score scales/weights/rounding, absent/exempt/unassessed/incomplete semantics, teacher comments, reviewer/publisher permissions, guardian visibility and amendment rules. Design formal assessments separately from assignment/practice feedback. Prefer printable HTML using the existing stack; server PDF requires separate justification/dependency approval if needed.

Prepare the SP6 schema/state/policy plan and acceptance cases, then request its own approval before coding. The references show useful outputs, but do not define Nova's school policy. Trial feedback can determine whether reporting, extensions or another workflow is the next priority.

## Verification, delivery and estimates

UT-01–05: roughly **5.5–10 developer-days**; UT-06 engineering closeout adds 1–2, for **6.5–12 developer-days** total before contingency or external waits. Estimates assume one developer, current package versions, no new schema/dependency or redesign of assessment policy. Deliver a reviewable result after each slice; re-estimate if source investigation reveals a broader issue.

Start with failing behavior-focused feature tests for changed routing/access/state, not tests that mirror labels or method internals. Extend existing suites rather than replacing them. Proposed coverage:

- UT-01: FilamentPanelRoutingTest, FilamentPanelAccessTest, Auth/AuthenticationTest, new workspace resolution cases, guardian eligibility/revocation and managed-learner access.
- UT-02: task-summary role/school/sibling cases, SchoolLearningReviewTest, GuardianAttendancePortalTest, learner assignment HTTP coverage.
- UT-03/04: FilamentSchoolLearningTest, SchoolLearningReviewTest, SchoolLearningAssignmentTest, FilamentAttendanceRegisterTest; browser checks for URL history, dirty guards, focus and confirmations.
- UT-05: FilamentSchoolWorkspaceTest, AcademicStructureTest, setup checklist cases and relevant registry/invitation regressions.

Run the narrowest relevant `php artisan test --compact <file>` after changes; use an isolated PostgreSQL database for final integration and preserve the configured SQLite lane. Run Pint for modified PHP, frontend build and Blade view compilation for changed views/JS. Once focused checks pass, run final SQLite and PostgreSQL suites because routing/query/view refactors span multiple roles. Existing PostgreSQL races should pass; do not add new concurrency tests to read-only counters unless new writes justify them. Record actual results; the 30 September suite counts are historical evidence, not verification of this proposed work.

No migrations are anticipated for UT-01–05. If a migration/dependency becomes necessary, update scope and obtain approval before introducing it. Rollback is application-code rollback preserving all records; avoid resetting databases or deleting migration history. Keep existing route names and old Learning URLs usable; missing query context opens the Courses mode. Ensure personal tools and fee workflows are still reachable after navigation changes.

Update architecture/operations when routing behavior changes; update plan/roadmap and implementation log per delivered slice. Update data-model/decisions only when an actual domain/schema decision changes. Preserve pre-existing worktree changes and use synthetic databases for acceptance.

## Defaults included for approval and open inputs

Approval includes the proposed role-routing matrix, assisted school-trial CTA, explicit multi-workspace chooser, no remembered default, “Feedback available” without read tracking, focused modes in the current Learning component, explicit draft save and no schema/package changes. These defaults make implementation bounded and reviewable.

Before public trial-request collection: owner must supply contact/channel and desired collection process; current plan uses instructions only. Before real school trial: school/host/account/trial details above. Before formal reports: sample and policy above. These later inputs do not block UT-01–05.

Owner approved UT-01–05 on 1 October 2026. Application implementation and local integration closeout are complete. UT-06 school/host work and UT-07 report policy remain open.

## Documentation verification

Planning source and framework documentation study completed; linked paths and documentation whitespace checked. No application tests, new browser runs or deployment were performed for this planning-only change.

Framework references: [Livewire 4 URL/history state](https://livewire.laravel.com/docs/4.x/url), [Livewire confirmation](https://livewire.laravel.com/docs/4.x/wire-confirm), [Filament 5 tenancy](https://github.com/filamentphp/filament/blob/5.x/docs/07-users/03-tenancy.md), [Laravel 13 authentication redirects](https://github.com/laravel/docs/blob/13.x/authentication.md). Reference repository findings and their evidence are in the linked comparison report.


## 1 October 2026 — Actual implementation and closeout

Owner approval covered UT-01–05 and the stated defaults. No schema, package, billing or report policy was added.

| Slice | Implemented result |
| --- | --- |
| UT-01 | Shared workspace destination service/controller, authenticated `/workspace`, compatible adult `/dashboard`, explicit `/study`, verified guardian-link scope, authorized chooser, adult panel/menu switching, assisted-trial instructions and contained landing decoration |
| UT-02 | Learning overview action, bounded teacher response summary, learner needed/closed/feedback counts, paginated assignment list with released-feedback badges, bounded recent guardian attendance/notices |
| UT-03 | Courses/Lessons/Assignments/Review modes, scoped course/assignment/response URLs, saved review hydration, roster pagination and return links, current membership/policy checks on subsequent requests |
| UT-04 | Final response confirmation, dirty draft warnings, successful-save/editor-load baseline reset and learner-specific attendance control labels |
| UT-05 | Admin-only calculated setup checklist, connected teacher/class/placement/account/assignment checks and direct academic section links |

Implementation refinement: task navigation uses normal named links with validated query locators, rather than synchronizing multiple editable Livewire URL properties. This preserves refresh and native browser history, reconstructs saved review fields once, and keeps authored content out of URLs. Review roster pagination still uses the existing Livewire pagination behavior. Successful course creation updates its canonical URL. The transactional course/lesson/assignment/submission/review services and publication rules remain unchanged.

Read services select metadata for task counts/lists. The teacher list is capped at five; assignment pages at twenty; guardian recent attendance/notices at twenty each. The added query test verifies the school summary's query count does not grow from one to seven classes and excludes response text/instructions from fetched list attributes. `Feedback available` means released, not unread.

Draft guards run in the browser, without localStorage or autosave. Successful saves and loading a different saved editor reset the baseline; validation errors retain input. Browser unload prompts depend on browser support. Ordinary learner forms retain server validation and finality wording without JavaScript; the explicit confirmation uses Alpine when available.

Synthetic screenshots: [administrator setup](../assets/school-trial-usability/admin-setup.png), [focused teacher review](../assets/school-trial-usability/teacher-review.png), [mixed-workspace chooser](../assets/school-trial-usability/workspace-chooser.png), [released learner feedback](../assets/school-trial-usability/learner-feedback.png). All records are synthetic; no credentials appear.

Browser closeout completed across separate fresh synthetic runs: platform and school administrator entry, guardian direct entry, personal-study entry, managed learner Open workspace, draft save, final confirmation cancel/accept, teacher pending-response deep link, private draft save, refresh and Back restoration, unsaved-feedback navigation cancel, explicit zero-score release and learner badge/feedback, attendance save and keyboard focus, guardian/bursar chooser and bursar Learning denial. Eleven representative captures have no document-wide overflow; landing was checked at 320/390/768/1280px. No JavaScript page errors occurred. A focused lesson-editor check also verified that loading a saved version causes no false warning and that changing lesson text warns before navigation. Focused mobile review measured 1,188px versus the study's earlier 3,071px; this is a viewport/layout observation, not measured human task time.

Final SQLite suite: 310 tests, 303 passed, 7 skipped, 1,464 assertions. Final isolated PostgreSQL 16 suite: 310 passed, 1,512 assertions. Pint, production frontend build, Blade view compilation, route listing and documentation/diff checks passed. Operational limitations are recorded in the implementation log. No school user study, trial hosting, scanner/restore validation or real-data trial is claimed. These remain UT-06 inputs/gates; reporting stays separately gated under UT-07.
