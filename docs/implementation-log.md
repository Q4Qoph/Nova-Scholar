# Implementation log

## 2026-09-22 — Remove duplicate Breeze school shell for staff

Status: implemented and locally verified; authenticated visual browser acceptance remains open.

Changes: changed the authenticated dashboard route so global platform administrators enter the Filament platform panel and eligible school staff enter their first active Filament school tenant. The Breeze dashboard now remains for independent-study and guardian users only. Removed school/platform operational links from the Breeze desktop and mobile navigation so staff no longer see a second school layout. Multi-school staff use Filament's tenant switcher; canonical attendance and communications remain inside the school workspace until migrated.

Verification: focused navigation/auth/panel tests passed (31 tests, 72 assertions); full regression verification remains required after this change. `vendor/bin/pint --dirty --format agent` and `php artisan view:cache --no-interaction` passed.

Limitations: no authenticated browser automation or screenshot review is available in this environment. The visual distinction between the personal Breeze workspace and Filament operational panels still needs manual browser acceptance.

Next steps: run the platform-admin, school-admin, teacher, guardian, and independent-study login journeys in a browser; confirm the Filament tenant switcher handles multiple schools; then continue migrating the remaining canonical school modules.

## 2026-09-22 — Workspace entry-point and navigation alignment

Status: implemented and locally verified; authenticated visual browser acceptance remains open.

Changes: updated the public landing page with explicit school, platform, learner, and personal-study entry points. Updated the authenticated dashboard and shared Breeze navigation to use Filament platform/school routes where the corresponding slices exist. Added role-aware school membership filtering so only active school administrators, teachers, and bursars see staff workspace links; guardian access remains a separate portal. Attendance and communications continue to use their canonical scoped routes until their Filament migrations are complete. Added focused landing/dashboard navigation coverage.

Verification: focused navigation/auth/panel tests passed (30 tests, 75 assertions); full PHPUnit suite passed (186 tests, 720 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: no authenticated browser automation or screenshot review is available in this environment. Responsive navigation, keyboard behavior, seeded-account journeys, logout, and visual parity still require manual browser acceptance.

Next steps: manually test the four landing entry points and platform/school/guardian journeys, then migrate or deliberately retain the remaining canonical school modules one slice at a time.

## 2026-09-22 — Filament browser-acceptance smoke check

Status: transport-level smoke check verified; authenticated visual browser acceptance remains open.

Checks: with the local Docker services running, `http://localhost:8000/platform/login` and `http://localhost:8000/school/login` returned HTTP 200. The protected school tenant root, learner registry, and fee operations URLs returned HTTP 302 when requested without a session, confirming authentication protection. The sandbox could not directly reach the host listener, so the checks were run against the local service with approved host access.

Limitations: no authenticated browser automation or screenshot review is available in this environment. Navigation, seeded-account login, responsive layout, keyboard behavior, logout, and the learner/import/finance workflows still require manual browser acceptance.

Next steps: manually test the seeded platform-admin and school-admin journeys, then record any UI defects before panel cutover.

## 2026-09-22 — FI-05 learner registry Filament adapter

Status: implemented and locally verified for the first read-only registry slice; learner admission/import/detail, guardian actions, lifecycle controls, and browser acceptance remain open.

Changes: added the tenant-scoped Filament learner registry page and school-panel navigation entry. The page authorizes with `EnrolmentPolicy`, loads enrolments only from the selected school, displays admission/status details, and links each learner to the canonical management route. Added negative isolation coverage for cross-school learners and guardian denial.

Verification: focused learner-registry/panel tests passed (8 tests, 17 assertions); full PHPUnit suite passed (164 tests, 640 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: this is a read-only adapter, so writes still use the canonical learner registry workflow. No browser/device review has been performed for the new page.

Next steps: perform manual browser acceptance for the learner registry, then migrate learner detail/admission or the next highest-value school workflow with equivalent tenant and service-boundary coverage.

## 2026-09-22 — FI-05 learner detail Filament adapter

Status: implemented and locally verified for the read-only learner detail slice; learner admission/import, guardian actions, managed access, lifecycle controls, and browser acceptance remain open.

Changes: added the tenant-scoped Filament learner detail page at `/school/{tenant}/learner/{record}`. It resolves the enrolment through the selected school before applying `EnrolmentPolicy`, then displays profile, enrolment, class-history, and active guardian summaries. The page links back to the Filament registry and forward to the canonical management route. Added focused coverage for staff access and cross-school denial.

Verification: focused learner-registry tests passed (4 tests, 12 assertions); full PHPUnit suite passed (166 tests, 645 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan route:list --name=filament.school` reports the tenant detail route; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: the page is read-only and does not replace the existing Breeze/Blade write workflow. No browser/device review has been performed for this page.

Next steps: perform manual browser acceptance for the registry and detail pages, then migrate learner admission or staged imports with the same tenant and service-boundary checks.

## 2026-09-22 — FI-05 learner admission Filament action

Status: implemented and locally verified; staged imports, guardian actions, managed access, lifecycle controls, and browser acceptance remain open.

Changes: added a school-admin-only admission form to the Filament learner registry. The action validates the same learner fields and school-scoped admission-number uniqueness as `StoreLearnerRequest`, resolves the active tenant explicitly, delegates to `AdmitLearner`, and retains the transactional profile/enrolment creation and `learner.admitted` audit event. Teachers retain registry read access but cannot submit the action.

Verification: focused learner-registry tests passed (6 tests, 18 assertions); full PHPUnit suite passed (168 tests, 651 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: staged CSV imports and other learner writes remain on the canonical Breeze/Blade workflow. Manual browser acceptance has not been performed for the admission form.

Next steps: test the admission form manually, then migrate staged learner imports or guardian management with equivalent tenant and service-boundary checks.

## 2026-09-22 — FI-05 guardian relationship Filament actions

Status: implemented and locally verified; managed learner access, lifecycle controls, staged imports, and browser acceptance remain open.

Changes: added school-admin-only guardian linking and revocation to the tenant-scoped Filament learner detail page. The actions validate verified guardian accounts and relationship data, resolve links through the selected learner and school, delegate to `LinkGuardian` and `RevokeGuardianLink`, and preserve relationship audit events. Teachers retain read-only learner detail access and cannot mutate guardian links.

Verification: focused learner-registry/detail tests passed (8 tests, 26 assertions); full PHPUnit suite passed (170 tests, 659 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: managed learner access, lifecycle actions, staged imports, and browser acceptance remain on the roadmap. No browser review has been performed for the new guardian controls.

Next steps: test the learner detail and guardian controls manually, then migrate managed learner access or staged imports with equivalent tenant and service-boundary checks.

## 2026-09-22 — FI-05 managed learner access Filament action

Status: implemented and locally verified; learner lifecycle actions, staged imports, and browser acceptance remain open.

Changes: added school-admin-only managed learner access issuance to the tenant-scoped Filament learner detail page. The action delegates to `CreateManagedLearnerAccess`, displays the generated learner login ID, and exposes the one-time activation URL in the current panel response. Existing token hashing, expiry, activation, and audit behavior remain in the service boundary; teachers cannot issue access.

Verification: focused learner-registry/detail tests passed (10 tests, 34 assertions); full PHPUnit suite passed (172 tests, 667 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: lifecycle actions and staged imports remain on the canonical Breeze/Blade workflow. Manual browser acceptance has not been performed for activation-link issuance.

Next steps: test activation-link issuance manually, then migrate staged imports or learner lifecycle controls with equivalent tenant and service-boundary checks.

## 2026-09-22 — FI-05 learner deactivation Filament action

Status: implemented and locally verified; promotion, transfer, staged imports, and browser acceptance remain open.

Changes: added school-admin-only learner deactivation to the tenant-scoped Filament learner detail page. The action validates the deactivation date, delegates to `DeactivateLearner`, refreshes the record, and preserves withdrawn history, active-placement closure, inactive profile state, managed-access blocking, and the `learner.deactivated` audit event. Teachers cannot submit the action.

Verification: focused learner-registry/detail tests passed (12 tests, 41 assertions); full PHPUnit suite passed (174 tests, 674 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: promotion, transfer, staged imports, and browser acceptance remain on the roadmap. No browser review has been performed for deactivation.

Next steps: test deactivation manually, then migrate promotion/transfer or staged imports with equivalent tenant and service-boundary checks.

## 2026-09-22 — FI-05 learner promotion Filament action

Status: implemented and locally verified; learner transfer, staged imports, and browser acceptance remain open.

Changes: added school-admin-only learner promotion to the tenant-scoped Filament learner detail page. Active class groups are loaded through the selected school, submitted class IDs are revalidated against that school, and the action delegates to `PromoteLearner`, preserving dated placement closure and the `learner.promoted` audit event. Qualified joined class-group columns after verification exposed an ambiguous status/name query risk. Teachers cannot submit the action.

Verification: focused learner-registry/detail tests passed (14 tests, 48 assertions); full PHPUnit suite passed (176 tests, 681 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: learner transfer, staged imports, and browser acceptance remain on the roadmap. No browser review has been performed for promotion.

Next steps: test promotion manually, then migrate transfer or staged imports with equivalent tenant and service-boundary checks.

## 2026-09-22 — FI-05 learner transfer Filament action

Status: implemented and locally verified; staged imports and browser acceptance remain open.

Changes: added dual-school-admin learner transfer to the tenant-scoped Filament learner detail page. Destination choices are limited to active schools where the actor has an active school-admin membership; destination admission numbers are validated within the destination school; and the action delegates to `TransferLearner`, preserving source withdrawal, destination enrolment, placement closure, and paired transfer audit events. Teachers cannot submit the action.

Verification: focused learner-registry/detail tests passed (16 tests, 55 assertions); full PHPUnit suite passed (178 tests, 688 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: staged imports and browser acceptance remain on the roadmap. No browser review has been performed for transfer.

Next steps: test transfer manually, then migrate staged learner imports with equivalent tenant and service-boundary checks.

## 2026-09-22 — FI-05 learner CSV staging Filament action

Status: implemented and locally verified for the staging slice; import review/commit and browser acceptance remain open.

Changes: added school-admin-only CSV upload/staging to the tenant-scoped Filament learner registry. The action validates the upload, delegates to `StageLearnerImport`, and preserves fixed headers, row-level validation, checksum replay protection, staged batch/row persistence, and the `learner_import.staged` audit event. Teachers cannot stage imports. Successful staging links to the canonical review page.

Verification: focused learner-registry/detail tests passed (18 tests, 62 assertions); full PHPUnit suite passed (180 tests, 695 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: import review and commit remain on the canonical Breeze/Blade workflow. No browser review has been performed for CSV staging.

Next steps: test CSV staging and review manually, then migrate the review/commit page or begin browser acceptance for the completed learner workspace.

## 2026-09-22 — FI-05 learner import review and commit

Status: implemented and locally verified; browser acceptance remains open.

Changes: added the tenant-scoped Filament import review page at `/school/{tenant}/learner-import/{record}`. It resolves batches through the selected school, applies `ImportBatchPolicy`, displays row-level validation and batch counts, and delegates commit to `CommitLearnerImport`, preserving idempotent row handling and audit events. The registry now links staged uploads to this page; teachers are denied.

Verification: focused learner-registry/import tests passed (20 tests, 69 assertions); full PHPUnit suite passed (182 tests, 702 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan route:list --name=filament.school` reports the learner import route; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: the canonical Blade import page remains available as a fallback. No browser review has been performed for the Filament import workflow.

Next steps: perform the learner-workspace browser acceptance checklist, then move to the next Filament module or cut over the completed learner workflows.

## 2026-09-22 — FI-06 finance read-only Filament adapter

Status: implemented and locally verified for the first read-only finance slice; finance mutations/readiness and browser acceptance remain open.

Changes: added the school-admin-only tenant-scoped Filament fee operations page at `/school/{tenant}/fee-operations`. It displays configured fee schedules, charge-batch history, and recent posted charges, querying only the selected school and applying `FeeSchedulePolicy`. It does not duplicate fee creation or preview/post workflows.

Verification: focused finance tests passed (2 tests, 6 assertions); full PHPUnit suite passed (184 tests, 708 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan route:list --name=filament.school` reports the fee operations route; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: fee schedule creation and preview-confirm-post remain on the canonical finance page. No browser review has been performed for the finance adapter.

Next steps: perform browser acceptance for learner/import/finance panels, then migrate finance mutations only after the readiness gate is satisfied.

## 2026-09-22 — School panel tenant-login redirect fix

Status: implemented and locally verified; manual browser confirmation remains outstanding.

Changes: added an explicit tenant home page at `/school/{tenant}` by registering `SchoolDashboard`, which reuses the school overview view. This removes the login dependency on Filament's fallback tenant redirect and prevents the transient 404 observed before the browser reached `/school/{tenant}/school-overview`.

Verification: `php artisan route:list --name=filament.school` now reports `school/{tenant:slug}` as `filament.school.pages.home`; focused panel/workspace tests passed (20 tests, 66 assertions); full PHPUnit suite passed (162 tests, 633 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: the original login click was not automated in this environment. Please retry with a hard refresh and report the final URL or any remaining redirect/error behavior.

Next steps: confirm school-admin and teacher login manually, then continue the browser acceptance checklist.

## 2026-09-22 — Filament custom-page theme loading

Status: implemented and locally verified; manual visual browser confirmation remains in progress.

Changes: replaced the temporary application stylesheet registration with dedicated platform and school Filament themes. Each theme imports Filament's base CSS and sources its panel's PHP/Blade files, making the existing Tailwind utility classes available without replacing Filament navigation, icon, and component styles.

Verification: `vendor/bin/pint --dirty --format agent` passed; `npm run build` passed; `php artisan view:cache --no-interaction` passed; focused Filament tests passed (19 tests, 63 assertions); the school login response references the dedicated compiled theme asset; `git diff --check` passed.

Limitations: the screenshot was not re-captured by this environment. Hard refresh/browser cache clearing and responsive visual checks remain for manual acceptance.

Next steps: hard-refresh the staff directory, verify cards/forms/sidebar visually, then continue the role and guardian browser journeys.

## 2026-09-22 — Filament browser-acceptance preparation

Status: environment prepared; manual browser acceptance is in progress and not yet verified.

Changes: started the Docker Compose services, confirmed PostgreSQL readiness, seeded the synthetic demo school and role accounts with the local seeder, and confirmed the platform login route responds at the local app URL. No credentials or private data were added to documentation.

Verification: `docker compose up -d` completed; PostgreSQL reported accepting connections; `php artisan db:seed --force --no-interaction` completed; `curl --head http://localhost:8000/platform/login` returned HTTP 200. Manual browser/device, role-journey, and visual checks remain outstanding.

Limitations: browser automation is not available in this shell, so the remaining acceptance must be performed interactively. The Tika service reports unhealthy in Compose but is outside the Filament panel acceptance path.

Next steps: use the seeded accounts to test platform admin, school admin, teacher, and guardian journeys, then record any defects before cutover.

## 2026-09-22 — FI-04 invitation revocation Filament mutation

Status: implemented and locally verified; the first staff mutation pass is complete, while broader resource migration and browser acceptance remain open.

Changes: added school-admin-only invitation revocation to the tenant-scoped Filament staff directory. Invitations are resolved through the selected school and revocation delegates to `CreateSchoolInvitation`, preserving accepted/revoked safeguards and audit behavior. Added Livewire coverage for revocation state and audit persistence.

Verification: focused FI-04 workspace tests passed (11 tests, 49 assertions); full PHPUnit suite passed (161 tests, 630 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: learner registry, guardian/lifecycle/import workflows, finance, attendance, notices, audit browsing, and browser/device review remain open or linked to canonical routes. Docker Compose was not started from the restricted shell because Docker socket access was denied.

Next steps: begin browser acceptance for the completed platform, school, academic, and staff slices, then continue with learner/resource migration.

## 2026-09-22 — FI-04 staff role Filament mutations

Status: implemented and locally verified; invitation revocation, remaining resource migration, and browser acceptance remain open.

Changes: added school-admin-only role assignment and removal actions to the tenant-scoped Filament staff directory. Memberships are resolved through the selected school and mutations delegate to `ManageSchoolRole`, preserving staff-role restrictions, removed-membership checks, last-active-school-admin protection, and audit events. Hardened `AcademicYearFactory` with unique generated names after the full regression run exposed a random duplicate-year fixture collision.

Verification: focused FI-04 workspace tests passed (10 tests, 46 assertions); full PHPUnit suite passed (160 tests, 627 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: invitation revocation remains on the canonical staff workflow. No browser/device review has been performed. Docker Compose was not started from the restricted shell because Docker socket access was denied.

Next steps: migrate invitation revocation, then begin browser acceptance for the completed Filament panel slices.

## 2026-09-22 — FI-04 staff invitation Filament mutation

Status: implemented and locally verified first staff mutation; role management, revocation, remaining resource migration, and browser acceptance remain open.

Changes: added school-admin-only staff invitation creation to the tenant-scoped Filament staff directory. Verified invitee options are limited to accounts not already active in the selected school; submission rechecks school-admin membership and delegates to `CreateSchoolInvitation`, preserving token generation, expiry, and audit behavior. Added Livewire coverage for invitation and audit persistence.

Verification: focused FI-04 workspace tests passed (9 tests, 40 assertions); full PHPUnit suite passed (159 tests, 621 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: role assignment/removal and invitation revocation remain on the canonical staff workflow. No browser/device review has been performed. Docker Compose was not started from the restricted shell because Docker socket access was denied.

Next steps: migrate role management or invitation revocation, preserving last-administrator and active-membership safeguards.

## 2026-09-22 — FI-04 teaching-assignment Filament mutation

Status: implemented and locally verified; the first academic mutation pass is complete, while staff mutations, remaining resource migration, and browser acceptance remain open.

Changes: added school-admin-only teaching-assignment creation to the tenant-scoped Filament academic structure page. Class groups, subjects, and active teachers are loaded from the selected school; submission rechecks tenant ownership and teacher role membership before delegating to `CreateTeachingAssignment`. Added Livewire coverage for assignment persistence and audit recording. Qualified the joined class-group selector query after verification exposed an ambiguous `status`/`name` column risk.

Verification: focused FI-04 workspace tests passed (8 tests, 37 assertions); full PHPUnit suite passed (158 tests, 618 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: invitations, role management, learner registry, finance, attendance, notices, and audit browsing remain linked or outside the Filament panel. No browser/device review has been performed. Docker Compose was not started from the restricted shell because Docker socket access was denied.

Next steps: migrate school staff mutations, starting with invitation creation, then perform browser acceptance for the completed panel slices.

## 2026-09-22 — FI-04 class-group Filament mutation

Status: implemented and locally verified; teaching assignments, remaining resource migration, and browser acceptance remain open.

Changes: added school-admin-only class-group creation to the tenant-scoped Filament academic structure page. The action resolves the academic year through the selected school, reuses `ClassGroupPolicy` and `CreateClassGroup`, validates year-scoped uniqueness, and preserves the audit event. Added Livewire coverage for persistence, audit recording, and duplicate-name rejection.

Verification: focused FI-04 workspace tests passed (7 tests, 34 assertions); full PHPUnit suite passed (157 tests, 615 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: teaching assignments remain on the canonical academic management page. No browser/device review has been performed. Docker Compose was not started from the restricted shell because Docker socket access was denied.

Next steps: migrate teaching-assignment creation with explicit class, subject, teacher, and tenant checks.

## 2026-09-22 — FI-04 term Filament mutation

Status: implemented and locally verified; remaining academic resources and browser acceptance remain open.

Changes: added school-admin-only term creation to the tenant-scoped Filament academic structure page. The action resolves the academic year through the selected school, reuses `TermPolicy` and `CreateTerm`, validates unique term names and date overlap, and preserves the audit event. Added Livewire coverage for persistence, audit recording, and overlapping-date rejection.

Verification: focused FI-04 workspace tests passed (6 tests, 28 assertions); full PHPUnit suite passed (156 tests, 609 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: class groups and teaching assignments remain on the canonical academic management page. No browser/device review has been performed. Docker Compose was not started from the restricted shell because Docker socket access was denied.

Next steps: migrate class groups or teaching assignments after their parent-record and teacher-scope checks receive equivalent Filament coverage.

## 2026-09-22 — FI-04 subject Filament mutation

Status: implemented and locally verified; remaining academic resources and browser acceptance remain open.

Changes: added school-admin-only subject creation to the tenant-scoped Filament academic structure page. The action delegates to `CreateSubject`, preserves the audit event, and normalizes subject codes before validation. The existing `StoreSubjectRequest` now applies the same normalization so HTTP and Filament paths reject case-insensitive duplicate codes consistently.

Verification: focused FI-04 workspace tests passed (5 tests, 22 assertions); full PHPUnit suite passed (155 tests, 603 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: terms, class groups, and teaching assignments remain on the canonical academic management page. No browser/device review has been performed. Docker Compose was not started from the restricted shell because Docker socket access was denied.

Next steps: migrate terms or class groups after their policy/service boundaries receive equivalent Filament coverage.

## 2026-09-22 — FI-04 academic-year Filament mutation

Status: implemented and locally verified first mutation; remaining academic resources and browser acceptance remain open.

Changes: added school-admin-only academic-year creation to the tenant-scoped Filament academic structure page. The action validates the selected school and uniqueness/date rules, delegates persistence to `CreateAcademicYear`, and preserves the existing audit event. Added Livewire coverage for persistence and audit recording while retaining the existing HTTP management route.

Verification: focused FI-04 workspace tests passed (4 tests, 16 assertions); full PHPUnit suite passed (154 tests, 597 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: terms, class groups, subjects, and teaching assignments remain on the canonical academic management page. No browser/device review has been performed. Docker Compose was not started from the restricted shell because Docker socket access was denied.

Next steps: complete broader verification, then migrate the next academic mutation only after its policy/service boundary has equivalent coverage.

## 2026-09-22 — FI-04 tenant-scoped staff and academic adapters

Status: implemented and locally verified first slice; full resource/form migration and browser acceptance remain open.

Changes: added Filament school-panel pages for the selected school's active staff/pending invitations and academic years/classes, subjects, and teaching assignments. Added explicit panel navigation and linked both pages to the existing canonical management routes. Added coverage for staff visibility, cross-school academic isolation, and guardian denial. No mutation workflow or policy/service boundary was duplicated.

Verification: focused Filament tests passed (11 tests, 27 assertions); full PHPUnit suite passed (153 tests, 594 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan route:list --name=filament.school` reported the new tenant routes; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: pages are read-only adapters and do not yet provide Filament forms/resources for invitations, role management, academic years, classes, subjects, or assignments. Docker Compose was not started from the restricted shell because Docker socket access was denied.

Next steps: run the full regression suite and frontend build, then migrate the first FI-04 mutation resource only after its policy/service parity tests are in place.

## 2026-09-22 — FI-02 workspace landing and navigation

Status: implemented and locally verified.

Changes: replaced the default Filament dashboard landing with platform and school workspace pages. The platform landing links to the school directory and reports school-status counts. The tenant-aware school landing identifies the selected school and links to the existing overview, learner registry, academic structure, attendance, fees, and communications routes. The panel links do not duplicate write workflows or bypass existing policies/services. Updated access tests to follow the panel root redirects and verify the school landing.

Verification: focused Filament panel/directory tests passed (8 tests, 14 assertions); full PHPUnit suite passed (150 tests, 581 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: browser/device review has not been performed. Panel resources, mobile-specific acceptance, global search scoping, and the migration of school writes remain open. Docker Compose was not started from the restricted shell because Docker socket access was denied.

Next steps: begin FI-04 with staff membership and academic resource adapters, preserving the current Blade/Livewire pages until parity tests pass.

## 2026-09-22 — FI-03 platform school directory and provisioning

Status: implemented and locally verified first slice; browser acceptance and additional platform operations remain open.

Changes: added the `SchoolDirectory` Filament page to the platform panel. It lists school metadata and membership counts, validates school provisioning inputs and a verified first administrator, and delegates creation to `ProvisionSchool`. Added platform-directory HTTP/Livewire coverage for admin visibility, ordinary-adult denial, and successful provisioning. No school learner, guardian, finance, or audit records are exposed through the directory.

Verification: FI-03 focused tests passed (3 tests, 7 assertions); full PHPUnit suite passed (150 tests, 579 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan view:cache --no-interaction` passed; `npm run build` passed; `git diff --check` passed.

Limitations: this is a custom page, not a generic school resource. It has no edit/suspend/delete actions, browser/device review, pagination for large school counts, or support/impersonation controls. Docker Compose was not started from the restricted shell because Docker socket access was denied.

Next steps: complete FI-02 workspace landing/navigation and add FI-03 browser acceptance, then proceed to staff/academic resource adapters.

## 2026-09-22 — FI-02 panel access foundations

Status: implemented and locally verified foundation; resource migration and browser cutover remain in progress.

Changes: replaced the generated single admin panel with separate `platform` and tenant-aware `school` panels. Added `FilamentUser`/`HasTenants` access methods to `User`, restricting platform access to verified global administrators and school access to active staff memberships. Added the first five feature tests for platform access, school tenant access, guardian denial, and removed-membership denial. Existing Blade/Livewire routes and domain services remain unchanged.

Verification: focused FI-02 tests passed (5 tests, 5 assertions); full PHPUnit suite passed (147 tests, 572 assertions); `vendor/bin/pint --dirty --format agent` passed; `php artisan route:list --name=filament` shows platform login/dashboard and school tenant/login routes; `git diff --check` passed. Filament 5 documentation was checked for `FilamentUser`, `canAccessPanel()`, and tenant contracts before implementation.

Limitations: the panels currently expose only Filament's default dashboard. No school/platform resources, custom workspace landing, navigation migration, relationship scoping, or browser review has been completed. Docker Compose could not be started from the restricted shell because Docker socket access was denied.

Next steps: add the FI-02 workspace landing pages and panel-specific navigation, then begin FI-03 platform school directory/provisioning using `ProvisionSchool`.

## 2026-09-22 — Filament installation and panel foundation

Status: implemented and locally verified package foundation; platform/school panels and authorization adapters remain in progress.

Changes: installed `filament/filament:^5.0`, resolved to Filament 5.8.4 with 32 additional packages, registered the generated `AdminPanelProvider`, published Filament assets, and added the provider to `bootstrap/providers.php`. No existing school routes, models, policies, or write services were replaced.

Verification: `php artisan filament:about` reports Filament v5.8.4; `php artisan route:list --name=filament` reports the admin dashboard/login/logout and export/import routes; `php artisan test --compact` passed with 142 tests and 567 assertions; `vendor/bin/pint --dirty --format agent` passed; `npm run build` passed. `docker compose up -d` could not run from the restricted shell because access to `/var/run/docker.sock` was denied; no migration was introduced by Filament installation. `git diff --check` passed.

Limitations: the generated `/admin` panel is still the default Filament dashboard and is not yet authorized for the platform-admin/school-membership model. School resources, panel separation, tenant switching, and browser acceptance are not implemented.

Next steps: build FI-02 panel foundations with explicit platform and school access gates; keep current Blade/Livewire workflows and SP4-02 available but unchanged during migration.

## 2026-09-22 — FI-01 Filament compatibility and authorization inventory

Status: verified; panel implementation and dependency installation remain deferred.

Changes: completed the read-only route, middleware, policy, request-validation, and school-service inventory for the proposed platform and school panels. Confirmed the existing verified-adult/global-admin boundary, active school-membership boundary, scoped school routes, explicit-school policy signatures, and transactional `app/Services/Schools` write operations. Attempted a Composer dry run for `filament/filament:^5.0` without changing project files.

Verification: `php artisan route:list --except-vendor` reported 81 application routes. Composer dry run resolved `filament/filament:^5.0` to Filament 5.8.4 with 32 package installs, zero updates, and zero removals; Composer reverted its temporary changes and SHA-256 hashes for `composer.json` and `composer.lock` were unchanged. No migrations, application code, dependencies, or generated assets were changed by FI-01.

Limitations: the packages have not yet been installed or booted in the application. The existing school policies require explicit `School` arguments in several `viewAny`/`create` checks, so default Filament resource authorization cannot be adopted without an adapter. No platform or school panel exists yet.

Next steps: approve and run the actual Filament installation, verify package boot/assets, then begin FI-02 panel foundations. Keep SP4-02 paused.

## 2026-09-22 — Feature pause and Filament integration plan

Status: planning complete; Filament implementation not started. Owner explicitly paused feature work.

Changes: created `docs/plans/filament-integration.md` with current-workflow mapping, two proposed panels, authentication/tenant boundaries, shared validation/service adapters, eight sequenced tickets, estimates, acceptance checks and rollback. Updated roadmap, detailed plan, index and D29. Laravel architecture/security guidance informed service reuse and explicit policy boundaries.

Verification: inspected current requirements, roadmap, school policy/middleware and fee-posting service; reviewed official Filament 5 installation and tenancy documentation. Recorded fee preview, key reuse, term-status and amount-display concerns as prerequisites, not completed fixes. Checked documentation diff for whitespace and new relative links. No application tests, installation, seeding, migrations or builds run for this documentation-only change.

Limitations: dependency compatibility remains unproven until FI-01; current app/test evidence is historical, not new verification. No credentials or student data were added to docs.

Next steps: FI-01 compatibility and authorization inventory, then FI-02 panel foundations and FI-03 platform provisioning. SP4-02 remains paused.

## 2026-09-22 — Local browser navigation and demo seed

Status: implemented and locally verified.

Changes:

- Updated landing and dashboard navigation so the current school workflows are reachable after login: Attendance for school members, and Fees/Communications for school administrators.
- Added an environment-guarded, idempotent synthetic demo school seeder with platform administrator, school administrator, teacher, guardian, learner, academic structure, teaching assignment, guardian link, and fee schedule records.
- Added the local browser setup note to the verification/operations documentation without recording credentials in project docs.

Affected areas: landing page, authenticated dashboard, local database seeding, navigation, verification documentation, and implementation records.

Verification:

- `vendor/bin/pint --dirty --format agent`: passed.
- `php artisan db:seed --force --no-interaction`: passed against Docker PostgreSQL after correcting the seed data to match the existing teaching-assignment schema.
- `docker compose ps`: PostgreSQL and supporting local services running; Tika remains unhealthy as previously configured.

Limitations: the platform administrator is seeded for service/provisioning checks; no platform-admin web console exists yet. Credentials are supplied in the developer handoff, not stored in documentation.

## 2026-09-22 — SP4-01 first fee schedule and charge-posting slice

Status: implemented and locally verified first slice; opening-balance sign-off, receipts, allocations, reversals, statements, and reconciliation remain deferred.

Changes:

- Added school-scoped fee schedules, charge batches, and posted charge tables with integer minor-unit amounts, currency, optional term/class scope, immutable charge snapshots, and idempotent school/batch keys.
- Added school-admin fee routes, validation, policy, preview/post service, fees page, navigation, audit events, and factories.
- Added focused coverage for class-targeted preview/post, idempotent retry, schedule snapshot immutability, and authorization; synchronized SP4 planning, architecture, and data-model documentation.

Affected areas: fee schedules, charge batches, posted charges, school finance routes/views, migrations, tests, and project documentation.

Verification:

- Focused fee tests: 3 passed, 15 assertions.
- Full PHPUnit suite: 142 tests passed, 567 assertions.
- `vendor/bin/pint --dirty --format agent`: passed.
- `npm run build`: passed with Vite 8.3.0.
- `docker compose up -d`: services running; all three fee migrations applied successfully and `php artisan migrate:status` reports them ran.

Limitations: opening-balance import/sign-off, receipt evidence, sibling allocations, credits/refunds, statements, reconciliation, and boarding/residence fee rules are not included in this slice.

## 2026-09-22 — SP3-04 first in-app notices slice

Status: implemented and locally verified; external email/SMS delivery, retries, and read/acknowledged state remain deferred.

Changes:

- Added school-scoped announcements and message-delivery ledger migrations, models, factories, policy, request validation, send service, controller, routes, admin navigation, and communication page.
- Added school-wide and current-class guardian targeting with transactional, idempotent in-app delivery and an audit event for sends.
- Extended the guardian portal with delivered notices constrained to the authenticated guardian.
- Added focused coverage for draft/send, class targeting, idempotent resend, guardian visibility, and school-admin authorization; synchronized the SP3 plan, architecture, data model, and roadmap records.

Affected areas: announcements, message deliveries, school communications, guardian portal, migrations, feature tests, and project documentation.

Verification:

- Focused announcement tests: 3 passed, 20 assertions.
- Full PHPUnit suite: 139 tests passed, 552 assertions.
- `vendor/bin/pint --dirty --format agent`: passed.
- `npm run build`: passed with Vite 8.3.0.
- `docker compose up -d`: services running; both announcement migrations applied successfully and `php artisan migrate:status` reports all migrations ran.

Limitations: only the in-app channel is implemented; delivery providers, queue retries, read state, and alert confirmation/suppression are not included in this slice.

## 2026-09-22 — SP3-03 guardian attendance views

Status: implemented and locally verified; notices, delivery, and alert confirmation/suppression remain planned.

Changes:

- Updated the SP3 attendance plan before implementation and recorded SP3-03 as complete.
- Extended the guardian portal with active linked-child selection and read-only attendance history.
- Re-resolved guardian links, school status, and enrolment status on every request; unknown or revoked child selections return 404.
- Constrained attendance queries by the selected active relationship and school, preventing sibling and cross-school leakage.
- Added focused guardian portal coverage and synchronized the roadmap, architecture, data model, and documentation index.

Affected areas: guardian portal controller/view, attendance read queries, feature tests, and project documentation.

Verification:

- Focused guardian tests: 8 passed, 54 assertions, including existing relationship regressions.
- Full PHPUnit suite: 136 tests passed, 532 assertions.
- `vendor/bin/pint --dirty --format agent`: passed after controller indentation/import fixes.
- `npm run build`: passed with Vite 8.3.0.
- `git diff --check`: passed.

Limitations: verification used synthetic guardian/learner records; no real child data or browser/device review was performed. Attendance is read-only here; notices, delivery status, alert suppression, and published statements/results remain open.

Next step: implement SP3-04 scoped notices and delivery ledger.

## 2026-09-22 — SP3-02 attendance corrections

Status: implemented and locally verified; guardian attendance views, alert confirmation/suppression, notices, and delivery remain planned.

Changes:

- Updated `docs/plans/sp3-attendance-registers.md` before implementation and recorded SP3-02 as complete.
- Added immutable `attendance_entry_corrections` records containing previous/new status, reason, actor, timestamp, and register version.
- Extended attendance saves so status changes require a reason, stale versions are rejected before writes, unchanged statuses create no correction, and correction history commits atomically with the register update.
- Added correction inputs to the attendance register UI and synchronized the roadmap, architecture, data model, and documentation index.

Affected areas: attendance correction migration/model/factory, attendance request/service/view, feature tests, and project documentation.

Verification:

- Focused attendance tests: 5 passed, 28 assertions.
- Full PHPUnit suite: 133 tests passed, 511 assertions.
- `vendor/bin/pint --dirty --format agent`: passed after one import-order fix.
- `npm run build`: passed with Vite 8.3.0.
- Docker Compose PostgreSQL migration: `2026_09_22_090217_create_attendance_entry_corrections_table` applied successfully; `php artisan migrate:status` reports the attendance migrations as `Ran`.

Limitations: the test suite uses synthetic data and does not prove real simultaneous PostgreSQL edits. No alert job or delivery channel exists yet; correction history is prepared for that later slice but no notification is sent here.

Next step: implement SP3-03 restricted guardian attendance views and child switching.

## 2026-09-22 — SP3-01 attendance registers

Status: implemented and locally verified for the attendance-register slice; attendance corrections, guardian attendance views, notices, and delivery remain planned.

Changes:

- Created `docs/plans/sp3-attendance-registers.md` and moved SP3 to in-progress in the active roadmap.
- Added school-scoped `attendance_sessions` and `attendance_entries` migrations, models, factories, relationships, and indexes.
- Added assigned-teacher/school-admin authorization, validated attendance register input, a transactional save service, and an audit event for each saved register.
- Added explicit `unmarked`, `present`, `absent`, `late`, and `excused` entry states. Active dated class placements determine the expected register entries.
- Added logical session uniqueness, retry-safe updates, version increments, and stale-version rejection to prevent silent concurrent overwrites.
- Added protected attendance routes, a responsive register page, and school navigation links.
- Updated the architecture, data model, README, roadmap, detailed plan, and this implementation record.

Affected areas: attendance migrations/models/factories, school policy/request/service/controller/routes, Blade navigation and attendance UI, feature tests, and project documentation.

Verification:

- Focused attendance tests: 4 passed, 23 assertions.
- Full PHPUnit suite: 132 tests passed, 506 assertions.
- `vendor/bin/pint --dirty --format agent`: passed after applying two formatter fixes.
- `npm run build`: passed with Vite 8.3.0.
- `git diff --check`: passed.

Limitations: verification used the local test database and synthetic learners; PostgreSQL concurrency and hosted CI execution were not rerun in this slice. Attendance correction/exception confirmation, guardian attendance display, notices, delivery status, SMS controls, printable exports, and manual browser review remain open.

Next steps: implement SP3-02 attendance correction and confirmed exception handling, then add guardian attendance views before notices/delivery.

## 2026-09-22 — SP2 promotion, transfer and deactivation

Status: implemented and locally verified; bulk lifecycle actions, reactivation, notifications, authorized exports, and broader learning workflows remain open.

Changes:

- Created docs/plans/sp2-promotion-transfer-deactivation.md before implementation and updated it with completed status.
- Added audited same-school promotion that closes the prior dated class placement without rewriting history.
- Added dual-school-admin transfer that creates a destination enrolment, withdraws the source enrolment, and retains the learner profile and source history.
- Added enrolment/profile/managed-access deactivation with a new learner deactivation timestamp and login/middleware rejection.
- Added lifecycle request validation, routes, school learner-record controls, and focused authorization/history tests.
- Synchronized the roadmap, architecture, and data model documentation.

Affected areas: user schema, learner lifecycle services, requests/controllers/routes, learner record UI, managed learner authentication, tests, docs.

Verification:

- Focused lifecycle tests: 4 passed, 26 assertions.
- Full PHPUnit suite against PostgreSQL: 128 passed, 483 assertions.
- Migration applied successfully to local PostgreSQL.
- vendor/bin/pint --dirty --format agent: passed.

Limitations: reactivation, bulk promotion/transfer, destination-school notifications, boarding placement details, authorized exports, and hosted PostgreSQL CI verification remain open.

Next step: implement authorized learner exports or begin SP3 attendance and notices, based on the next roadmap priority.

## 2026-09-22 — SP2 managed learner access

Status: implemented and locally verified; promotion/transfer/deactivation workflows, authorized exports, and broader learner learning workflows remain open.

Changes:

- Created docs/plans/sp2-managed-learner-access.md before implementation and updated it with completed status.
- Added managed learner account metadata, nullable email, profile linkage, hashed activation records, and one-use expiry-bound activation.
- Added admin provisioning, generated learner login IDs, activation/password setup, learner login/logout, restricted learner dashboard, and adult-route middleware.
- Added admin learner-record UI with secure demo activation-link handoff and audit event.
- Added focused security/authentication coverage and synchronized roadmap, architecture, and data model docs.

Affected areas: user/profile schema, learner activation model/factory, auth requests/controllers/middleware/routes, school learner controller/view, learner views, tests, docs.

Verification:

- Focused managed-learner tests: 5 passed, 35 assertions.
- Full PHPUnit suite against PostgreSQL: 124 passed, 457 assertions.
- Migrations applied successfully to local PostgreSQL.
- Blade view cache: passed.
- vendor/bin/pint --dirty --format agent: passed.
- npm run build: passed with Vite 8.3.0.
- git diff --check: passed.

Limitations: email/SMS delivery, learner password recovery, guardian-led activation, shared-device/session policy, deactivation UI, transfers, and courses/assignments remain open. Hosted PostgreSQL CI verification remains pending.

Next step: implement promotion, transfer, and deactivation workflows.

## 2026-09-22 — SP2 dated learner class memberships

Status: implemented and locally verified; managed learner access, transfers, authorized exports, and progression remain open.

Changes:

- Created docs/plans/sp2-dated-class-memberships.md before implementation and updated it with the completed status.
- Added `learner_class_memberships` with school, enrolment, class, effective start/end dates, status, and access-path indexes.
- Added model relationships, school-admin policy, request validation, and an enrolment-locking assignment service that rejects overlapping placements.
- Added learner-record class placement form and staff-visible placement history with school-scoped routes and audit events.
- Added focused coverage for dated placement creation, historical retention, overlap rejection, staff read access, admin authorization, and cross-school isolation.
- Updated the roadmap, architecture, data model, and SP2 implementation status.

Affected areas: dated class-membership schema/model/factory, learner policy/request/controller/service/routes, learner record Blade view, feature tests, and documentation.

Verification:

- Focused dated class-membership tests: 5 passed, 24 assertions.
- Full PHPUnit suite against PostgreSQL: 119 passed, 422 assertions.
- Migration applied successfully to the local PostgreSQL service.
- Blade view cache: passed.
- vendor/bin/pint --dirty --format agent: passed.
- npm run build: passed with Vite 8.3.0.
- git diff --check: passed.

Limitations: promotion automation, bulk placement imports, class capacity, automatic end dates, transfers, managed learner login, and authorized exports remain open.

Next step: implement managed learner access and restricted learner authentication.

## 2026-09-22 — SP2 staged learner imports

Status: implemented and locally verified; managed learner access, dated class memberships, transfers, authorized exports, and progression remain open.

Changes:

- Created docs/plans/sp2-learner-imports.md before implementation and updated it with the completed status.
- Added school-scoped `import_batches` and `import_rows` tables with source checksums, parsed payloads, validation errors, statuses, commit linkage, and replay-safe constraints.
- Added staged CSV upload, fixed-header validation, per-row preview, valid-row commit, invalid-row retention, checksum replay protection, and audited staging/commit services.
- Added school-admin authorization, scoped routes, import preview UI, registry upload controls, and recent batch links.
- Added focused coverage for validation, partial commit, replay idempotency, authorization, malformed headers, and cross-school isolation.
- Updated the roadmap, architecture, data model, and SP2 implementation status.

Affected areas: import schema/models/factories, school policy/request/controller/services/routes, learner registry views, feature tests, and documentation.

Verification:

- Focused learner-import tests: 5 passed, 28 assertions.
- Full PHPUnit suite against PostgreSQL: 114 passed, 398 assertions.
- Migration applied successfully to the local PostgreSQL service.
- Blade view cache: passed.
- vendor/bin/pint --dirty --format agent: passed.
- npm run build: passed with Vite 8.3.0.
- git diff --check: passed.

Limitations: the first import shape excludes class assignments, transfer matching, header versioning, formula-safe exports, and operational retention limits. Hosted PostgreSQL CI verification remains pending.

Next step: implement managed learner access and dated class memberships.

## 2026-09-22 — Product UI synchronization

Status: implemented and locally verified.

Changes:

- Created docs/plans/ui-synchronization.md before implementation and updated it with the completed status.
- Replaced the AI-only landing-page message with current school administration, teaching, learner registry, academic structure, teaching assignment, and verified guardian messaging.
- Updated the authenticated dashboard to show active school workspaces with direct overview, learner, and academic links while retaining personal study tools.
- Expanded desktop and mobile navigation to expose implemented school areas and preserved conditional guardian navigation.
- Updated architecture documentation and recorded the UI boundary that planned attendance, fees, reports, and imports are not presented as available.

Affected areas: landing Blade view, dashboard route/view, responsive navigation view, UI plan, architecture documentation, and implementation log.

Verification:

- Full PHPUnit suite: 109 passed, 370 assertions.
- Blade view cache: passed.
- vendor/bin/pint --dirty --format agent: passed.
- npm run build: passed with Vite 8.3.0.
- School route inventory confirmed the implemented overview, learner, academic, and teaching-assignment routes.
- git diff --check: passed.

Limitations: browser review of authenticated states still requires a logged-in local session; attendance, fees, reports, imports, and managed learner access remain intentionally absent from navigation because they are not implemented.

Next step: implement staged learner imports, then expose that workflow after its authorization and validation are complete.

## 2026-09-22 — SP2 teaching assignments

Status: implemented and locally verified; dated learner class memberships, imports, managed learner access, and progression remain open.

Changes:

- Created docs/plans/sp2-teaching-assignments.md before implementation and updated it with the completed status.
- Added the school-scoped `teaching_assignments` migration, model, factory, relationships, policy, request validation, and creation service.
- Added administrator assignment controls and staff-visible assignment listings to the academic structure page.
- Enforced active teacher membership, same-school class/subject relationships, duplicate protection, scoped authorization, and audited creation.
- Added focused feature coverage and synchronized the roadmap, architecture, data model, and documentation index.
- Fixed a duplicate generated factory import discovered during isolated verification.

Affected areas: teaching-assignment schema, academic relationships, policy, Form Request, service, academic controller/routes, Blade view, feature tests, and project documentation.

Verification:

- Focused teaching-assignment tests: 5 passed, 19 assertions.
- Full PHPUnit suite against PostgreSQL: 109 passed, 370 assertions.
- vendor/bin/pint --dirty --format agent: passed.
- npm run build: passed with Vite 8.3.0.
- Migration applied successfully to the local PostgreSQL service.

Limitations: assignment removal, effective dates, substitutes, workload limits, dated learner class memberships, imports, managed learner access, and hosted PostgreSQL CI verification remain pending.

Next step: implement staged learner imports before attendance workflows.

## 2026-09-22 — SP2 class groups and subjects

Status: implemented and locally verified; teaching assignments, dated learner class memberships, imports, managed learner access, and progression remain open.

Changes:

- Created docs/plans/sp2-classes-subjects.md before implementation.
- Added school-scoped class groups attached to academic years and school-scoped subjects with unique codes.
- Added ClassGroup/Subject models, factories, relationships, policies, and audited creation services.
- Added admin forms and staff-visible class/subject lists to the academic structure page.
- Added focused coverage for creation, audit attribution, staff read access, admin-only mutation, duplicate scope, cross-school isolation, and same-name/code reuse across schools.
- Fixed duplicate generated factory imports discovered during isolated verification.
- Updated the roadmap, architecture, data model, and SP2 documentation status.

Affected areas: class/subject schema, academic relationships, policies, Form Requests, services, academic controller/routes, Blade views, feature tests, and documentation.

Verification:

- Focused class/subject tests: 5 passed, 20 assertions.
- Full PHPUnit suite against PostgreSQL: 104 passed, 351 assertions.
- vendor/bin/pint --dirty --format agent: passed.
- npm run build: passed with Vite 8.3.0.

Limitations: teaching assignments, dated learner class memberships, imports, progression, and curriculum taxonomy validation are not implemented. Hosted PostgreSQL CI verification remains pending.

Next step: implement teaching assignments, then staged learner imports before attendance workflows.

## 2026-09-22 — SP2 academic years and terms

Status: implemented and locally verified; classes, subjects, teaching assignments, imports, managed learner access, and progression remain open.

Changes:

- Created docs/plans/sp2-academic-terms.md before implementation.
- Added school-scoped academic years and terms with date/status fields, uniqueness, and indexes.
- Added AcademicYear/Term models, factories, relationships, policies, and audited creation services.
- Added validated admin forms for academic years and terms with invalid-range and overlap rejection.
- Added protected academic structure routes, responsive Blade view, and school overview navigation.
- Added focused coverage for creation, audit attribution, staff read access, admin-only mutations, invalid dates, overlap prevention, and cross-school isolation.
- Fixed duplicate generated factory imports discovered during isolated verification.
- Updated the roadmap, architecture, data model, and SP2 documentation status.

Affected areas: academic schema, school relationships, policies, Form Requests, services, controller/routes, Blade views, feature tests, and documentation.

Verification:

- Focused academic structure tests: 5 passed, 16 assertions.
- Full PHPUnit suite against PostgreSQL: 99 passed, 331 assertions.
- vendor/bin/pint --dirty --format agent: passed.
- npm run build: passed with Vite 8.3.0.

Limitations: this slice does not yet include class groups, subjects, teacher assignments, progression, imports, or term locking/correction workflows. Hosted PostgreSQL CI verification remains pending.

Next step: implement class groups and subjects, or staged learner imports, before attendance depends on academic structure.

## 2026-09-22 — SP2 verified guardian relationships

Status: implemented and locally verified; guardian email delivery, disputed-relationship workflow, attendance, notices, and academic structure remain open.

Changes:

- Created docs/plans/sp2-guardian-relationships.md before implementation.
- Added school-scoped guardian links bound to enrolments, existing verified users, relationship labels, verifier, status, and revocation timestamps.
- Added admin-only link/revoke services with audit events and duplicate-safe updates.
- Added GuardianLinkPolicy and validated guardian-link requests requiring an existing verified email.
- Added restricted guardian portal and navigation discovery showing only active linked learners.
- Added revocation handling that immediately removes the learner from guardian access.
- Added focused security coverage for multiple links, unrelated learner exclusion, unverified users, non-admin mutation denial, revocation, audit events, and cross-school nested binding.
- Updated the roadmap, architecture, data model, and SP2 documentation status.

Affected areas: guardian-link schema, enrolment/user relationships, school services, policies, Form Requests, controllers/routes, guardian and learner Blade views, feature tests, and documentation.

Verification:

- Focused guardian relationship tests: 5 passed, 33 assertions.
- Full PHPUnit suite against PostgreSQL: 94 passed, 315 assertions.
- vendor/bin/pint --dirty --format agent: passed.
- npm run build: passed with Vite 8.3.0.

Limitations: guardian relationships are explicitly verified by a school administrator; no email invitation/acceptance, disputed relationship workflow, evidence/retention process, attendance, notices, or learner login exists yet. Hosted PostgreSQL CI verification remains pending.

Next step: implement academic structure (terms/classes) or staged learner imports as the next SP2 workflow slice.

## 2026-09-22 — SP2 learner registry foundation

Status: implemented and locally verified; guardian links, academic structure, imports, managed learner access, and transfer workflows remain open.

Changes:

- Created docs/plans/sp2-learner-registry.md before implementation.
- Added learner profile and school enrolment migrations with school-scoped admission-number uniqueness and status/date fields.
- Added learner/enrolment models, factories, school relationships, and an audited AdmitLearner service.
- Added EnrolmentPolicy, validated admission request rules, nested school routes, and staff/admin role boundaries.
- Added learner registry and detail Blade views using the existing responsive Tailwind conventions.
- Added feature coverage for admission, audit attribution, teacher read-only access, duplicate scope, non-staff denial, and cross-school nested binding.
- Fixed a route-scoping defect found by focused tests by adding the explicit School::learners relationship expected by the {learner} nested binding.
- Updated the roadmap, architecture, data model, and SP2 documentation status.

Affected areas: learner registry schema, school/enrolment models, admission service, policy/Form Request/controller/routes, Blade views, feature tests, and documentation.

Verification:

- Focused learner registry tests: 5 passed, 19 assertions.
- Full PHPUnit suite against PostgreSQL: 89 passed, 282 assertions.
- vendor/bin/pint --dirty --format agent: passed.
- npm run build: passed with Vite 8.3.0.

Limitations: this is only the first registry slice. It has no guardian verification, classes/terms, CSV staging/import, learner login, transfer lifecycle, or production data-retention policy. Hosted PostgreSQL CI verification remains pending.

Next step: implement guardian relationship verification or academic structure, choosing the next slice based on the school workflow priority.

## 2026-09-22 — Restore local PostgreSQL availability

Status: implemented and locally verified.

Changes:

- Created docs/plans/local-postgres-availability.md documenting the startup dependency and recovery procedure.
- Started the existing postgres Compose service on host port 5433.
- Ran all pending Laravel migrations against the local PostgreSQL database.

Cause: the application environment selected PostgreSQL and database-backed sessions, but the local PostgreSQL container was stopped. The first request therefore failed while reading the sessions table.

Affected areas: local Docker service availability, PostgreSQL schema state, and database-backed Laravel sessions.

Verification:

- PostgreSQL container reported healthy with 0.0.0.0:5433->5432/tcp.
- php artisan migrate --force --no-interaction: passed.
- curl -I http://127.0.0.1:8000/: returned HTTP/1.1 200 OK.

Limitations: the database service must be started after a machine restart or container shutdown. No application fallback was added because PostgreSQL is the configured local baseline and the migrations are now PostgreSQL-compatible.

Next step: continue implementation after confirming the local Compose services are running.

## 2026-09-22 — SP1 school navigation and overview policy

Status: implemented and locally verified; durable selected-school preference and broader school-resource policies remain open.

Changes:

- Created docs/plans/sp1-school-switching-and-policy.md before implementation.
- Added a SchoolPolicy::view authorization check to the protected overview controller, preserving not-found behavior for unauthorized contexts.
- Added an authenticated navigation view composer that exposes only active memberships belonging to active schools.
- Added desktop and responsive navigation links for each available school overview.
- Added feature coverage for active-school links, removed/inactive filtering, direct policy decisions, and controller-level membership rechecks.
- Updated the roadmap, architecture, school implementation plan, and documentation index status.

Affected areas: school policy, authenticated navigation view composition, school overview controller, Blade navigation, feature tests, and project documentation.

Verification:

- Focused school-switching/policy tests: 4 passed, 14 assertions.
- Full PHPUnit suite: 84 passed, 263 assertions.
- vendor/bin/pint --dirty --format agent: passed after import ordering fixes.
- npm run build: passed with Vite 8.3.0.

Limitations: navigation selects an authorized overview but does not persist a current-school preference; learner, attendance, finance, and other school-resource policies remain planned. Hosted PostgreSQL verification remains pending.

Next step: implement the first school-owned resource with nested policy-backed access and decide whether it needs a durable selected-school preference.

## 2026-09-22 — SP1 invitation and role HTTP flows

Status: implemented and locally verified; email delivery, school switching, and broader school-resource policies remain in progress.

Changes:

- Created `docs/plans/sp1-invitation-http-flows.md` before implementation.
- Added validated invitation and role Form Requests with school-admin authorization and staff-role allow-lists.
- Added protected web routes/controllers for invitation creation, review, acceptance, revocation, role assignment/removal, and membership removal.
- Added scoped nested route binding and explicit parent checks so cross-school membership mutations return 404.
- Added responsive Blade/Tailwind forms for invitation, pending-invitation revocation, staff role management, and membership removal.
- Deliberately exposed the raw invitation link only through the inviter's one-time session flash because email delivery is not configured.
- Added HTTP feature coverage for valid invite/accept flow, validation, intended-user restriction, revocation, role lifecycle, and cross-school mutation denial.
- Fixed a scoped-binding defect found by the focused tests by type-hinting the parent `School` in the invitation destroy action.
- Updated roadmap, architecture, and SP1 documentation to distinguish implemented web flows from deferred email/switching/resource work.

Affected areas: Form Requests, school controllers/routes, scoped route binding, Blade/Tailwind school views, and feature tests.

Verification:

- Focused HTTP invitation tests: 5 passed, 24 assertions.
- Full PHPUnit suite: 80 passed, 249 assertions.
- `vendor/bin/pint --dirty --format agent`: passed after controller import ordering fixes.
- `npm run build`: passed with Vite 8.3.0.
- `git diff --check`: passed.

Limitations: invitation email/notification delivery is not configured; the demo link in session flash is not production delivery. School switching, role-specific dashboards, support access, and nested school-resource authorization remain open. Hosted PostgreSQL verification remains pending.

Next step: implement school switching/navigation and the first school-owned resource with policy-backed nested access tests.

## 2026-09-22 — SP1 invitations and role lifecycle services

Status: implemented and locally verified for the service slice; email delivery, HTTP invitation screens, school switching, and broader resource policies remain in progress.

Changes:

- Created `docs/plans/sp1-invitations-and-roles.md` before implementation.
- Added `school_invitations` with school/inviter/invitee binding, hashed token, scoped staff role, expiry, acceptance, revocation, and indexes.
- Added invitation creation, revocation, and acceptance services. Acceptance rechecks the signed-in verified user, school status, token lifecycle, and duplicate membership inside a transaction.
- Added controlled staff role assignment/removal and membership removal with same-school admin authorization and last-active-school-admin protection.
- Added `SchoolRole::isStaffRole()` to keep school-scoped staff privileges separate from global `UserRole` values.
- Added audit events for invitation creation, acceptance, revocation, role assignment/removal, and membership removal.
- Added feature tests for invitation security, token replay/expiry/revocation, cross-school access, role escalation, last-admin protection, and audit behavior.
- Updated the roadmap, architecture, data model, and SP1 plans to distinguish implemented services from remaining HTTP and delivery work.

Affected areas: school invitation schema, membership lifecycle, scoped roles, audit events, school services, factories, and feature tests.

Verification:

- Focused invitation/role tests: 7 passed, 23 assertions.
- Full PHPUnit suite: 75 passed, 225 assertions.
- `vendor/bin/pint --dirty --format agent`: passed after removing an unused test import.
- `git diff --check`: passed.

Limitations: no invitation email provider, token-link presentation, HTTP invite/accept/revoke endpoints, school switching UI, or nested school-resource policies exist yet. Hosted PostgreSQL verification remains pending.

Next step: expose the invitation lifecycle through validated HTTP routes/UI, then add school switching and role-specific route policies.

## 2026-09-22 — SP1 protected school context

Status: implemented and locally verified for the protected overview slice; invitations, role management, and scoped school-resource routes remain in progress.

Changes:

- Created `docs/plans/sp1-school-context.md` before implementation.
- Added `EnsureSchoolContext` middleware and registered the `school.context` middleware alias.
- Added protected `/schools/{school}/overview` route, controller, and responsive Blade view.
- Rechecked active school status and active membership on every request; unauthorized, removed, and inactive contexts return 404.
- Attached the authorized membership to the request without global/static context state.
- Added feature coverage for valid access, cross-school denial, removed memberships, inactive schools, guest redirects, and unverified-user redirects.
- Updated the roadmap, architecture, and SP1 documentation to distinguish this implemented boundary from later invitations and resource policies.

Affected areas: school middleware, route binding, protected school overview, Blade/Tailwind view, feature tests, and documentation.

Verification:

- Focused school-context tests: 6 passed, 11 assertions.
- Full PHPUnit suite: 68 passed, 202 assertions.
- `vendor/bin/pint --dirty --format agent`: passed after middleware formatting fixes.
- `npm run build`: passed with Vite 8.3.0.
- `git diff --check`: passed.

Limitations: this protects only the overview route; school invitations, role-management mutations, school switching UI, nested resource policies, background-job scoping, and support access are not implemented. Hosted PostgreSQL verification remains pending.

Next step: implement expiring one-use school invitations and controlled role assignment, including last-school-admin protection and audit events.

## 2026-09-21 — SP1 school provisioning and audit foundation

Status: implemented and locally verified for SP1-01's first slice; invitations, context middleware, role management, and route isolation remain in progress.

Changes:

- Created `docs/plans/sp1-school-foundation.md` before implementation.
- Added additive `schools`, `school_memberships`, `school_role_assignments`, and `audit_events` migrations with foreign keys, scoped indexes, and uniqueness constraints.
- Added `SchoolRole`, school/membership/role/audit models, factories, relationships, casts, and the user's school-membership relation.
- Added `ProvisionSchool`, an atomic service requiring a verified global platform administrator and a verified first school administrator. It assigns only `school_admin` within the school and records minimal actor/event metadata.
- Added feature coverage for successful provisioning, actor and administrator verification, duplicate-slug rollback, scoped-versus-platform roles, relationships, and audit attribution.
- Updated the active roadmap, architecture, data model, and SP1 plan to distinguish the implemented foundation from the remaining SP1 boundaries.

Affected areas: school identity schema, scoped roles, audit foundation, provisioning service, factories, and feature tests.

Verification:

- Focused school foundation tests: 5 passed, 19 assertions.
- Full PHPUnit suite: 62 passed, 191 assertions.
- `vendor/bin/pint --dirty --format agent`: passed after applying import/type formatting fixes.
- `git diff --check`: passed.

Limitations: no HTTP provisioning endpoint, invitation flow, school-context middleware, role-management UI, support access, or cross-school resource route exists yet. PostgreSQL hosted verification remains pending; local tests use the existing SQLite test setup.

Next step: implement SP1 school-context resolution and authorization middleware, then add invitation/role lifecycle tests before exposing school routes.

## 2026-09-21 — SP0 baseline lane and synthetic demo fixture

Status: implemented and locally verified; hosted PostgreSQL CI execution and schema-tool reconciliation remain open.

Changes:

- Created `docs/plans/sp0-baseline-and-synthetic-demo.md` before implementation.
- Added an independent PostgreSQL 16 GitHub Actions test job while retaining the existing SQLite job and frontend-build job.
- Added `tests/Fixtures/synthetic-school-demo.json` with two synthetic schools, distinct staff, duplicate admission-number negative input, cross-school authorization cases, and explicit R0 unsupported features.
- Kept the fixture out of `DatabaseSeeder` because school tables and membership authorization are SP1 work; no synthetic school records were inserted into the current user-centric schema.
- Updated SP0 status and documentation to distinguish local evidence, configured CI, and pending hosted verification.

Affected areas: CI workflow, synthetic test fixture, SP0 plan/status documentation, and implementation log.

Verification:

- `jq empty tests/Fixtures/synthetic-school-demo.json`: passed.
- `php artisan test --compact`: 57 tests passed, 172 assertions.
- `npm run build`: passed with Vite 8.3.0.
- `vendor/bin/pint --dirty --format agent`: passed.
- Read-only SQLite checks: migration status, route listing (38 routes), and database-default inspection passed.
- `git diff --check`: passed.

Limitations: the current local `.env` points to PostgreSQL on port 5433, but that service was not running when `php artisan migrate:status` was attempted; the command failed with a connection error and was not counted as verification. GitHub Actions has not run the new PostgreSQL job, and the Boost schema inspection issue remains unresolved. The fixture is input for future SP1 tests, not a working school seeder or proof of tenant isolation.

Next step: implement SP1 school, membership, role, and audit foundations using the fixture's logical keys and negative cases.

## 2026-09-21 — SP0 deferred-AI execution boundary

Status: implemented and locally verified for the scoped job boundary; SP0-01 baseline reconciliation and SP0-03 synthetic fixtures remain open.

Changes:

- Created `docs/plans/sp0-ai-boundary.md` before implementation.
- Added `AiExecutionContext` and `AiAvailability`, with school/managed-learner provider execution disabled by default through `AI_SCHOOL_ENABLED=false`.
- Added context checks to chat, quiz-generation, and flashcard-generation jobs before any Groq call. Blocked jobs mark their work failed with a safe terminal state and release pending usage reservations.
- Preserved personal/internal-alpha job behavior and existing records; no school tables, memberships, learner records, or dependencies were added.
- Updated the roadmap, architecture, verification notes, and documentation index to distinguish the implemented boundary from the remaining SP0 work.

Affected areas: AI configuration, queued generation jobs, usage-reservation recovery, feature tests, and SP0 documentation.

Verification:

- Focused AI/practice tests: 11 passed, 39 assertions.
- Full PHPUnit suite: 57 passed, 172 assertions.
- `vendor/bin/pint --dirty --format agent`: passed; Pint reordered imports in two test files.
- `git diff --check`: passed.

Limitations: school routes and membership-derived context do not exist yet; the enum is a forward-compatible execution boundary, not tenant authorization. The `AI_SCHOOL_ENABLED` override is intentionally available for controlled development testing and must remain disabled for the school release until product, privacy, safeguarding, and cost decisions are resolved. No provider call, PostgreSQL integration lane, production deployment, or real school data was used.

Next step: complete SP0-01 baseline/schema reconciliation and SP0-03 synthetic two-school fixtures, then implement SP1 school tenancy and scoped permissions.

## 2026-09-16 — Planning baseline and required tooling setup

Status: documentation complete; product implementation not started.

Changes:

- Inspected the fresh Laravel skeleton and dependency declarations.
- Verified PHP 8.4.24 and Composer 2.7.1 are available. Composer emitted deprecation warnings with this PHP version; dependency installation succeeded.
- Installed Laravel Boost v2.9.0 as a development dependency with its locked dependencies. Ran Boost installation and reread the generated `AGENTS.md`.
- Initial sandbox restrictions blocked network access and writes to protected agent directories. Approved retries completed installation, including Codex and Claude guidance/skills/MCP configuration.
- Added requirements traceability, staged delivery plan, proposed architecture/data model, decision register, and verification/operations planning under `docs/`.
- Added a standing documentation rule outside the generated Boost section in `AGENTS.md` requiring plans and implementation records for future changes.

Verification:

- Boost installation reported successful guidelines, skills, and MCP setup for both configured agents.
- Composer reported no security vulnerability advisories during installation; this is not a full application security review.
- `php artisan test --compact`: passed the 2 existing example tests (2 assertions). This verifies the skeleton baseline only, not planned product functionality.
- `git diff --check`: passed with no whitespace errors in tracked changes.
- Checked all 11 local Markdown links under `docs/`: every target exists.

Limitations: no authentication UI, learning, payments, institutional features, infrastructure, or external integrations have been implemented. All P0–P9 product phases remain proposed. Commercial allowances, scope conflicts, provider access, privacy policy, and operational ownership remain decisions to resolve.

Next step: work through P0 feasibility and decisions, then create the detailed P1 implementation plan before building identity and the application shell.

## 2026-09-16 — P0 local foundation and CI baseline

Status: in progress.

Changes:

- Added `compose.yaml` for local PostgreSQL with pgvector, Redis, Mailpit, and MinIO.
- Added `.env.docker.example` for those local services while preserving the default SQLite `.env.example` workflow.
- Named the application Nova Scholar in `.env.example` and added blank environment-variable placeholders for AI and M-Pesa credentials.
- Added a GitHub Actions workflow that installs locked dependencies, runs the existing test suite against SQLite, and builds Vite assets.
- Added the detailed P0 plan in `docs/plans/p0-foundation.md`.

Verification:

- `docker compose config --quiet`: passed.
- Downloaded and started the local stack. PostgreSQL could not use host port 5432 because it was already occupied, so the compose mapping and Docker environment template use host port 5433. Existing local services were not modified.
- PostgreSQL is healthy. `CREATE EXTENSION IF NOT EXISTS vector` succeeded and reports pgvector 0.8.6. Laravel's three default migrations completed against the isolated compose database.
- Redis and Mailpit health checks pass. MinIO runs on ports 9000/9001; `curl --fail http://127.0.0.1:9000/minio/health/live` passed.
- `php artisan test --compact`: passed the 2 existing example tests (2 assertions). This is a skeleton baseline, not product verification.
- `npm install --ignore-scripts`: installed the declared frontend development dependencies, created `package-lock.json`, and reported 0 vulnerabilities. The lockfile lets CI use `npm ci`.
- The initial `npm run build` failed because the Laravel starter font plugin attempted to resolve `fonts.bunny.net`. Removed the remote font integration and retained the system font stack; the final Vite build passed (three modules transformed).
- Final `php artisan test --compact`: passed the 2 existing example tests (2 assertions).
- Final `git diff --check`: passed with no whitespace errors.

Limitations: MinIO's prior Docker Hub repository and dated Quay tags were unavailable at setup time, so local development uses the currently published `quay.io/minio/minio:latest` image. Pin its resolved digest before a shared or production use. No provider credentials, provider calls, production resources, or paid services were created.

Livewire and the Laravel Breeze authentication scaffold were added for P1 after owner approval. The PHP Redis extension and application dependencies for Redis, S3, document extraction, and AI integration remain absent. P0 remains in progress pending hosted CI execution, feasibility spikes, and D04/D05 decisions.

## 2026-09-16 — P1 identity and application shell

Status: implemented and locally verified; manual responsive/accessibility review and configured-mail delivery remain before P1 can be closed.

Created `docs/plans/p1-identity-and-shell.md` before implementation. The owner approved Laravel Breeze Blade authentication plus Livewire. P1 now provides a Nova Scholar landing page, registration/sign-in/sign-out, password reset, email verification, a verified dashboard, profile name/email/password settings, private local profile photos, learning preferences, and server-controlled student roles.

Changes:

- Installed `laravel/breeze` v2.4.2 as a development dependency and `livewire/livewire` v4.4.5 as an application dependency, then generated Breeze's Blade authentication scaffold.
- Added `App\\UserRole`, with `student` assigned only by server-side registration logic and `admin` reserved for a future controlled workflow.
- Extended `users` with `role`, `profile_photo_path`, and `learning_preferences`; updated the model and factory casts/defaults and enabled Laravel email verification.
- Added validated profile preference/photo handling. JPEG, PNG, and WebP images up to 2 MB are stored privately and served only to their signed-in owner.
- Replaced the starter entry page with a responsive Nova Scholar landing page and a verified student dashboard shell.
- Restored the project to Tailwind CSS v4 after Breeze generated a v3 configuration, retaining the Tailwind Vite plugin and Breeze's Alpine behavior.

Verification:

- Applied the profile migration to the isolated PostgreSQL/pgvector compose database.
- Targeted P1 feature tests: 14 passed, 50 assertions.
- Full test suite: 29 passed, 80 assertions.
- `vendor/bin/pint --dirty --format agent`: passed after applying one import-order fix.
- `npm run build`: passed with Tailwind CSS v4 and no remote font dependency.

Limitations: No real email provider has been configured, so delivery outside local development is untested. Manual screen-reader, keyboard, and small-device testing is also outstanding. Admin provisioning, suspension, and policies for future owned learning resources remain later P1/P8 work; no user can self-assign an administrator role.

## 2026-09-16 — P2 plans, entitlements, and usage foundation

Status: implemented and locally verified; P2 remains open for approved commercial policy and production concurrency validation.

Changes:

- Added plans, price versions, plan features, subscriptions, subscription periods, usage reservations, and usage records with foreign keys, uniqueness constraints, ownership/status indexes, UTC timestamps, and integer minor units for costs.
- Added model relationships, casts, and factories for the P2 domain.
- Added `EntitlementService` to resolve only a user's current active period/plan and `UsageService` to reserve idempotently, settle once, or release feature usage. Finite allowances count both pending reservations and settled records.
- Added a verified-only, read-only `/subscription` page and dashboard link. It displays access status and any public plans but deliberately has no checkout, trial, or entitlement-granting action.
- Added P2 feature tests for access boundaries, entitlement windows, request-key idempotency, allowance exhaustion, settlement, and release behavior.

Verification:

- Started the local Docker services and applied all seven P2 migrations to the isolated PostgreSQL database.
- Focused P2 tests: 9 passed, 20 assertions.
- Full test suite: 38 passed, 100 assertions.
- `vendor/bin/pint --dirty --format agent`, `npm run build`, and `git diff --check`: passed.

Limitations: D05 remains unresolved, so no plan, price, allowance, trial, tax rule, or payment behavior is seeded. Row locking is implemented and tested functionally with the SQLite suite, but real concurrent reservation behavior must be stress-tested on production-like PostgreSQL/Redis before charging or gating live provider calls. P2 does not integrate M-Pesa, a provider adapter, queues, Redis locking, or spend alerts.

### P2 catalogue update — accepted BRD prices

The owner accepted the BRD's individual monthly prices: Student KES 299 and Pro KES 699. Added an idempotent `PlanSeeder`, invoked by `DatabaseSeeder`, that creates the two public monthly catalogue records and their KES minor-unit price versions effective 1 July 2026 UTC. Added the `billing_interval` field and displays current prices on the read-only subscription page. The School KES 5,000–20,000 monthly range remains deferred as institution-specific pricing. Allowances, trial policy, tax, payment behavior, and actual entitlements remain unresolved and unseeded.

### P3 decisions — document extraction and allowances

The owner selected Apache Tika as the private PDF/DOCX/TXT extraction service; OCR is deferred. The recommended local container is the pinned `apache/tika:3.3.1.0` image, to be digest-pinned before shared deployment. The owner also approved Student storage of 20 documents/1 GB and Pro storage of 100 documents/10 GB, with a 100 MB per-file limit. These allowances require plan-feature seeding and extraction benchmarking before enforcement.

### P3 upload foundation

Added the private document schema, user ownership relation/policy, validated verified-user upload/list/delete routes, and document-library page. Uploads accept PDF/DOCX/TXT up to 100 MB and use generated private local-storage paths; processing is dispatched after commit. Automated suite: 40 tests, 105 assertions passed; Pint passed. Tika service wiring, extraction status transitions, download endpoint, allowance enforcement, migration verification, and document-specific tests remain in progress.

### P3 Apache Tika local service

Added the pinned `apache/tika:3.3.1.0` Docker Compose service, restricted to loopback port 9998, with a health check and application configuration (`TIKA_URL`, `TIKA_TIMEOUT_SECONDS`). `docker compose config --quiet` and Laravel configuration inspection pass. Extraction job wiring, a real service start/pull verification, and document processing tests remain in progress.

### P3 queued extraction implementation

Implemented `ProcessDocument`: it streams the private original to Tika with a bounded connection/request timeout, records `extracting`, marks successful non-empty text as `ready`, records `no_extractable_text` when appropriate, and safely marks exhausted jobs as `failed`. It does not update a deleted document. Pint and the existing full test suite pass (40 tests, 105 assertions). The Tika image download had not completed during the local Docker startup attempt, so real-container extraction remains unverified.

### P4 Groq development-provider foundation

Owner selected Groq for development. Added non-secret configuration placeholders and `GroqChatService`, which uses Groq's OpenAI-compatible chat-completions endpoint, bounded timeouts, and fails clearly when no local key is configured. No key was written to the repository. Conversation persistence/controller wiring and a live request remain in progress; rotate the key supplied in chat before setting a replacement in the ignored local `.env`.

### P4 authorization compatibility fix

Laravel 13's generated base controller does not include the legacy `authorize()` helper. Chat and document controllers now use explicit `Gate::authorize()` calls, preserving ownership-policy enforcement. Full tests pass (41 tests, 106 assertions).

### Dashboard navigation map

Updated the authenticated desktop and mobile navigation plus dashboard quick-action cards to link the implemented AI Tutor, Document Library, Subscription, and Profile pages. Updated dashboard copy to distinguish available tools from upcoming quizzes, flashcards, and planner features. Frontend build, full tests, and diff checks pass.

### P4 chat worker payload fix

Status: implemented and targeted-verified; a live Groq request remains environment-dependent.

The 19:17:59 worker log showed Groq returning HTTP 400 because the request contained an empty `messages` array. The pending user prompt had been excluded by the job's `status = complete` history filter. The job now appends the current pending prompt, and exhausted provider failures mark that message `failed` instead of leaving it indefinitely pending. Chat and message factories now provide valid defaults for tests and local fixtures.

Verification:

- `php artisan test --compact tests/Feature/GenerateChatResponseTest.php`: 2 tests passed, 4 assertions.
- `vendor/bin/pint --dirty --format agent`: passed.
- The local `.env` contains a non-empty `GROQ_API_KEY` (the value is intentionally not recorded here).

Operational next step: clear cached configuration and start a fresh queue worker before submitting a new chat prompt. A live provider response is not claimed until that request succeeds.

### P4 assistant Markdown rendering

Status: implemented; visual browser verification remains recommended.

Assistant replies now render an escaped Markdown subset while user content remains escaped plain text. Failed messages display a clear retry-oriented status.

### Repository hygiene

Status: implemented and locally verified.

Added ignore rules for local Codex/Claude skill directories and Boost MCP configuration while retaining reviewable project files such as `AGENTS.md`, `.env.example`, `compose.yaml`, and `docs/`. Existing tracked `CLAUDE.md` remains tracked and was not removed automatically.

### P4 tutor boundary verification

Status: implemented and verified.

Replaced the placeholder chat feature test with coverage for verified-user access, guest and unverified denial, cross-user chat isolation, prompt validation, pending message persistence, and `GenerateChatResponse` queue dispatch.

Verification:

- Focused chat tests: 5 passed, 22 assertions.
- Full suite: 47 tests passed, 131 assertions.
- Laravel Pint passed.

P4 internal-alpha scope is complete; production exit still requires vector citations, token-based cost recording, latency measurement, and interactive cancellation.

### P5 quiz and flashcard foundation

Status: implemented and targeted-verified; attempts, scoring, and spaced review remain in progress.

Added owned quiz/question and flashcard-deck/card schemas, factories, policies, validated generation forms, queued Groq JSON generation jobs, malformed-output failure handling, and student-facing list/detail pages. Quiz generation validates question count, type, and multiple-choice option uniqueness before committing records; flashcard generation uses the same all-or-nothing persistence boundary. Dashboard navigation now links to both tools.

Verification:

- Focused P5 tests: 3 passed, 12 assertions.
- Laravel Pint passed.

Remaining P5 work: attempts and deterministic scoring, answer-key protection on attempt views, review history, document-grounded generation, and dedicated generation metering.

### P5 attempts, scoring, and flashcard reviews

Status: implemented and verified for the internal alpha.

Added quiz attempts and answers with owner checks, answer-key-safe attempt forms, deterministic multiple-choice/true-false scoring, short-answer storage for later review, idempotent submitted attempts, and flashcard review outcome history. Duplicate submissions do not overwrite the original score or create duplicate answers.

Verification:

- Focused P5 tests: 5 passed, 21 assertions.
- Full suite: 55 tests passed, 163 assertions.
- Laravel Pint passed and the frontend build remains green.

Remaining P5 hardening: dedicated generation metering, document-grounded quiz/card generation, AI short-answer feedback, and adaptive spaced repetition.

### P5 generation metering

Status: implemented and targeted-verified.

Quiz and flashcard requests now reserve dedicated generation allowances before queue dispatch and settle one unit after successful persistence. Failed jobs mark the set failed and release the reservation. Plan seeding adds provisional monthly limits of 20 Student / 100 Pro for each generation feature.

Verification:

- P5 focused tests: 5 passed, 21 assertions.
- Full suite: 55 tests passed, 163 assertions.
- Vite build, Pint, and diff checks passed.

Limitations: generation is metered per set rather than provider tokens, and document-grounded generation remains deferred.

### P5 local database migration

Status: verified against the local PostgreSQL container.

Applied the pending P4/P5 migrations, including quizzes, questions, flashcard decks/cards, attempts, answers, reviews, and chat-document selection. Re-ran `PlanSeeder` so the provisional AI-chat, quiz-generation, and flashcard-generation features exist for the local plans.

Verification: all migrations report `Ran` against the PostgreSQL database on port 5433.

### P5 PostgreSQL usage-lock fix

Status: implemented and verified by regression suite.

PostgreSQL rejected the generation request because the usage service combined `FOR UPDATE` with aggregate `SUM(...)`. Reservation accounting now locks matching usage rows first and sums the returned quantities in application memory, preserving the concurrency boundary without unsupported SQL.

Verification: full PHPUnit suite passed after the fix; the affected reservation paths no longer generate aggregate-lock SQL.

### P4 usage metering and recovery

Status: implemented and verified for the internal alpha.

Chat requests now reserve one `ai_chat` allowance before queue dispatch. Successful provider jobs settle the reservation into a usage record; failed jobs release it and mark the message failed. Expired pending reservations are released before allowance calculations. The plan seeder adds provisional development allowances of 100 Student requests and 500 Pro requests per monthly period.

Verification:

- Full suite: 50 tests passed, 142 assertions.
- Focused chat/generation tests and existing usage tests passed.
- Laravel Pint passed.

Limitations: the quantity is one request rather than provider token usage, provider cost is not yet recorded, and cancellation is represented by queue failure/release rather than an interactive cancel control. D19 must be revisited before paid beta.

### P4 grounded document context

Status: implemented and targeted-verified; retrieval and citations remain deferred.

Added the chat/document selection pivot, ownership and readiness checks for selected notes, a multi-select tutor control, and bounded context injection for queued Groq requests. The context is labeled as untrusted reference material so uploaded text cannot override provider instructions. Processing, failed, or another user's documents cannot be attached.

Verification:

- Chat and generation tests: 9 passed, 34 assertions.
- Full suite: 49 tests passed, 139 assertions.

Limitations: this is bounded whole-document context, not vector retrieval; page/section citations, embeddings, insufficient-evidence evaluation, usage reservation settlement, and cancellation remain future P4 work.

## 2026-09-17 — Competitive landscape and Kenya market research

Status: desk research and documentation complete; recommendations proposed for owner review.

Changes:

- Created `docs/plans/competitive-market-research.md` before drafting the report.
- Added `docs/competitive-market-research.md`: cited competitor capabilities and commercial approaches; Kenyan university/TVET structure and access evidence; positioning, prioritized features, pricing experiments, distribution, and an interview/benchmark/pilot plan.
- Distinguished published product claims and statistics from recommendations and untested hypotheses. Recorded source periods and retrieval limitations, and explicitly identified OCR, offline study, trial access and other potential scope changes.
- Added the report to `docs/README.md` and corrected stale P4/P5/baseline descriptions in the documentation index and roadmap using existing implementation records.
- Documented the GPT-6 research recommendation and the absence of an exposed session-model setting control. Application provider settings were not changed.

Affected areas: project documentation and roadmap status only. Existing application changes remain outside this research task.

Verification: final local checks covered five documentation files (16 links, no missing targets or trailing whitespace); `git diff --check -- docs` passed. Reviewed source support, date/denominator distinctions, annual-versus-monthly pricing, illustrative arithmetic, and separation of proposed work from implementation. Application tests were not rerun because this task changes documentation only; earlier test results in the report are explicitly historical.

Limitations: public-source desk research is not a student survey or hands-on competitor benchmark. CUE/KNBS full-document retrieval and some product pages failed; indexed official evidence and limitations are disclosed. No Kenyan competitor market shares, validated willingness to pay, legal clearance or independently demonstrated learning improvements are claimed.

Next steps: owner reviews the report, selects discovery cohorts and course families, and decides which recommendations should change the implementation plans. Student recruitment, interviews, purchases, provider changes and implementation were not performed.

## 2026-09-17 — School, parent and educator market follow-up

Status: desk research and documentation complete; recommendations proposed for owner review.

Added `docs/school-parent-educator-market-research.md`, comparing grade bands and adult tertiary, CBC/CBE transition, existing learning products, parent-funded catch-up/holiday needs, educator tools versus marketplaces, child-service safeguards, commercial hypotheses and a comparative discovery/pilot design. Integrated the original report's conclusions and updated the documentation index, research plan and roadmap checkpoint. Existing adult scope and application work remain unchanged.

Verification: reviewed cited official/vendor evidence and retrieval limitations; checked the enrolment sums and illustrative revenue arithmetic. Local Markdown targets and whitespace were checked across all six research-related documents; `git diff --check -- docs` passed. Application tests were not rerun for this documentation-only change.

Limitations: no interviews, paid trials, competitor account testing, legal clearance or validated willingness to pay. Some official evidence was available only through indexed passages, explicitly identified in the report. Recommendations do not authorize child onboarding, holiday tuition or marketplace operations.

Next steps: owner reviews both reports, chooses recruitment/reviewer access and discovery budget, then selects one initial market based on evidence. Approve revised requirements and a dedicated child-service plan before implementing a school route.

## 2026-09-21 — School management and connected e-learning study

Status: desk research and documentation complete; local documentation checks verified; recommendations proposed.

Changes: created the study plan before drafting `docs/school-management-learning-strategy-study.md`. Compared the two earlier studies with a school-led business; examined common/day/boarding operations, current vendor evidence, school-included and independent learning access, proposed pricing and cost sensitivity, three separate money flows, child-data boundaries, conceptual architecture, project reuse, onboarding and staged pilot gates. Linked the study from both earlier reports, the documentation index and roadmap. Existing application changes and accepted product requirements were preserved.

Affected areas: seven documentation files, including two new research documents. No code, dependencies, database data, configured prices or deployment changed.

Verification: seven documentation files checked; all 32 local Markdown targets resolved, no trailing whitespace, and `git diff --check -- docs` passed. Sixteen illustrative financial/access calculations were independently recalculated with matching results, including the cost sensitivity of school-included learning. Reviewed source support, pricing inconsistencies and proposed-versus-observed distinctions. No application tests run for this documentation-only task.

Limitations: no school/parent interviews, product-account trials, payments, legal clearance or validated demand. Several official pages/PDFs could only be read through indexed material or could not be retrieved; the report identifies these cases. The owner clarified that individual subscriptions target students at non-subscribing schools; the final recommendation and packages reflect that clarification. Project capability inventory is not a code audit or new production verification.

Next steps: review the proposed school direction, identify reachable partners and operations/content reviewers, set a bounded discovery budget, then use the study's evidence gates to choose scope. Approve revised institutional/child-service requirements and a dedicated implementation plan before a real-data pilot or product pivot.

## 2026-09-21 — Accepted school pivot, further workflow study and actionable plan

Status: owner direction accepted; research and planning complete; documentation checks verified. SP0–SP9 application implementation has not started. This entry supersedes the preceding research entry's unresolved strategic direction, while prices and real-data readiness remain separate decisions.

Owner decisions: school administration plus e-learning becomes the product focus; AI is deprioritized. School-funded learning is included for customer-school students; independent subscriptions serve learners at non-subscribing schools. Owner confirmed estimates should use one developer and no partner is confirmed, so school recruitment is planned.

Changes:

- Created `docs/plans/school-platform-implementation.md` before developing the material change, then completed 10 phases, 43 implementation tickets, dependencies, responsible roles, candidate records/files, acceptance tests, migration/cutover requirements, recruitment workstream and a first-ten-working-days backlog.
- Added `docs/school-platform-workflow-study.md`: further official competitor/LMS/assessment/boarding evidence, non-AI teaching workflows, service differentiation, native-versus-integrated LMS options, content operations, delivery costs and inspected code gaps.
- Preserved the previous roadmap as `docs/plans/legacy-study-assistant-roadmap.md` and replaced the active roadmap with SP0–SP9. Added NS01–NS18 and NQ01–NQ05 ahead of clearly labelled historical FR/NFR requirements.
- Updated architecture, proposed data model, verification/operations and decision register; recorded accepted D21–D23 separately from proposed technical/commercial/service decisions. Marked legacy AI/retrieval work deferred and documented planned provider containment without claiming it exists.
- Updated the documentation index, added current-direction notes to the three earlier reports, and annotated the legacy P3/P4/P5 plans. Existing code, database, dependencies, configured prices, private files and user work were preserved.

Affected areas: documentation only. Laravel/testing skills informed conventional scoped authorization, transaction/queue boundaries, additive migrations and behavior-focused verification. No new dependencies, schemas, permissions, AI-disable controls, payment integrations or deployment were implemented.

Verification:

- Read-only Boost application info and `composer show --direct` confirmed installed Laravel 13.32.0, Breeze 2.4.2, Livewire 4.4.5 and PHPUnit 12.5.35; application info reports PHP 8.4 and Tailwind 4.3.3. Composer completed with existing CLI deprecation notices; no dependencies changed.
- Inspected individual-user routes/roles, entitlement and quiz submission paths, account deletion, migrations/test conventions and current SQLite CI. Consulted installed-version framework documentation for scoped binding, transactions/locking and after-commit jobs.
- Boost schema inspection returned PostgreSQL with an empty table list. This is inconclusive live-schema evidence, explicitly recorded for SP0 read-only reconciliation; no assumption of missing/deleted production tables or migration was made.
- Local documentation validation passed across 23 Markdown files and 68 local links, with no missing targets or trailing whitespace. Checked 23 unique requirement IDs, 10 phase rows and 43 unique implementation ticket IDs.
- Independently recalculated 17 effort, contingency, content-count and economic examples; all matched. `git diff --check -- docs` passed. Reviewed status/legacy boundaries and external-source limitations.
- Application tests, asset build, formatting, provider sandbox and restore drills were not run because this turn changes documentation only. Prior alpha test counts are not new school-platform verification.

Limitations: desk research and bounded code inspection, not a full code/security audit or empirical school study. No school interviews, customer accounts, contracts, merchant transactions or real child onboarding occurred. KNEC's selected PDF was available only as indexed official material; KICD standards were identified but not fully audited; prior ODPC retrieval limits remain. One-developer estimates, provisional service targets and cost examples require validation; provider/partner/content waiting time is outside engineering-day totals.

Next steps: start SP0 baseline/AI containment and SP1 synthetic school isolation; owner starts REC-01 recruitment alongside development. Do not request strategic pivot approval again. Resolve partner-specific fee/report templates before those implementations and fulfill privacy, payment, support and content gates before their corresponding live releases.
