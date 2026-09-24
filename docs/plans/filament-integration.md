# Filament adoption and integration

Date: 22 September 2026.
Status (updated 24 September 2026): FI-01 through FI-07 first slices are locally verified, including native attendance, communications, overview, learner registry/detail, academic structure and fee workflows using shared services and scoped policies. Synthetic Chrome acceptance covers the admin and teacher attendance journeys, guardian visibility, school notice creation/delivery, role restrictions, learner admission/guardian linking, staff invitations and roles, academic setup, keyboard activation, bursar finance denials, and 390px rendering. Browser mutations used isolated Docker PostgreSQL; `nova_scholar` was not targeted. NQ03's local PostgreSQL diagnostic completed 270 mixed requests across three waves with 3 schools × 600 active learners, 30 staff sessions and 60 learner sessions: 252 HTTP 200, 18 expected redirects, zero errors, combined p95 2.33 seconds and maximum 2.52 seconds. Tenant-scoped 50-row pagination brought native registry p95 to 2.12 seconds and aggregate response bytes from 43.6 MB to 10.5 MB. This is local-profile evidence, not hosted or production capacity evidence. Permanent Filament routing is implemented and locally verified. The latest focused role/access suite passes (27 tests, 136 assertions); Chrome confirmed bursar denials at 390px and a four-role navigation/overflow matrix at 1024×768. Broader workflow/device parity, hosted CI, hosted capacity validation and final cutover gates remain. Do not call the integration complete until the remaining school workflows and release gates pass.
Browser-acceptance finding: the school panel needs an explicit tenant home page so login navigation resolves directly to `/school/{tenant}` instead of relying on the fallback tenant redirect before opening the school overview.
Prior owner instruction, 22 September 2026: pause feature implementation, including SP4-02, while integrating Filament. Owner authorized staged completion and roadmap resumption on 23 September 2026; integration and release gates below remain active. Existing functionality and data remain available for browser acceptance.

## Objective and scope

Give platform administrators and school staff coherent operational workspaces while reusing the current models, services, audit history, and authentication. Address the crowded navigation and the platform administrator's current fallback to the personal-study dashboard.

Requirements: NS01–NS08, NS17–NS18, NQ01–NQ02, NQ05. This work changes the presentation and entry points of implemented features; it does not complete missing receipts, alerts, lessons, reports, or payment integrations.

## Baseline and compatibility gate

The recorded installed baseline is Laravel 13.32.0, PHP 8.4, Livewire 4.4.5, Tailwind 4.3.3, PHPUnit 12.5.35, and Filament 5.8.4. The package resolution and installation completed successfully with 32 additional packages and no unrelated package updates or removals. The generated admin panel provider boots and Filament routes are registered.

Before installation, inspect package constraints and record a dependency dry run. Keep the installed Laravel/Livewire major versions; investigate conflicts rather than performing an unrelated upgrade. Use the existing-application installation approach, not scaffolding that overwrites application assets. Start with core Filament packages; no permission, tenancy, impersonation, or paid plugins are required by this plan.

## FI-01 compatibility and authorization inventory

Status: verified on 22 September 2026. The application inventory and route/policy/service map are complete. The Composer dependency dry run resolved successfully without modifying project files. `composer.json` and `composer.lock` hashes were unchanged.

Verified baseline findings:

- Direct dependencies include Laravel Framework 13.32.0, Livewire 4.4.5, Laravel Breeze 2.4.2, Laravel Boost 2.9.0, Tailwind 4.3.3, and Filament 5.8.4. The earlier compatibility spike is historical; Filament is installed and both panels are registered.
- The original dry run resolved Filament 5.8.4 with 32 package installs and no unrelated updates/removals. The installation and panel boot are recorded in the implementation log.
- The application exposes 81 non-vendor routes. Existing school operations use `auth`, `adult.account`, `verified`, `school.context`, and scoped bindings for nested school records.
- Global platform authorization is currently represented by verified users with `UserRole::Admin`; school access is represented by active `SchoolMembership` records and `SchoolRole` assignments.
- School policies commonly require an explicit `School` argument for `viewAny` and `create`, while record policies verify the record's school ownership and membership role. Filament resources cannot rely on default model-only policy calls for these cases.
- School business operations are already isolated in transactional services under `app/Services/Schools`, including provisioning, registry, academics, attendance, communications, invitations/roles, imports, and fee posting. These services should remain the write authority for panel actions.
- The existing platform-admin seed record is suitable for later panel access testing, but no platform web console exists yet. No dependency, migration, route, or application code was changed by FI-01.

FI-01 exit decision: the compatibility gate is passed. The next authorized action is the actual Filament installation, followed by FI-02 panel foundations. Do not register panels before the install and package boot verification succeed.

FI-02 implementation status: implemented and locally verified on 22 September 2026. Filament has separate `platform` and `school` panels. The platform panel is restricted to verified global `UserRole::Admin` accounts. The school panel uses Filament's tenant route with `School` and `slug`, and resolves only active school memberships carrying `school_admin`, `teacher`, or `bursar` roles. Guardians, learners, students without staff membership, unverified users, removed memberships, and inactive schools are denied. Each panel now has a role-specific landing page; the school landing page links to the existing scoped overview, learner, academics, attendance, fees, and communications routes. Existing routes remain canonical for writes.

FI-03 first-slice status: implemented, locally verified and browser-accepted on 23 September 2026. The platform panel includes a school directory and controlled provisioning page. It lists school-level operational metadata only and delegates creation to `ProvisionSchool`, which creates the school, verified first-administrator membership, scoped school-admin role, and audit event transactionally. Headless Chrome confirmed successful creation, duplicate-slug rejection without an extra school, platform-admin denial from the new tenant, and first-administrator entry. A keyboard-only form journey also created a synthetic boarding school. Platform access remains gated by `UserRole::Admin` and email verification; ordinary adults cannot reach the page.

FI-04 first-slice status: implemented and locally verified on 22 September 2026; staff and academic setup actions received synthetic PostgreSQL-backed Chrome acceptance on 24 September. The school panel includes tenant-scoped staff-directory and academic-structure pages. Native custom actions create staff invitations, assign/remove staff roles, revoke invitations, and create academic years, terms, class groups, subjects, and teaching assignments through existing services and explicit tenant/policy checks. No generic Filament CRUD resource bypasses the existing policies, Form Requests, or domain services. Invitation creation, role assignment/removal, invitation revocation, and the complete academic setup path were browser-accepted. Broader role/device parity and remaining task acceptance stay open.

FI-04 academic mutation status: implemented and locally verified on 22 September 2026; complete year/term/class/subject/teaching-assignment setup browser-accepted against isolated Docker PostgreSQL on 24 September. Academic-year, subject, term, class-group, and teaching-assignment creation are custom tenant page actions that reuse existing services and policy boundaries. Subject codes are normalized before validation in both HTTP and Filament flows so service uppercasing cannot turn a duplicate into a database exception. Term, class-group, and teaching-assignment creation keep parent and tenant ownership explicit.

FI-04 staff mutation status: implemented and locally verified on 22 September 2026; invitation create/revoke and role assign/remove browser-accepted against isolated Docker PostgreSQL on 24 September. These actions use the selected tenant and existing services/safeguards; the role-removal browser check confirmed the teacher membership remained intact with the assigned bursar role removed.

FI-05 first-adapter status: implemented and locally verified on 22 September 2026. The registry and detail slices are tenant-scoped; reads authorize with `EnrolmentPolicy`, school-admin admission delegates to `AdmitLearner`, guardian link/revoke actions delegate to `LinkGuardian` and `RevokeGuardianLink`, managed learner access delegates to `CreateManagedLearnerAccess`, learner deactivation delegates to `DeactivateLearner`, promotion delegates to `PromoteLearner`, dual-school-admin transfer delegates to `TransferLearner`, CSV staging delegates to `StageLearnerImport`, and import review/commit delegates to `CommitLearnerImport` with equivalent validation, authorization, replay, and audit behavior. The CSV stage/review/partial-commit/replay browser path was verified on 23 September; other registry browser parity checks remain open.

FI-08 entry-point alignment status: implemented and locally verified on 24 September 2026. The public landing page separates school, platform, learner, and personal-study entry points. Authenticated school staff enter their first eligible Filament school tenant directly, platform administrators enter the platform panel directly, and only independent-study or guardian users render the personal dashboard. The shared Breeze navigation contains only personal tools and the separate guardian portal; school/platform operations are owned by Filament. Attendance, communications, attendance register, notices, learner and finance workflows now have native school-panel destinations; canonical service-backed routes remain for their other callers. Role and device acceptance remains open.

## Proposed workspaces

| Surface | Users and scope | Integration direction |
| --- | --- | --- |
| Public landing and adult authentication | Guests and adult accounts | Keep Blade; clearly distinguish staff/guardian sign-in, learner sign-in, and account registration |
| Platform panel, proposed `/platform` | Verified adult `UserRole::Admin` accounts | School directory and controlled provisioning; platform-level operational metadata only |
| School panel, proposed `/school/{school}` | Active staff membership in an active school | Filament shell with school switcher and policy-controlled modules; start with school admins, extend tested teacher/bursar entry points |
| Guardian portal | Active verified guardian relationships | Keep focused Blade/Livewire experience and child switching |
| Learner portal | Active managed learner identity | Keep separate learner sign-in/dashboard; no adult-panel access |
| Personal study | Existing individual accounts | Retain existing routes/data; keep legacy tools outside school staff navigation |

Use the same users and session guard. Panel access is not permission to access every record. A platform administrator has no automatic school membership or permission to browse learners, guardian relationships, or tuition records. A person with multiple roles gets an explicit workspace chooser; a person with one eligible workspace can enter it directly. Revalidate any remembered school on each request.

New registration grants no administrative role. After email verification, accounts without a school or guardian relationship see onboarding guidance and invitation instructions. Preserve authorized intended invitation URLs. Authentication, password reset, verification, and logout should behave consistently across panels and existing portals.

## Mapping existing work

| Existing capability | Proposed Filament interface | Business behavior to retain |
| --- | --- | --- |
| School provisioning service | Platform directory and custom provision action | `ProvisionSchool`, verified first administrator, membership creation and audit in one transaction |
| School overview, invitations and roles | School dashboard, staff list, invitation/role actions | Existing invitation and role services; expiry, revocation, last-admin protection |
| Learner registry and guardians | School-scoped enrolment resource and relationship actions | Admission, verified guardian linking, revocation; never an unrestricted global learner-profile resource |
| Academics | Year, term, class, subject and assignment resources | Same-school validation, term overlap checks, teacher role and assignment constraints |
| Imports and learner lifecycle | Custom staged-import and lifecycle pages/actions | Preview/commit, promotion, transfer, deactivation and activation services; preserve history |
| Attendance | Link to current register initially; custom panel page later if useful | Assigned-teacher scope, dated roster, unmarked state, session version and correction reason |
| Notices | Announcement list/draft form and explicit send action | `SendSchoolAnnouncement`, active recipient resolution, idempotency, delivery ledger and audit |
| Fee schedules and batches | Schedule resource, custom preview/post page, read-only posted charges | `PostFeeChargeBatch` after readiness fixes; integer money, immutable snapshots and audit |
| Audit events | Read-only school-scoped history | No edit/delete actions, no broad platform access to sensitive metadata |

A custom workflow can live inside Filament; the reason for retaining the attendance page initially is migration cost and verified behavior, not a framework limitation. Start with links for unmigrated modules so each workflow has one canonical entry point.

## Authorization, tenancy and validation

1. Adapt Filament tenancy to existing `School`, active memberships and scoped roles. Do not introduce parallel tenant or role tables. Existing middleware assumes a `{school}` route binding; a panel adapter must resolve the tenant explicitly rather than assuming that middleware runs unchanged.
2. Check tenant membership and role on initial loads and subsequent Livewire requests. Removing membership while a modal remains open must prevent submission. Reject foreign record IDs, relationship options, global search results, widgets, exports and background jobs.
3. Inventory policies before resource registration. Several current `viewAny`/`create` policies require an explicit School argument; default resource calls must be adapted to that contract. Missing policy methods must deny access, not silently grant CRUD.
4. Extract reusable validation and authorization where current checks live only in Form Requests. Controllers and panel actions should share these rules and call the same domain operation. Merely invoking a service is insufficient when that service currently assumes the controller already authorized the request.
5. Disable generic edit/delete/bulk operations for posted charges, correction history, deliveries and audit records. Lifecycle transitions need named, authorized actions with audit records.
6. Use application-selected school IDs and validated related records. Hidden fields and filtered navigation do not establish authorization. No global policy bypass for platform admins.

## Readiness findings to resolve during integration

These are code-inspection findings, not fixes made by this planning task:

- Fee posting can execute without a prior preview and recalculates recipients/amount at posting. Define and enforce preview confirmation, including stale-preview rejection or explicit refreshed confirmation.
- A posted fee batch is returned before checking whether its key belongs to the submitted schedule. Reject mismatched key reuse in both preview and posting, including already-posted batches.
- Existing schedule validity fields need defined enforcement; decide the charge date and dated-class eligibility rules. The fees controller filters terms using `active`, while existing term factories/seeding use `open`; reconcile the domain status consistently.
- Existing fee UI displays minor units alongside a currency label. Display clearly formatted major units, preserving exact integer conversion and validation. For the initial KES workflow, 125000 minor units must display as KES 1,250.00.
- Existing school relations and queries require inspection before automatic relationship fields/global search are exposed. Tenant ownership must follow explicit school IDs consistently.
- The demo seeder uses a fixed fallback password and overwrites records on rerun. Separate demo creation from credential reset, source passwords through configuration, and verify repeat runs preserve browser-test work. Do not put passwords in docs.
- **Resolved during FI-07 on 23 September:** communications initially raised `SQLSTATE[42702]` because `School::classGroups()` is a `hasManyThrough` relation and unqualified `status` and `name` filters collided with academic-year columns on PostgreSQL. Both filters now qualify `class_groups`, with an HTTP regression test. Attendance and communications URLs generated for the selected School use its model route key (numeric ID), not the Filament tenant slug; browser checks now follow those generated links.

Finance readiness fixes are a prerequisite for enabling financial panel mutations. PostgreSQL retry/concurrency checks must substantiate claims beyond the current sequential SQLite feature tests.

## Delivery sequence

Ticket status below reflects the completed slices and remaining gates as of 24 September 2026. Estimates are the original one-developer planning ranges, not remaining-work commitments.

| Ticket | Scope and dependency | Acceptance / exit gate | Estimate |
| --- | --- | --- | --- |
| FI-01 | Baseline and compatibility spike | **Verified.** Inventory, authorization map, dependency resolution and package boot completed. | 0.5–1 |
| FI-02 | Panel foundations and workspace routing; FI-01 | **Locally verified.** Panels, tenant access, redirects and workspace links have focused/full test evidence; authenticated browser journeys remain open. | 1–2 |
| FI-03 | Platform school directory/provisioning; FI-02 | **First slice implemented and locally/browser verified.** Provisioning uses `ProvisionSchool`; success, duplicate slug, membership/role/audit creation, first-administrator access and platform-admin tenant denial were checked in synthetic Chrome. | 1–2 |
| FI-04 | School staff and academic resources; FI-02 | **Native staff and academic setup actions locally verified; invitation create/revoke, role assign/remove, and full academic setup browser-accepted on isolated PostgreSQL.** Broader role/device parity and remaining task acceptance stay open. | 2–3 |
| FI-05 | Registry, guardians and lifecycle/import adapters; FI-04 | **Registry, detail, admission, guardian, managed access, lifecycle and import slices locally verified.** Synthetic browser acceptance now covers CSV stage, validation review, partial commit, and repeat-upload idempotency. Other registry browser parity checks remain open. | 2–4 |
| FI-06 | Finance readiness and panel pilot; FI-02 | **Native finance actions implemented and locally/browser verified.** Schedule creation, preview/confirmed post, manual receipt, and partial allocation use shared school services; tenant isolation, revoked membership, audit writes, and current tests are covered. FI-08 role acceptance keeps finance school-admin-only, including direct fee-page denial for teachers and bursars. The full 24 September local SQLite and PostgreSQL suites pass; the PostgreSQL posting race test confirms the school-row lock protocol. A synthetic 600-learner batch baseline is measured; hosted NQ03 mixed-workload contention and a fresh hosted run remain open before release readiness. | 2–4 |
| FI-07 | Notices and attendance integration; FI-04 | **Native attendance, communications, and overview pages implemented and locally/browser verified on synthetic data.** Admin save, teacher reasoned correction, teacher communications denial and linked guardian attendance/notice visibility passed. Shared services preserve correction/version rules, audience resolution, idempotent delivery and audit. Trusted keyboard activation of the attendance register action passed; wider device/role parity remains open. | 1–2 |
| FI-08 | Browser acceptance and cutover; preceding tickets | **In progress, not passed.** Synthetic browser checks cover platform/school admin, teacher and guardian journeys; provisioning; CSV stage/review/partial commit/replay; native finance; tenant switching/two-tab isolation; and 390px rendering. The 24 September Chrome checks also covered the four-role navigation matrix at 1024×768 and 390px class-targeted notice creation, send, replay, and linked guardian visibility, alongside attendance save/correction, learner admission/guardian linking, promotion/deactivation, staff role changes, academic setup, keyboard activation, and native page rendering; browser mutations used isolated Docker PostgreSQL data. The local NQ03 repeat with 3 schools × 600 learners completed 270 requests with 252 HTTP 200, 18 expected redirects, zero errors, 2.33-second combined p95 and 2.12-second native registry p95 after tenant-scoped 50-row pagination. A 600-charge batch benchmark recorded 151–180 ms posting and 189–219 ms waiting for one competing same-school roster write. These are local-only measurements. The current full worktree was re-run against isolated Docker PostgreSQL after the fee-policy refinement: 224 tests and 977 assertions passed. SQLite passed 222 tests, 962 assertions, and 2 PostgreSQL-only skips. The latest observed hosted run is the 22 September failure on SHA `4b0ecbf`; it predates this worktree. Remaining acceptance: broader browser/role/device parity and applicable workflow gaps, a fresh hosted CI result, representative hosted capacity evidence, and release readiness review. | 1–2 |

FI-08 contention review scope: use synthetic PostgreSQL data for the NQ03 baseline of three schools with 600 active learners each. The local profile is three measured waves of 90 simultaneous HTTP requests after authentication/session warm-up: 30 school-staff sessions (24 native Filament learner-registry GETs and six learner-admission POSTs per wave, balanced across schools) and 60 managed-learner dashboard GETs. Admission requests use the shared `AdmitLearner` service through the existing scoped HTTP endpoint; Livewire admission POST latency is not included. Authentication setup is excluded from request timing. Capture status/error counts and p50/p95/p99/max latency by endpoint and combined. Record charge-post duration and same-school roster-write lock wait separately; no batch-time SLO is approved. This local profile runs on Docker PostgreSQL and a 16-worker PHP CLI server, so it can expose local contention but cannot establish production capacity or monthly uptime. It omits file transfer, queue processing and finance posting during the 90-request waves; these limits must accompany any results. The first 24 September run returned all 600 registry rows per request (about 605 KB), with 4.01 seconds combined p95. After tenant-scoped 50-row pagination, the repeated profile returned 252 HTTP 200 and 18 expected redirects with zero errors; combined p95 was 2.33 seconds, registry p95 2.12 seconds, and maximum 2.52 seconds. The local threshold is met for this server profile; hosted capacity is still unverified. The earlier focused run posted 600 charges per school in 151–180 ms; one competing admission waited 189–219 ms in the three runs. That was sequential school-by-school testing with one competing writer, not the NQ03 mixed workload. NQ03 proposes p95 ordinary requests below 3 seconds, which remains a diagnostic threshold until a representative hosted test and workload review pass. Do not fail or pass operational readiness on an invented batch-time threshold; the mixed-workload run and decision on whether the lock protocol needs tuning remain open before cutover.

### FI-08 tablet-width role navigation sweep

Status: locally/browser verified, 24 September 2026.

Scope: at 1024×768, inspect the platform-admin and school-admin workspace navigation, teacher workflow visibility, and bursar finance/attendance exclusions. Check horizontal overflow on each role's overview. Confirm school-admin fee access and teacher/bursar denials remain tenant-scoped.

Dependencies: permanent Filament routing, FI-03 platform directory, FI-07 overview/workflows, FI-06 fee authorization, isolated Docker PostgreSQL demo fixture.

Acceptance: platform admin reaches its platform workspace without school-only links; school admin sees eligible school workflows; teacher sees Attendance but not Fees or Communications; bursar sees neither Fees nor Attendance; all direct requests prohibited for teacher/bursar return 403; no checked overview has horizontal overflow at 1024×768.

Verification: seeded synthetic accounts only in `nova_scholar_filament_verify`; Chrome 149 at 1024×768 reached the platform overview for platform admin and the selected school overview for school admin, teacher and bursar. All four had no horizontal overflow. School admin saw Staff, Attendance, Fees and Communications; teacher saw Attendance but not Fees/Communications and direct fee/communications returned 403; bursar saw neither Fees nor Attendance and both direct pages returned 403. `nova_scholar` was not modified.

Unresolved: this is representative Chrome viewport evidence, not a complete cross-browser, physical tablet, keyboard, or hosted acceptance matrix.

### FI-07B communications keyboard activation

Status: planned; keyboard activation not yet browser-verified.

Scope: use keyboard focus and Space to activate Save draft on the native school communications page.

Dependencies: FI-07B school-admin page and draft creation service; isolated synthetic demo account and database.

Acceptance: a school admin can complete the required notice fields and activate Save draft with Space; the draft appears in page history without horizontal overflow at 390×844.

Verification: Chrome 149 keyboard events at 390×844; confirm the focused Save draft button responds to Space and renders the draft. Enter was delivered as a keydown but did not activate the button in this automation run; no send or external delivery is part of this check.

Unresolved: this verifies one control and viewport. Confirm Enter activation with a physical/browser-level keyboard run and complete full keyboard and cross-browser accessibility review.

### FI-08 native learner registry pagination

Status: implemented and locally verified, 24 September 2026; hosted capacity validation remains open.

Scope: limit each native school Filament learner-registry page to 50 tenant-scoped enrolments, render Livewire pagination controls, retain the total learner count, and keep new admissions returning the user to page one. Continue using the current authorization gate and `AdmitLearner` service. Do not change the legacy HTTP fallback route in this slice.

Dependencies: FI-02 school tenant authorization; FI-05 registry and admission action; FI-08 NQ03 endpoint profile.

Acceptance: at least 51 learners render as no more than 50 rows on page one; page navigation loads the remaining same-school row and never exposes another school's learner; existing staff/guardian restrictions and admission behavior remain intact; successful admission resets pagination to the first page. Native list response size and latency are measured again with the 3 × 600 mixed workload.

Verification: focused Filament learner-registry tests for page-one/page-two contents, count and tenant scoping; current regression coverage for role authorization and admission through `AdmitLearner`; repeat the same PostgreSQL-backed 270-request diagnostic and record per-endpoint/combined percentiles and response bytes. No production-capacity claim may be based on the local server.

Unresolved decisions: 50 is the initial page size; review task usability with school staff before a production pilot. The local mixed workload excludes fee posting, file transfer and queues; hosted NQ03 acceptance remains a later gate.

Indicative total: 10.5–20 developer-days, excluding an optional complete attendance rewrite and external provider work. Deliver FI-01–FI-03 as the first reviewable milestone; reassess before expanding. This deliberately starts with the missing platform console before financial mutations, refining the earlier finance-first suggestion based on repository inspection.

### FI-07 first pass — panel entry to existing attendance and notices

Status: first navigation pass implemented and locally/browser verified, 23 September 2026.

Scope: expose attendance and communications in the Filament school navigation as tenant-derived links to the existing school-scoped routes. Keep each workflow's current controller, Form Request, policies and services as the only write path; do not create duplicate Filament CRUD or a second notice-send implementation.

Dependencies: FI-02 tenant panel and FI-04 staff/academic pages; existing SP3 attendance sessions/corrections and in-app notice delivery.

Acceptance: eligible teachers/admins can reach attendance from the panel; only school admins see/use notice management; links resolve the selected School model route key; both pages render against PostgreSQL; current stale-version/correction reason and notice recipient/idempotency/audit behavior remain covered by focused feature tests. **First pass met locally and in headless Chrome.**

Verification: the first navigation-only pass was tested locally and in headless Chrome on 23 September. It is superseded by FI-07A and FI-07B, which move the attendance register and communications workflows into native tenant pages while retaining shared service-backed canonical endpoints for their other callers.

Decision update, 24 September 2026: the owner directed full Filament migration and explicitly declined a panel-off rollback switch. Route platform administrators and eligible school staff into their permanent Filament workspaces; preserve separate guardian, managed-learner and personal-study experiences. Fix forward during migration.

### FI-07A native attendance register

Status: implemented, locally verified and browser-checked on synthetic data, 24 September 2026; broader role/device parity remains open.

Scope: provide the teacher/admin register-open, roster, status-entry, save and correction workflow inside the school tenant panel. Use the existing `SaveAttendanceRegister` service as the only write authority, with the selected tenant as the only school source. Preserve assigned-teacher scoping, dated eligible rosters, unmarked versus absent behavior, version checks, required correction reason, correction history and audit records. Keep canonical HTTP routes for shared callers and other interfaces.

Dependencies: FI-02 tenant page/middleware, SP2 dated class placements, SP3 register/correction service and policy. No schema or dependency change expected.

Acceptance: teacher sees only active assignments they own; school admin sees active school assignments; opening a class/date prepares the exact dated tenant roster without writing; the first Save creates the register with submitted statuses, while later saves use version checks; unauthorized assignments, foreign IDs and inactive learners are rejected; changing a previously recorded status requires a reason and preserves correction/audit history. Tenant switching cannot retain a prior school's register state.

Verification: focused attendance, guardian attendance and panel workspace coverage passed (31 tests, 157 assertions). Synthetic Chrome verified admin register opening/save, teacher access to an assigned register, rejection of a correction without a reason, successful reasoned correction and guardian visibility of the changed status. The page had no horizontal overflow at 390px. Native-panel two-school browser task switching, broader mobile/keyboard journeys and conflict/version behavior remain open.

Implementation finding: the shared save service treats any change to an already persisted status—including the initial transition from unmarked—as a correction requiring a reason. The panel therefore prepares an unsaved roster on Open and persists it on the first Save, matching the existing HTTP flow without changing domain behavior. Unresolved: whether the school pilot needs paper/PDF export or attendance exceptions/alerts in this page. Those stay in SP3 and are not prerequisites for register parity.

### FI-07B native school communications

Status: implemented, locally verified and browser-checked on synthetic data, 24 September 2026; broader audience/device parity remains open.

Scope: replace the external canonical communications link and overview card with a school-tenant Filament page for drafting, listing and explicitly sending guardian notices. Route overview cards for already-migrated learner, academic, fee, attendance and communications workflows to their tenant panel pages. Extract draft creation into a shared `CreateSchoolAnnouncement` service used by both the existing Form Request/controller and the panel. Continue to use `AnnouncementPolicy` and `SendSchoolAnnouncement`; keep audience resolution, active guardian/class membership filters, delivery upsert/idempotency and audit behavior in the existing domain service. Show tenant-scoped draft/sent history and delivery counts. Email/SMS, retries and read/acknowledgement are out of scope for this UI migration.

Dependencies: FI-02 school tenant page, SP3-04 announcement model/request/policy and `SendSchoolAnnouncement`. No schema or package change expected.

Acceptance: only school administrators see or access the page and actions; class groups and announcement records are scoped to the selected tenant; class targeting requires an active class belonging to that tenant, while school-wide notices have no class ID; sending checks policy at invocation and calls the shared send service; repeat send is safe and does not duplicate delivery/audit rows; foreign announcement IDs return 404. Guardian portal continues to show only delivered notices to the correct linked guardians.

Verification: `FilamentSchoolCommunicationsTest` passed (4 tests, 26 assertions), including admin draft/send/repeat send, guardian delivery visibility, class targeting, teacher denial, foreign class/announcement IDs and delivery/audit counts. The combined FI-07 regression group passed (31 tests, 162 assertions). Synthetic Chrome created and sent a school-wide notice, confirmed one in-app delivery and read it from the linked guardian portal; teacher direct access returned 403. A second Chrome flow selected `Grade 5 Demo`, created a class-targeted draft, sent it, and confirmed the linked guardian saw it at 390×844. Replaying the send action kept the visible count at one delivery; isolated PostgreSQL inspection confirmed one delivery and one `announcement.sent` audit event. The page had no horizontal overflow at 390px. Broader keyboard and device acceptance remain open. Pint, Blade compilation, Vite build and `git diff --check` passed.

Unresolved: external delivery/read state remain SP3 follow-on work, not blockers to moving the current in-app notice workflow into Filament.

### FI-07C native school overview and staff entry

Status: implemented, locally verified and role/mobile checked in synthetic Chrome, 24 September 2026; broader browser parity remains open.

Scope: migrate the remaining overview summary into the tenant panel: selected school type, current active membership and its scoped roles, existing in-panel workflow links, and an administrator-only staff directory entry. Remove Filament page links back to the legacy school overview from both the overview page and staff directory. Keep invitations and role mutations exclusively in `SchoolStaffDirectory` through `CreateSchoolInvitation` and `ManageSchoolRole`; do not copy those forms into the overview. Retain the canonical HTTP overview for its other authorized callers.

Dependencies: FI-02 tenant resolution/access, FI-04 `SchoolStaffDirectory` and shared role/invitation services, active `SchoolMembership` and role-assignment data. No schema or dependency change expected.

Acceptance: each tenant panel shows the selected school's name/type and only the current user's active membership roles; a school administrator sees the overview's staff-management card, while teachers and bursars do not see that card or its management description. The existing staff directory remains available for staff review, with mutations gated to school admins. Workflow cards point to tenant Filament pages; no page within the enabled panel links back to the canonical overview. Direct cross-tenant membership access stays denied and membership/role changes remain service-authorized. Fee workflow cards must follow their destination policy: only school administrators see the Fees card, while teachers and bursars receive no link to the school-admin-only finance page.

Verification: `FilamentSchoolWorkspaceTest`, `SchoolContextTest`, `SchoolInvitationTest`, `SchoolInvitationHttpTest` and historical rollback coverage passed (35 tests, 152 assertions). Synthetic Chrome confirmed the admin overview card and 390px no-overflow; a teacher saw the scoped role and no management card. Staff directory review remained accessible to the teacher while mutation controls were absent. The latest focused workspace/fee/panel-access rerun passed (27 tests, 136 assertions); admin fees are visible while teachers and bursars do not receive the finance link. Chrome 149 at 390×844 confirmed the bursar sees the role, no Fees or Attendance links, and no horizontal overflow; direct fee and attendance requests both returned 403. The demo seeder's local-only environment guard was the cause of earlier login failures; rerun used `APP_ENV=local` and the isolated verification database. Pint passed.

Unresolved: broader role/task/device parity and keyboard journeys remain. Representative 390px rendering and trusted keyboard activation of attendance register opening have passed; complete remaining browser parity and hosted release gates before declaring the migration complete.

### FI-07D remove residual canonical management links from native workflows

Status: implemented and locally/browser verified, 24 September 2026; full workflow parity remains open.

Scope: complete the already implemented tenant Filament registry, learner-detail, academic-structure and fee pages as the panel destinations by removing their redundant links to canonical Breeze management screens. Keep canonical HTTP routes and views available for their other callers; do not duplicate lifecycle, guardian, import, placement, academic, posting, receipt or allocation actions, which already call their shared services from native pages.

Dependencies: FI-04 learner workflow pages and services, FI-02 academic structure page/services, FI-06 native fees and SP4-02 allocations, FI-08 permanent workspace routing. No schema or dependency change expected.

Acceptance: native registry, learner-detail, academic and fee pages contain no links to canonical learner, academic or fee management routes; existing role-gated Filament actions continue to work and route/tenant authorization tests still pass. Canonical routes remain available to their intended callers.

Verification: `FilamentLearnerRegistryTest`, `FilamentSchoolWorkspaceTest`, `FilamentFeeOperationsTest`, `AcademicStructureTest`, `TeachingAssignmentTest`, `ClassSubjectTest` and `SchoolContextTest` passed (64 tests, 280 assertions). Chrome loaded registry/detail/academics/fees with no canonical `/schools/` links and checked 390px no-overflow; admin admitted a synthetic learner and linked a guardian, and added a subject. Pint, Blade compilation, Vite build and `git diff --check` passed.

Unresolved: a two-school learner transfer was browser-checked on 24 September: platform admin provisioned a destination school, school admin transferred the learner through the native panel, source enrolment became withdrawn, destination enrolment became active, and the destination detail rendered at 390px without horizontal overflow. Broader two-school lifecycle/placement journeys remain. Synthetic Chrome also exercised class creation, promotion and deactivation; CSV staging/review/partial-commit/replay and full academic setup are browser-checked, and lifecycle actions have feature coverage. Canonical routes remain available for their other callers, and permanent Filament routing has no panel-off switch.

### FI-06 first readiness slice — preview-bound fee posting

Status: implemented and locally/browser verified, 23 September 2026. Review found that schedule-row locking did not serialize roster writes against fee posting. FI-06 now includes a school-row locking protocol for changes to charge eligibility; the PostgreSQL race test confirms stale-preview rejection under a concurrent admission. The native finance browser journey now covers schedule, preview/post, receipt, partial allocation, and a 390px viewport. A 24 September full-suite run against an isolated local PostgreSQL database passed (221 tests, 971 assertions), alongside the local SQLite suite (221 tests, 956 assertions, 2 PostgreSQL-only skips). FI-08 is not complete and navigation cutover remains gated.

Scope: harden the existing school fee workflow before exposing write actions in Filament. A post must match a draft batch preview for the same school, schedule and batch key; the eligible enrolment set and schedule amount/currency/scope must still match the preview; reused keys belonging to another schedule must be rejected before posted-batch replay; schedules must be active and valid on the charge date; term selection must use the existing `open` status; displays must convert integer minor units using the currency's minor-unit exponent.

Dependencies: SP4-01 schedule/charge services, current fee routes/Form Requests and local PostgreSQL schema. Fee preview/post and all services that add, withdraw, transfer, import, or change class placement for an enrolment must take the school row lock first, establishing one serialization point for eligible-roster snapshots. Native Filament actions are covered in the following FI-06 slice.

Acceptance: the preview form submits; direct post without preview is rejected with no financial writes; same-schedule posted retry remains idempotent; any key reuse against another schedule is rejected even if the original batch is already posted; changed roster/schedule snapshot requires a refreshed preview; inactive/out-of-date schedules and closed terms cannot be selected/posted; term/class relations remain school-scoped; KES 125000 displays as `KES 1,250.00`; charges remain integer minor-unit snapshots and audit behavior remains atomic.

Verification: sequential fee tests passed, including stale roster/schedule preview rejection. A PostgreSQL-only race test held an admission transaction open, confirmed fee posting waited on the school row, then confirmed posting rejected the stale preview without writing charges. On 24 September, the full local SQLite suite passed (221 tests, 956 assertions, 2 PostgreSQL-only skips) and the full isolated local PostgreSQL suite passed (221 tests, 971 assertions). A synthetic three-school benchmark with 600 active learners per school recorded 151–180 ms posting durations and a 189–219 ms wait for one competing roster write. Pint and `git diff --check` passed. Native fee preview/post passed the FI-06 synthetic browser flow. Same-school roster writes serialize behind the fee-posting transaction. NQ03 mixed-workload p95 and hosted PostgreSQL CI remain unverified for the current worktree.

Unresolved: when to add opening balances/receipts/allocations/statements, define boarding fee dimensions, and decide the school process for reversing posted charges. These remain SP4 work after FI-06 readiness.

### FI-06 native finance actions

Status: implemented and locally/browser verified, 23 September 2026.

Scope: provide native Filament page actions for creating a school fee schedule, previewing and confirming a charge batch, recording a manually confirmed receipt, and allocating receipt funds to posted charges. Keep the school-admin fee policy, selected tenant as the only school source, tenant-filtered form choices, and service-side ownership/balance/idempotency checks. Extract schedule creation into a shared transactional service so the existing HTTP workflow and Filament action use the same write boundary. Posted charges and receipts stay append-only in this slice; no provider integration is added.

Dependencies: FI-06 preview-bound `PostFeeChargeBatch`, SP4-02 `RecordSchoolReceipt` and `AllocateSchoolReceipt`, existing fee policies and canonical fee workflow, Filament 5.8.4 page actions/forms.

Acceptance: authorized school admins can create a schedule, preview a batch, confirm posting only after a preview, record a manually confirmed receipt, and partially allocate a receipt to a tenant-owned posted charge. Every action rechecks `FeeSchedulePolicy` against the current tenant/membership when invoked; a revoked member cannot submit an already-open modal. Submitted schedule/receipt/charge IDs are re-resolved within the active school and forged foreign IDs create no records. Idempotency and stale-preview rules stay identical across Filament and HTTP. Teacher, bursar, guardian, learner and cross-school attempts are denied. Each successful write preserves transactional audit events. **Met locally:** Livewire coverage, full SQLite/PostgreSQL suites, and synthetic headless Chrome flow pass. Finance cutover remains gated on FI-08 and operational readiness.

Verification: 6 Filament finance tests passed (48 assertions); 14 fee tests passed (106 assertions). Full SQLite suite: 206 tests, 204 passed and 2 PostgreSQL-only skips. Full isolated PostgreSQL suite: 206 tests and 875 assertions passed. Pint, view cache, synthetic browser flow and 390px no-overflow check passed. Browser run created a synthetic schedule, previewed and posted one learner charge, recorded a manual receipt, and allocated part of it; the remaining balance appeared in the UI. No live payment provider was used. Large-school lock contention and hosted PostgreSQL CI remain unverified.

Verification plan: action-level Livewire feature coverage for allowed and denied roles, tenant-scoped options/IDs, preview-before-post, stale preview, idempotent retries, partial allocation, audit history, and live membership revocation; full SQLite and isolated PostgreSQL suites; Pint and view compilation; browser flow for schedule → preview → post → receipt → allocation on synthetic isolated data. Keep per-school lock contention and hosted PostgreSQL CI as release gates.

## Verification and browser acceptance

- Retain existing HTTP tests and add meaningful Filament/Livewire action tests for newly exposed entry points; shared service tests cover domain behavior once.
- Test two schools, users with multiple memberships, revoked membership, teachers, bursars, guardians, managed learners, unverified users and platform admins without school access.
- Test foreign IDs in action payloads and relationship searches, not only hidden menu links. Confirm no tenant leaks through counts, audit metadata or global search.
- Finance: cannot bypass preview; changed roster/amount is detected; duplicate key and different-schedule key reuse settle correctly; posted history cannot be edited; concurrency runs against an isolated PostgreSQL test database.
- Registration/login: pending invitation respected; unknown account receives onboarding; guardian opens linked children; learner enters learner portal; no inadvertent school access from a global role.
- Browser journeys: landing → staff login → school selection → academics/registry; platform login → provision school; teacher → register/correction; school admin → notice → guardian read; fee preview → confirm → posted batch. On 23 September, role login and core workspace route smoke checks were completed for platform admin, school admin, teacher and guardian; platform directory/provisioning and read-only school workflows loaded; logout returned to panel login and protected access redirected after logout; and the school overview was checked at 390px. Provisioning browser checks confirmed success, duplicate-slug rejection, membership/role/audit rows, platform-admin tenant denial and first-administrator entry. Keyboard tab order reached the CSV control and submit button; Enter staged the CSV and submitted the provisioning form. Native fee actions also passed browser acceptance. Two-tab browser acceptance kept two synthetic school contexts separate; switching through the tenant menu selected each tenant, and opening one tenant’s import ID under the other returned 404. Broader browser parity and release readiness remain open; Filament remains the permanent staff workspace.
- Check mobile navigation, visible labels, keyboard focus, loading/errors, session expiry, two school tabs, back navigation and logout on a shared browser. Compare ordinary paginated lists using synthetic school-size data.
- Run focused tests, Pint for PHP changes, Vite build, Blade compilation where appropriate, then broader regression checks at cutover. Vite alone does not compile or validate Blade templates.

## Full migration and panel routing

Owner direction, 24 September 2026: complete the Filament migration without maintaining a panel-off rollback switch. The platform panel is the permanent workspace for platform administrators; the tenant panel is the permanent staff workspace for school workflows that have reached parity. Keep guardian and managed-learner experiences separately scoped while their portal requirements remain distinct. Preserve domain services, policies, identifiers, and historical records; do not create parallel writes.

### FI-08 permanent Filament entry routing

Status: implemented and locally verified on 24 September 2026; hosted CI/capacity and broader parity remain open.

Scope: remove `FILAMENT_PANELS_ENABLED`, its configuration and middleware, and panel-off branches from landing/dashboard navigation. Always route platform administrators to `/platform` and eligible school staff to their authorized Filament school tenant. Keep personal-study, guardian, and managed-learner routes available to their intended audiences. Remove the platform-admin Breeze dashboard fallback. Do not remove canonical service-backed HTTP endpoints still used by separate portals or integrations.

Dependencies: existing panel authorization/tenant resolution; FI-03 platform access; FI-04/FI-05/FI-06/FI-07 workflow parity and shared services.

Acceptance: panel login and Livewire routes are always registered; dashboard dispatch consistently sends platform admins and eligible staff to their Filament workspace; the landing page links directly to those panel logins; personal-study and guardian flows remain intact; no configuration switch or old staff dashboard fallback remains; all write paths retain current policy checks and shared-service calls.

Verification: focused routing/access checks passed (10 tests, 21 assertions). At that routing-slice verification, PostgreSQL passed (221 tests, 975 assertions) and SQLite passed (219 tests, 960 assertions; 2 PostgreSQL-only skips); the later current-worktree suite results are recorded in FI-08 above. Pint and `git diff --check` passed. Feature tests exercised dashboard redirects, panel logins, landing links and the personal dashboard. NQ03 hosted capacity and broader workflow/device acceptance remain separate FI-08 release gates.

Unresolved: browser/device parity, hosted CI for the current tree, hosted capacity evidence, and remaining school workflow gaps. Fix forward during migration; the user has declined a panel-off rollback mechanism.

## Decisions and open questions

- Recommended: two panels (platform and school), separate guardian/learner portals, shared services and policies, core packages first.
- Preserve current fee authority (school admin) initially. Bursar access is a separately documented role-policy change, not a side effect of installing Filament.
- Decide after FI-01 whether the attendance register benefits from a custom Filament page or stays linked longer.
- Confirm platform directory metadata and permitted provisioning controls; broad support access/impersonation is outside the first milestone.
- Panel routes/branding are established as `/platform` and tenant-aware `/school/{school}`. The current navigation uses custom pages for implemented panel workflows and policy-filtered links to canonical attendance/communications routes; future resource scope follows module parity evidence. No paid plugin has been installed.
- The owner resumed the paused school roadmap on 23 September in staged slices. SP4-02's manual receipt/allocation slice and its local synthetic browser flow are implemented and verified. FI-03 provisioning now passes browser acceptance; FI-06 PostgreSQL roster/post concurrency and finance browser acceptance are verified; per-school large-batch contention review remains open. Keep navigation cutover and live finance use gated on FI-08 and operational readiness. Hosted PostgreSQL CI, broader keyboard journeys, and SP3 alert/external delivery/read-state work remain open; staff entry uses Filament as the permanent route.

## Source references

Official documentation reviewed on 22 September 2026: [Filament 5 installation](https://filamentphp.com/docs/5.x/introduction/installation), [Filament tenancy](https://filamentphp.com/docs/5.x/users/tenancy). These support the proposed panel/tenant approach; exact package compatibility and security behavior still require the implementation spike and tests.
