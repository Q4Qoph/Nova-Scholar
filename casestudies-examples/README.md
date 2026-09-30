# School management case study repositories

The owner approved App-School-Management, Skuul, and LAVSMS for local study. All three shallow clones are present below. Repository folders are ignored by the Nova Scholar Git repository; this index remains trackable.

The purpose is to compare workflows, UI, and implementation approaches with Nova Scholar. Do not run a reference app or its install scripts in this workspace. Do not copy code until the source's actual license and attribution terms have been checked. Nova Scholar's existing tenant boundaries, services, policies, and Laravel 13 / Filament 5 architecture remain authoritative.

See the [repository-by-repository findings](../docs/case-study-code-findings.md) for the login, role navigation, page, operation, and data-flow traces, and the [proposed Nova Scholar implementation plan](../docs/plans/case-study-informed-implementation.md) for the actionable role journeys and delivery order.

See the [30 September page-flow and screenshot report](../docs/case-study-page-flow-report.md) for the current Nova comparison, publisher image evidence and 16 synthetic local captures. Reference apps remain unexecuted; recommendations are proposed.

## Candidate comparison

| Candidate | Match to Nova Scholar | Relevance | Caveat | Recommendation |
| --- | --- | --- | --- | --- |
| [App-School-Management](https://github.com/muhamadfikrii/App-School-Management) (`app-samlosier` in README) | Manifest: Laravel 12, Filament 4, PHP 8.2+; README says Livewire 3 and MySQL/MariaDB | Filament resource/form patterns and a grade-to-report-card data shape | Single-school/basic roles. README and Composer metadata claim MIT, but no license text file exists in this checkout. Confirm the upstream terms before reusing any code. | UI and assessment reference |
| [yungifez/skuul](https://github.com/yungifez/skuul) | Checked-out manifest: Laravel 13, PHP 8.3+, Livewire 4; multi-school | Active school context, per-school roles/permissions, and cross-school access coverage | README is stale and still says Laravel 9. Uses Spatie team-scoped roles; compare its boundaries with Nova's existing authorization before considering patterns. | Strongest tenancy/security reference |
| [4jean/lav_sms](https://github.com/4jean/lav_sms) | Manifest: Laravel 8, PHP 7.2+ | Role-specific workflows for fees/receipts, marks/grades, exams, parents, teachers, library, and dorms | Legacy implementation, last commit in 2022. MIT license file is present. Its global repository methods are not a safe tenancy pattern for Nova. | Domain workflow reference only |
| [Dantechdevs/CBC-school](https://github.com/Dantechdevs/CBC-school) | Claimed Kenyan CBC workflows and Laravel 12 in an indexed setup guide | Potential CBC assessment/report and local workflow comparison | Repository URL currently returns 404, so source, implementation, and license cannot be verified. | Do not clone until a working URL is supplied |
| [Nahyomee/School-Management-Schema](https://github.com/Nahyomee/School-Management-Schema) | Laravel migrations, simple multi-school `school_id` schema, ERD | Lightweight schema comparison for academic tables | Only two commits; README's license passage refers to Laravel's license, not clearly the repository's own. Nova already has richer scoped models and lifecycle history. | Skip clone unless license is clarified |
| [yordanos-bogale5/School-Mgt-System](https://github.com/yordanos-bogale5/School-Mgt-System) | Laravel 10, Filament, Filament Shield | Small teacher/subject/grade workflow and panel UX | Two commits; README contains Laravel's boilerplate license statement rather than a clear project license. | Skip clone unless license is clarified |

## Further candidates found

- [wamwagii/smsv2](https://github.com/wamwagii/smsv2) is described as a Kenyan Laravel 13 / Filament 5.6 app using SQLite and PostgreSQL, with student/staff, fee, payment, receipt, and result workflows. This is the closest reported stack and local-domain match, but the repository and its license were not independently verified during this review.
- [academico-sis/academico](https://github.com/academico-sis/academico) reports Laravel 12 and Filament 5 with enrolments, courses, scheduling, and reports. Its maintainers label the Filament rewrite work in progress; no clear project license was visible in the repository page reviewed.

## Owner-approved clones

| Local directory | Source / checked-out commit | License evidence | Study focus |
| --- | --- | --- | --- |
| [`app-school-management/`](app-school-management/) | [muhamadfikrii/App-School-Management](https://github.com/muhamadfikrii/App-School-Management) · `99b8001ea0ea3943b3bb52dc6f09cffbcd965040` (2025-12-10) | README and `composer.json` claim MIT; no license text file in clone | Filament forms/resources; grades, final grades, report cards |
| [`skuul/`](skuul/) | [yungifez/skuul](https://github.com/yungifez/skuul) · `8de86bac3f03637f48343c848ac6eea001951f99` (2026-08-20) | MIT `LICENSE` present | School context and tenant-boundary tests |
| [`lav_sms/`](lav_sms/) | [4jean/lav_sms](https://github.com/4jean/lav_sms) · `10a2e962686da88bd7bc20135c75032367cc0750` (2022-09-20) | MIT `LICENSE` present | Legacy parent/teacher/finance/marks workflows |

## First-pass code findings

- **Assessment UI and records:** App-School-Management has separate grade and final-grade records, grade components, and a final-grade detail relationship. Its Filament grade form connects a learner, class, subject, teacher, academic year, component, and score. This is useful as a workflow/data-shape prompt for Nova's planned SP5–SP6 assessment and reports, but its unscoped option queries and open mass assignment (`$guarded = []`) need Nova's tenant-scoped service and validation boundaries around any equivalent behavior. See `app-school-management/app/Filament/Resources/Grades/Schemas/GradeForm.php` and `app-school-management/app/Models/FinalGrade.php`.
- **Tenant context and isolation:** Skuul's `SchoolContext` resolves a remembered school from the session, checks current access, falls back to a default membership, and sets Spatie's team ID. Its feature tests cover school switching, expired membership, per-school roles/permissions, scoped academic-year queries, and cross-school read/edit/update/delete paths. This is the most useful reference for reviewing Nova's active-school and tenant-boundary behavior; keep Nova's existing roles, policies, and shared services authoritative. See `skuul/app/Services/School/SchoolContext.php`, `skuul/tests/Feature/SchoolContextTest.php`, and `skuul/tests/Feature/CrossSchoolAccessTest.php`.
- **Operational workflows:** LAVSMS provides role-separated controller/view flows and repositories for payment records, receipts, marks, and grades. Its `PaymentRepo` uses broad `all()`/`find()` operations and does not show a school scope, so study the sequence and screens while avoiding its data-access pattern. See `lav_sms/app/Repositories/PaymentRepo.php` and `lav_sms/app/Http/Controllers/SupportTeam/PaymentController.php`.

All findings are static inspection only. No dependencies were installed, and no reference app, migrations, seeders, tests, or scripts were run. The clones are reference-only; source integration requires a feature-specific review and license/attribution check.
