# School management code case studies

Date: 24 September 2026. Status: repository-by-repository static study complete.

## Study method and Nova Scholar baseline

The reviews below use the shallow clones and pinned commits in [`casestudies-examples/README.md`](../casestudies-examples/README.md). I inspected manifests, routes, selected models, services, Filament/Blade screens, migrations, and tests. I did not install dependencies or run reference code, migrations, seeders, or tests. These findings describe the checked-out source at those commits; they are not runtime verification.

Nova Scholar already has Laravel 13, Livewire 4, Filament 5, school context and memberships, school-scoped services and policies, learners and verified guardian links, academic years/terms/classes/subjects/teaching assignments, attendance, a first fee schedule/charge/receipt/allocation workflow, and private individual quizzes. School courses, assignments/submissions, formal school assessments, moderation, and published report versions remain planned SP5–SP6 work. The current project architecture and roadmap remain authoritative.

## 1. App-School-Management

**Snapshot:** `muhamadfikrii/App-School-Management`, commit `99b8001ea0ea3943b3bb52dc6f09cffbcd965040` from 10 December 2025. The manifest specifies Laravel 12, PHP 8.2+, and Filament 4; the README describes Livewire 3 and a single-school MySQL/MariaDB installation.

### What the code does

- Organizes Filament resources into separate schemas, tables, and resource pages. Student, class, grade-component, grade, final-grade, and report screens are easy to trace by domain.
- Stores raw grade entries with learner, subject, class, academic year, teacher, semester, grade component, and score. A component carries a weight.
- Calculates a subject result by averaging entries per component and adding weighted component averages. The report form then displays a grade band and pass status and persists subject-level report details under a final-grade record.
- Provides a useful interaction pattern: choose a class and learner, select subjects, then show calculated subject results in a repeated report-card form. The report resource also supports search and a PDF export route.

### Findings and limits

- The grade/report form performs calculations in Filament form callbacks. Nova should use form callbacks for feedback only; a domain service should recalculate and validate all official totals when records are reviewed or published.
- Several form choices query all academic years, classes, students, or subjects directly. That is suitable only to its single-school assumption and is not a tenant boundary.
- `Grade`, `FinalGrade`, and `GradesDetail` use open mass assignment. Report records can be edited or deleted; there is no moderation state, frozen publication version, amendment history, or clear missing/exempt/not-assessed distinction.
- The report average uses `array_filter` on scores, which drops a numeric zero from the average. The report path calculates to two decimal places, then stores a rounded integer score. Nova should define its own explicit rounding rule. These are static observations, not test results.
- The grading labels and KKM threshold reflect this project's Indonesian context. They are not a Kenyan CBC/CBE grading policy.
- The checked-out test suite contains only example tests, so it does not provide evidence for the grade, report, authorization, or tenant behavior.
- The README and `composer.json` claim MIT, but this checkout has no license text file. Confirm upstream license terms before copying any source.

### Nova Scholar use

Study the Filament resource composition and the separation between component marks and a report's per-subject display. Rebuild the workflow around Nova's existing `TeachingAssignment`, class membership, services, Form Requests, policies, and Filament 5 APIs. Keep raw assessment records separate from frozen published report versions, and compute totals server-side from an educator-approved template.

Inspected files: `app-school-management/app/Filament/Resources/Grades/Schemas/GradeForm.php`, `app-school-management/app/Filament/Resources/Reports/Schemas/ReportForm.php`, `app-school-management/app/Models/Subject.php`, `app-school-management/app/Models/FinalGrade.php`, `app-school-management/database/migrations/2025_09_11_162617_create_grades_table.php`, and `app-school-management/database/migrations/2025_09_13_101843_create_report_cards_detail_table.php`.

## 2. Skuul

**Snapshot:** `yungifez/skuul`, commit `8de86bac3f03637f48343c848ac6eea001951f99` from 20 August 2026. Its checked-out manifest specifies Laravel 13, PHP 8.3+, Livewire 4, and Spatie Permission 8. Its README is stale and still describes Laravel 9. An MIT `LICENSE` file is present.

### What the code does

- Groups authenticated routes behind verified-account, active-account, and active-school middleware. `SchoolContext` resolves a remembered school only if the user still has access, falls back to an active membership, and sets Spatie's team ID for per-school roles and permissions.
- Uses an explicit `InSchool` query scope rather than silently scoping every model. School-owned models and services must therefore apply the scope or validate relationships at their boundary.
- Separates controller workflows from domain services for fees and examinations. Fee invoices are created in a transaction; fee records use Brick Money and check school ownership. Exam-record batches use a transaction, update-or-create records, and check that teachers are assigned to the subject.
- Includes feature coverage for active-school switching, expired membership, per-school permission state, denied cross-school access, exam-record authorization, fee authorization, and school isolation across common record actions.
- Models an exam's result-publication state as a mutable boolean. Its service prevents publishing an exam with no slots, but this is not a frozen, moderated, amendable report lifecycle.

### Findings and limits

- The explicit tenant scope is easy to understand, but every query still has to remember to apply it. Nova's existing school services and policy checks should remain the enforcement boundary rather than adding a second scope convention by default.
- In `FeeInvoiceService`, the student-school check compares `current_school_id()` with itself. This condition is always false, so the apparent cross-school student validation is not effective. Treat this as a static defect in the reference, not as a pattern to borrow.
- Its fee service updates paid totals on the invoice record; it does not provide Nova's immutable receipt/allocation ledger semantics.
- Its exam publication flag does not prove marks are complete, reviewed, frozen, or safe for guardian release. The useful comparison is the exam-entry and tabulation workflow, not its publication model.
- The app uses Blade/controller screens and Spatie team-scoped permissions rather than Nova's Filament 5 pages and current custom school-role services. Do not add Spatie or replace Nova's permission model based on this reference.

### Nova Scholar use

Use its active-school context and boundary tests as a checklist when adding new school resources: reject stale memberships, isolate lists and records, scope nested relations, and test unauthorized reads and writes. Its exam and fee modules can inform user journeys. Implement them through Nova's current service and authorization conventions, including PostgreSQL concurrency checks and immutable finance records where applicable.

Inspected files: `skuul/app/Services/School/SchoolContext.php`, `skuul/app/Traits/InSchool.php`, `skuul/app/Services/Exam/ExamRecordService.php`, `skuul/app/Services/Exam/ExamService.php`, `skuul/app/Services/Fee/FeeInvoiceService.php`, `skuul/app/Services/Fee/FeeInvoiceRecordService.php`, `skuul/tests/Feature/SchoolContextTest.php`, `skuul/tests/Feature/CrossSchoolAccessTest.php`, `skuul/tests/Feature/ExamRecordTest.php`, and `skuul/tests/Feature/FeeInvoiceRecordTest.php`.

## 3. LAVSMS

**Snapshot:** `4jean/lav_sms`, commit `10a2e962686da88bd7bc20135c75032367cc0750` from 20 September 2022. The manifest allows Laravel 8 and PHP 7.2+. An MIT `LICENSE` file is present.

### What the code does

- Uses separate role-specific route groups, controllers, and Blade screens for administrators, support staff, teachers, students, and parents. The fee screens cover fee setup, per-learner payment records, receipt display/PDF output, and a payment-management view. Mark screens cover batch entry, mark display, tabulation, comments/skills, and print views.
- Uses repositories as wrappers around Eloquent. The support-team payment controller validates an amount, updates the learner's running paid/balance fields, then creates a receipt row. The marks controller sets up one mark record per learner and updates a class/subject batch.
- Contains an explicit parent result check in the marks display path, alongside role middleware on route groups. This demonstrates the practical parent-facing result journey and printable report layout.

### Findings and limits

- Payment totals and receipt rows are written in separate operations without a transaction in the inspected controller. Its reset action zeroes running totals and deletes receipt rows. This does not preserve an auditable correction history and conflicts with Nova's current receipt/allocation model.
- Repository methods include broad `all()` and unscoped `find()` calls. Role middleware and single-school assumptions do not make these data-access patterns suitable for Nova's multi-school boundary.
- The code uses older controller validation, implicit mutable totals, and legacy Laravel conventions. The repository is useful for screen and role-flow discovery only.
- The code study did not find evidence of immutable report publication, teacher moderation, recipient snapshots, or versioned amendments.

### Nova Scholar use

Use its role-oriented journeys to check that teacher, bursar, learner, and guardian tasks are understandable, especially report display and receipts. Keep Nova's services, active-school authorization, audit history, manual receipt evidence, and verified guardian links in place. Avoid copying the repository layer and mutable balance flow.

Inspected files: `lav_sms/routes/web.php`, `lav_sms/app/Http/Controllers/SupportTeam/PaymentController.php`, `lav_sms/app/Repositories/PaymentRepo.php`, `lav_sms/app/Http/Controllers/SupportTeam/MarkController.php`, `lav_sms/app/Models/PaymentRecord.php`, `lav_sms/app/Models/Receipt.php`, and `lav_sms/resources/views/pages/support_team/marks/print/index.blade.php`.

## Login, pages, navigation, and data flow

This section traces the user-visible route into an operation and the records it reads or writes. It is source inspection, not a claim that the reference application was run successfully.

### App-School-Management: Filament assessment and report flow

- **Login and landing page:** Filament generates the login and password-reset pages under the `admin` panel at `/admin`. `User::canAccessPanel()` admits administrators and teachers to the same panel. Resources then provide their own visibility/access checks; the panel does not route each role to a separate workspace.
- **Navigation:** the panel discovers Filament resources and pages. Resource groups/labels form the menu, while each resource's `canAccess()` determines whether it is available to the current role. The grade and report screens are the relevant teacher/admin path.
- **Operation and records:** a user opens the Grades create page and selects year, class, learner, subject, teacher, grade component, and score. A `Grade` row stores the score. `Subject::calculate()` aggregates component scores and weights. The user then opens the Reports create page, chooses the learner and period, and adds subject rows through a relationship repeater. The report form calls the calculation from the UI callback and saves the report plus related detail rows. The report resource can render a PDF through the final-grade export controller.
- **Flow:** `Filament login → Grades form → Grade rows → Subject calculation → Reports form → FinalGrade + GradesDetail rows → PDF view`.
- **What to learn:** keep the grouped Filament page composition and the separation between assessment entries and report display rows. Nova must calculate official totals in a server-side service when saving, reviewing, and publishing; a form callback is only a preview. The inspected export route shows `auth` middleware, so a Nova guardian report endpoint must also check the exact guardian-to-learner link and the report's released state.
- **Static auth defect:** `AdminPanelProvider` returns the panel configuration before an email-verification block that references `$form`; that block cannot run, and `$form` is not defined in the visible method. Treat it as unreachable/dead configuration in this snapshot, not as runtime verification of email verification behavior.

Source: [`AdminPanelProvider`](../casestudies-examples/app-school-management/app/Providers/Filament/AdminPanelProvider.php), [`User`](../casestudies-examples/app-school-management/app/Models/User.php), [`GradeForm`](../casestudies-examples/app-school-management/app/Filament/Resources/Grades/Schemas/GradeForm.php), [`ReportForm`](../casestudies-examples/app-school-management/app/Filament/Resources/Reports/Schemas/ReportForm.php), [`Subject`](../casestudies-examples/app-school-management/app/Models/Subject.php), and [`ExportFinalGradeController`](../casestudies-examples/app-school-management/app/Http/Controllers/ExportFinalGradeController.php).

### Skuul: sign-in, active school, and exam entry

- **Login and landing page:** `/login` renders a Blade shell with a Livewire email/password/remember-me form. The login text says school administrators invite users. Fortify redirects to `/dashboard`; the web middleware resolves the active school for signed-in requests. School routes also require authentication, verified email, an active account, and the required school context. Accounts without an eligible membership cannot proceed into school data; eligible users with no selected school are directed to choose one.
- **Navigation:** the authenticated app shell displays the current school selector, page heading, breadcrumbs, and a Livewire-built sidebar. The menu groups administration, academic, fee, and exam pages and checks permission strings before showing entries. This is presentation behavior; route middleware, policies, Form Requests, and services remain the operation boundary.
- **Operation and records:** the teacher opens the exam-record page and submits exam, section, subject, learner, and mark data. `ExamRecordController` applies resource authorization and delegates the write. The request validates identifiers and non-negative integer marks. `ExamRecordService` checks the teacher's subject assignment, compares each score with its exam-slot maximum, and uses a transaction with `updateOrCreate()` for the submitted records. The exam record page/table then reads those records.
- **Flow:** `Invitation → login → active-school resolution → permission-filtered menu → exam-record page → request validation + resource authorization → service assignment/maximum checks → transactional ExamRecord writes → list/table`.
- **What to learn:** this is the strongest reference for the active-school sign-in journey and action-level isolation tests. Nova already has its own tenant-aware Filament school panel and role services, so use Skuul's stale-membership and cross-school denial cases as test scenarios; do not add its Spatie team-permission system or make every Nova model depend on its `InSchool` trait.
- **Static limitation:** Skuul's exam publication flag is a mutable switch, not a reviewed/frozen report publication lifecycle. Its fee code also contains a broken student-school comparison described above. These are not patterns to carry over.

Source: [`login view`](../casestudies-examples/skuul/resources/views/auth/login.blade.php), [`login form`](../casestudies-examples/skuul/resources/views/livewire/auth/login-form.blade.php), [`web routes`](../casestudies-examples/skuul/routes/web.php), [`SchoolContext`](../casestudies-examples/skuul/app/Services/School/SchoolContext.php), [`SetActiveSchool`](../casestudies-examples/skuul/app/Http/Middleware/SetActiveSchool.php), [`Menu`](../casestudies-examples/skuul/app/Livewire/Layouts/Menu.php), [`menu view`](../casestudies-examples/skuul/resources/views/livewire/layouts/menu.blade.php), [`ExamRecordController`](../casestudies-examples/skuul/app/Http/Controllers/ExamRecordController.php), [`StoreExamRecordRequest`](../casestudies-examples/skuul/app/Http/Requests/StoreExamRecordRequest.php), and [`ExamRecordService`](../casestudies-examples/skuul/app/Services/Exam/ExamRecordService.php).

### LAVSMS: role menu, marks, parent view, and fee receipt

- **Login and landing page:** `Auth::routes()` supplies the login page. The custom login controller accepts the legacy email-or-username identifier and redirects every role to `/home`. `/`, `/home`, and `/dashboard` all render the same support-team dashboard view; role-specific differences mainly appear in the menu and route middleware rather than a distinct landing page.
- **Navigation and access:** the shared shell renders role-dependent menu entries. `teamSA`, `teamSAT`, `teamAccount`, `my_parent`, and student middleware gate route groups. The parent child list filters by the authenticated parent's ID, and the marks display checks whether the viewer is the learner, an authorized staff role, or the linked parent before returning a result. The custom middleware and global repositories still assume one school.
- **Marks operation and records:** a teacher/admin selects exam, class, section, and subject. The selector creates a mark and exam-record row per learner, then opens a batch page. The update action stores component marks, derives total and grade, recalculates positions/aggregates, and updates the rows. A learner or linked parent can view the result; staff also have tabulation and print screens.
- **Fee operation and records:** an accountant selects a class; the controller combines class-specific and general fee definitions, then creates one `PaymentRecord` per learner/fee. `pay_now` updates running paid/balance fields and creates a separate `Receipt` row; the receipt page and PDF load the payment, learner, and receipt rows. The reset action zeros the running totals and deletes receipts.
- **Flow:** `role login → shared dashboard → role menu → select exam/class/subject → batch mark rows → calculated totals/tabulation → learner/linked-parent result`; for fees, `accountant menu → class + fee setup → PaymentRecord rows → running balance update + Receipt row → receipt HTML/PDF`.
- **What to learn:** LAVSMS is the clearest source for checking whether a role can finish a real task across pages, especially teacher batch marks, parent report access, and cashier receipt screens. It is useful for page order and labels only. It does not provide a safe multi-school data boundary or auditable financial history.

Source: [`web routes`](../casestudies-examples/lav_sms/routes/web.php), [`LoginController`](../casestudies-examples/lav_sms/app/Http/Controllers/Auth/LoginController.php), [`HomeController`](../casestudies-examples/lav_sms/app/Http/Controllers/HomeController.php), [`role menu`](../casestudies-examples/lav_sms/resources/views/partials/menu.blade.php), [`parent children`](../casestudies-examples/lav_sms/app/Http/Controllers/MyParent/MyController.php), [`MarkController`](../casestudies-examples/lav_sms/app/Http/Controllers/SupportTeam/MarkController.php), and [`PaymentController`](../casestudies-examples/lav_sms/app/Http/Controllers/SupportTeam/PaymentController.php).

## Which reference helps with which problem?

| Nova Scholar problem to solve | Best study source | Use it for | Do not adopt |
| --- | --- | --- | --- |
| Make teacher marks and report entry understandable as a sequence of pages | App-School-Management, checked against LAVSMS | Filament form grouping, class/learner/subject selection, subject detail rows, result and print destination | Indonesian KKM/grade rules, client-side official calculations, unscoped select options, or the unverified license as permission to copy code |
| Keep a request inside the selected school and test unauthorized actions | Skuul | Sign-in-to-active-school flow, stale-membership/switch cases, cross-school negative tests, service transaction boundary | A second permission system or reliance on menu visibility/implicit global model scoping |
| Ensure each role can complete a school task and find the next page | LAVSMS | Teacher batch-mark steps, parent result path, bursar payment/receipt screens and role menu review | Its common support-team landing page, legacy role middleware as Nova's design, or global repositories |
| Produce reports guardians may safely trust | None is sufficient | Borrow screen sequence only, then design the release states and guardian path from Nova requirements | No reference demonstrates moderation plus immutable published versions, amendment history, and verified-guardian release together |
| Preserve reliable fee history | None is sufficient | Compare invoice and receipt screens while continuing Nova's existing receipt/allocation ledger | Mutable totals plus separately written or deletable receipts |

Nova already has Breeze web-auth routes and permanent [Platform](../app/Providers/Filament/PlatformPanelProvider.php) and [School](../app/Providers/Filament/SchoolPanelProvider.php) Filament workspaces, with the school panel tenant-bound to `School`; its guardian and managed-learner experiences are separate ([auth routes](../routes/auth.php), [architecture](architecture.md)). A current school operation such as [creating a teaching assignment](../app/Services/Schools/CreateTeachingAssignment.php) checks that the class, subject, and active teacher membership all belong to the same school before writing. The implementation should preserve these destinations and existing policies. Use the repository studies to refine what each page lets a role do, not to replace Nova's current login, tenancy, role, or navigation architecture.

## Cross-repository conclusion

| Area | What the references help us study | Nova Scholar direction |
| --- | --- | --- |
| School context | Skuul's session-based selection and negative tenant tests | Keep Nova's existing context, policies, and shared services; add cross-school denial coverage for every new resource and nested relation |
| Teacher assessment UI | App-School-Management's Filament grade forms; Skuul/LAVSMS exam entry and tabulation journeys | Build on Nova's Filament 5 school pages and teaching assignments; validate and calculate through services, never trust client-computed official totals |
| Reports | App-School-Management's subject rows; Skuul/LAVSMS result/tabulation/print screens | Define one educator-reviewed pilot template, a review and publish workflow, immutable published snapshots, guardian authorization, and audited amendments |
| Fees | Skuul invoice setup and LAVSMS payment/receipt screens | Continue Nova's existing fee schedule, charge, receipt, and allocation services; finish reversals, statements, and reconciliation without mutable/deletable receipt history |
| Login and navigation | Skuul's active-school shell; LAVSMS role task paths; App's Filament resource navigation | Keep Nova's Platform/School panel separation, tenant-aware school panel, Gate-filtered navigation, and separate guardian/managed-learner destinations |
| Access and testing | Skuul's action-level access tests and LAVSMS role journeys | Keep Nova's Form Requests, policies, tenant-aware services, feature tests, and real PostgreSQL race coverage |

The next implementation planning artifact is the [case-study-informed school implementation plan](plans/case-study-informed-implementation.md). The existing SP roadmap remains the source for release order and requirements.
