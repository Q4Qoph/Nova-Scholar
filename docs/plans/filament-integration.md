# Filament adoption and integration

Date: 22 September 2026.
Status: FI-01, FI-02, FI-03, FI-04, and FI-05 first slices verified; FI-06 finance read-only adapter and FI-08 workspace entry-point alignment verified locally; browser cutover and remaining mutation/resource migration remain deferred.
Browser-acceptance finding: the school panel needs an explicit tenant home page so login navigation resolves directly to `/school/{tenant}` instead of relying on the fallback tenant redirect before opening the school overview.
Owner instruction: pause current feature implementation and plan Filament integration. SP4-02 and other new feature slices are paused pending this integration work. Existing functionality and data remain available for browser testing.

## Objective and scope

Give platform administrators and school staff coherent operational workspaces while reusing the current models, services, audit history, and authentication. Address the crowded navigation and the platform administrator's current fallback to the personal-study dashboard.

Requirements: NS01–NS08, NS17–NS18, NQ01–NQ02, NQ05. This work changes the presentation and entry points of implemented features; it does not complete missing receipts, alerts, lessons, reports, or payment integrations.

## Baseline and compatibility gate

The recorded installed baseline is Laravel 13.32.0, PHP 8.4, Livewire 4.4.5, Tailwind 4.3.3, PHPUnit 12.5.35, and Filament 5.8.4. The package resolution and installation completed successfully with 32 additional packages and no unrelated package updates or removals. The generated admin panel provider boots and Filament routes are registered.

Before installation, inspect package constraints and record a dependency dry run. Keep the installed Laravel/Livewire major versions; investigate conflicts rather than performing an unrelated upgrade. Use the existing-application installation approach, not scaffolding that overwrites application assets. Start with core Filament packages; no permission, tenancy, impersonation, or paid plugins are required by this plan.

## FI-01 compatibility and authorization inventory

Status: verified on 22 September 2026. The application inventory and route/policy/service map are complete. The Composer dependency dry run resolved successfully without modifying project files. `composer.json` and `composer.lock` hashes were unchanged.

Verified baseline findings:

- Direct dependencies remain Laravel Framework 13.32.0, Livewire 4.4.5, Laravel Breeze 2.4.2, Laravel Boost 2.9.0, and Tailwind 4.3.3; Filament is not installed.
- `filament/filament:^5.0` resolves to Filament 5.8.4 and 32 additional packages with zero package updates or removals in the dry run. The actual install remains a separate approved dependency change.
- The application exposes 81 non-vendor routes. Existing school operations use `auth`, `adult.account`, `verified`, `school.context`, and scoped bindings for nested school records.
- Global platform authorization is currently represented by verified users with `UserRole::Admin`; school access is represented by active `SchoolMembership` records and `SchoolRole` assignments.
- School policies commonly require an explicit `School` argument for `viewAny` and `create`, while record policies verify the record's school ownership and membership role. Filament resources cannot rely on default model-only policy calls for these cases.
- School business operations are already isolated in transactional services under `app/Services/Schools`, including provisioning, registry, academics, attendance, communications, invitations/roles, imports, and fee posting. These services should remain the write authority for panel actions.
- The existing platform-admin seed record is suitable for later panel access testing, but no platform web console exists yet. No dependency, migration, route, or application code was changed by FI-01.

FI-01 exit decision: the compatibility gate is passed. The next authorized action is the actual Filament installation, followed by FI-02 panel foundations. Do not register panels before the install and package boot verification succeed.

FI-02 implementation status: implemented and locally verified on 22 September 2026. Filament has separate `platform` and `school` panels. The platform panel is restricted to verified global `UserRole::Admin` accounts. The school panel uses Filament's tenant route with `School` and `slug`, and resolves only active school memberships carrying `school_admin`, `teacher`, or `bursar` roles. Guardians, learners, students without staff membership, unverified users, removed memberships, and inactive schools are denied. Each panel now has a role-specific landing page; the school landing page links to the existing scoped overview, learner, academics, attendance, fees, and communications routes. Existing routes remain canonical for writes.

FI-03 first-slice status: implemented and locally verified on 22 September 2026. The platform panel now includes a school directory and controlled provisioning page. It lists school-level operational metadata only and delegates creation to `ProvisionSchool`, which creates the school, verified first-administrator membership, scoped school-admin role, and audit event transactionally. Platform access remains gated by `UserRole::Admin` and email verification; ordinary adults cannot reach the page. Browser acceptance and further directory controls remain pending.

FI-04 first-adapter status: implemented and locally verified on 22 September 2026. The school panel now includes tenant-scoped staff-directory and academic-structure pages. These pages are read-only adapters that query only the selected school and link to the existing invitation, role-management, and academic mutation routes. No generic Filament CRUD resource bypasses the existing policies, Form Requests, or domain services. Full resource/form migration and browser acceptance remain pending.

FI-04 academic mutation status: first academic mutation pass verified on 22 September 2026. Academic-year, subject, term, class-group, and teaching-assignment creation are implemented as custom tenant page actions, reusing the existing services and policy boundaries. Subject codes are normalized before validation in both HTTP and Filament flows so service uppercasing cannot turn a duplicate into a database exception. Term, class-group, and teaching-assignment creation keep parent and tenant ownership explicit.

FI-04 staff mutation status: first staff mutation pass verified on 22 September 2026. Invitation creation, role assignment/removal, and invitation revocation use the selected tenant and existing services/safeguards. Full resource/form migration and browser cutover remain open.

FI-05 first-adapter status: implemented and locally verified on 22 September 2026. The registry and detail slices are tenant-scoped; reads authorize with `EnrolmentPolicy`, school-admin admission delegates to `AdmitLearner`, guardian link/revoke actions delegate to `LinkGuardian` and `RevokeGuardianLink`, managed learner access delegates to `CreateManagedLearnerAccess`, learner deactivation delegates to `DeactivateLearner`, promotion delegates to `PromoteLearner`, dual-school-admin transfer delegates to `TransferLearner`, CSV staging delegates to `StageLearnerImport`, and import review/commit delegates to `CommitLearnerImport` with equivalent validation, authorization, replay, and audit behavior. Browser acceptance remains open.

FI-08 entry-point alignment status: implemented and locally verified on 22 September 2026. The public landing page separates school, platform, learner, and personal-study entry points. Authenticated school staff now enter their first eligible Filament school tenant directly, platform administrators enter the platform panel directly, and only independent-study or guardian users render the personal dashboard. The shared Breeze navigation contains only personal tools and the separate guardian portal; school/platform operations are owned by Filament. Attendance/communications remain canonical routes inside the school workspace until their panel migrations complete. Manual authenticated browser acceptance remains open.

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

Finance readiness fixes are a prerequisite for enabling financial panel mutations. PostgreSQL retry/concurrency checks must substantiate claims beyond the current sequential SQLite feature tests.

## Delivery sequence

All tickets below are proposed and unstarted. Estimates are developer-days for one developer, subject to the compatibility spike and discovered defects.

| Ticket | Scope and dependency | Acceptance / exit gate | Estimate |
| --- | --- | --- | --- |
| FI-01 | Baseline and compatibility spike | Inventory, policy/service map, route map, and dependency dry run verified; Filament 5.8.4 resolves with no unrelated upgrades | 0.5–1 |
| FI-02 | Panel foundations and workspace routing; FI-01 | Verified: separate panel routes, tenant switching, role-aware landing pages, revoked-access checks, and existing workflow links pass focused/full tests; browser acceptance remains | 1–2 |
| FI-03 | Platform school directory/provisioning; FI-02 | First slice verified: platform admin can list metadata and provision through existing service; ordinary adults denied; no implicit school-data access | 1–2 |
| FI-04 | School staff and academic resources; FI-02 | First academic mutation pass and read-only adapters verified with tenant/policy/service coverage; staff mutations and full resource migration remain open | 2–3 |
| FI-05 | Registry, guardians and lifecycle/import adapters; FI-04 | Tenant-scoped registry and read-only learner-detail adapters preserve `EnrolmentPolicy` and cross-school isolation; admission/import, guardian, and lifecycle migrations remain open | 2–4 |
| FI-06 | Finance readiness and panel pilot; FI-02 | First tenant-scoped read-only fee workspace verified; readiness findings and preview-confirm-post migration remain open | 2–4 |
| FI-07 | Notices and attendance integration; FI-04 | Notice actions preserve recipients/audit; attendance reachable with current scope/corrections; custom register migration only if parity is demonstrated | 1–2 |
| FI-08 | Browser acceptance and cutover; preceding tickets | Role journeys, mobile/keyboard checks, shared-device logout, query checks, docs and rollback rehearsal complete | 1–2 |

Indicative total: 10.5–20 developer-days, excluding an optional complete attendance rewrite and external provider work. Deliver FI-01–FI-03 as the first reviewable milestone; reassess before expanding. This deliberately starts with the missing platform console before financial mutations, refining the earlier finance-first suggestion based on repository inspection.

## Verification and browser acceptance

- Retain existing HTTP tests and add meaningful Filament/Livewire action tests for newly exposed entry points; shared service tests cover domain behavior once.
- Test two schools, users with multiple memberships, revoked membership, teachers, bursars, guardians, managed learners, unverified users and platform admins without school access.
- Test foreign IDs in action payloads and relationship searches, not only hidden menu links. Confirm no tenant leaks through counts, audit metadata or global search.
- Finance: cannot bypass preview; changed roster/amount is detected; duplicate key and different-schedule key reuse settle correctly; posted history cannot be edited; concurrency runs against an isolated PostgreSQL test database.
- Registration/login: pending invitation respected; unknown account receives onboarding; guardian opens linked children; learner enters learner portal; no inadvertent school access from a global role.
- Browser journeys: landing → staff login → school selection → academics/registry; platform login → provision school; teacher → register/correction; school admin → notice → guardian read; fee preview → confirm → posted batch.
- Check mobile navigation, visible labels, keyboard focus, loading/errors, session expiry, two school tabs, back navigation and logout on a shared browser. Compare ordinary paginated lists using synthetic school-size data.
- Run focused tests, Pint for PHP changes, Vite build, Blade compilation where appropriate, then broader regression checks at cutover. Vite alone does not compile or validate Blade templates.

## Rollout and rollback

Ship panel registration and navigation behind an explicit configuration switch. Keep current authorized pages reachable during migration. Move one module's navigation only after parity tests and browser acceptance; keep a documented mapping from old routes to new pages. Avoid maintaining two independent implementations of a write operation.

Filament integration itself should not require moving existing school records or replacing identifiers. Additive schema changes needed for preview integrity get their own migration and tests. Rollback disables panel entry/navigation and restores the previous UI while retaining shared correctness fixes and all newly written business records. Do not roll back the database to undo a UI release.

## Decisions and open questions

- Recommended: two panels (platform and school), separate guardian/learner portals, shared services and policies, core packages first.
- Preserve current fee authority (school admin) initially. Bursar access is a separately documented role-policy change, not a side effect of installing Filament.
- Decide after FI-01 whether the attendance register benefits from a custom Filament page or stays linked longer.
- Confirm platform directory metadata and permitted provisioning controls; broad support access/impersonation is outside the first milestone.
- Exact panel routes/branding are now established as `/platform` and tenant-aware `/school/{school}`; resource scope and navigation remain to be settled during FI-02. No paid plugin has been installed.
- New feature work resumes after the agreed integration milestone; SP4-02 remains the next planned finance slice. SP3 alert, delivery and read-state work remains open.

## Source references

Official documentation reviewed on 22 September 2026: [Filament 5 installation](https://filamentphp.com/docs/5.x/introduction/installation), [Filament tenancy](https://filamentphp.com/docs/5.x/users/tenancy). These support the proposed panel/tenant approach; exact package compatibility and security behavior still require the implementation spike and tests.
