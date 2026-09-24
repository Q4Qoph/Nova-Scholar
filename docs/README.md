# Nova Scholar documentation

Nova Scholar is being redirected to school administration and e-learning for Kenyan day, boarding and mixed schools. School contracts include student learning access; students at non-subscribing schools can purchase independent learning. The owner accepted this direction on 21 September 2026 and deprioritized AI. SP1 now has an implemented school access foundation, protected overview, invitation/role flows, and active-school navigation; SP2 now has learner registry, verified guardian relationships, academic years/terms, classes, subjects, teaching assignments, staged imports, dated class placements, managed learner access, promotion, transfer, and deactivation controls; SP3-01 through SP3-03 and the first SP3-04 in-app notices slice are implemented and locally verified; external delivery remains open.

The documentation began on 16 September 2026 from the owner's July BRD, SRS and SAD. Current requirements and the SP0–SP9 plan supersede the original adult AI-assistant release scope; historical FR/NFR identifiers and P0–P9 work are retained for traceability. The spelling **Nova Scholar** is used consistently. A plan is not evidence that a feature exists.

## Reading order

1. [Requirements and scope](requirements.md): consolidated source requirements, release boundaries, and traceability.
2. [Active roadmap](plans/implementation-plan.md) and [actionable school implementation plan](plans/school-platform-implementation.md): tasks, dependencies, estimates, first ten working days and release gates.
3. [Architecture](architecture.md): application boundaries, security, and integration flows.
4. [Data model](data-model.md): proposed entities, relationships, and invariants.
5. [Verification and operations](verification-and-operations.md): acceptance checks, deployment, monitoring, and recovery.
6. [Decision register](decisions.md): assumptions and choices requiring resolution.
7. [Implementation log](implementation-log.md): what actually changed and what was verified.

## Current study and delivery direction

As of 24 September 2026, the owner has authorized staged completion of the Filament integration alongside resumption of the school roadmap. The [Filament integration plan](plans/filament-integration.md) is active. Platform admins and eligible school staff now enter permanent Filament workspaces; guardian, managed-learner and personal-study experiences remain separate. Native attendance, communications, overview, learner registry/detail, academics and fees use tenant-scoped authorization and shared services. Synthetic Chrome has verified representative admin, teacher and guardian journeys, academic setup, CSV import/replay, learner promotion/deactivation, learner transfer to a provisioned second school, class-targeted notice delivery/replay, finance actions, keyboard activation and 390px layouts against isolated Docker PostgreSQL. A focused post-fix role/access suite passed (27 tests, 136 assertions); Chrome verified bursar denials at 390px, the four-role 1024×768 matrix, and class-targeted notice send/replay to a linked guardian. The latest full suites pass: PostgreSQL (224 tests, 977 assertions) and SQLite (222 tests, 962 assertions; 2 PostgreSQL-only skips). Corrective hosted CI run `36009434351` passed both SQLite and PostgreSQL jobs on `93229c4`, including the frontend build; hosted capacity remains unverified. The local NQ03 profile completed 270 requests across three synthetic schools with zero errors and 2.33-second combined p95 after pagination. Broader browser/role/device parity and release readiness remain open. SP4-02 receipt/allocation work and FI-06 posting concurrency have local verification; opening balances, reversals/refunds, statements and reconciliation remain school-roadmap work. Filament 5.8.4 is installed; full FI-08 acceptance is not claimed.

[School platform workflow and delivery study](school-platform-workflow-study.md) develops the accepted direction into non-AI teaching workflows, administration/service benchmarks, content operations, build-versus-integrate choices and updated cost examples. The [implementation plan](plans/school-platform-implementation.md) uses the owner-confirmed one-developer baseline and includes recruitment because no school partner is confirmed. SP0 includes the implemented AI boundary, a configured PostgreSQL CI lane, and a schema-neutral synthetic demo fixture; SP1 now has school persistence, protected context, invitation/role services, validated web flows, active-school navigation, and an explicit overview policy. SP2 now has learner profiles/enrolments, verified guardian links with restricted portal access, school-scoped academic years/terms, classes, subjects, teaching assignments, staged learner imports, dated class placements, and restricted managed learner access. SP3-01 through SP3-03 now provide scoped registers, reasoned corrections, and linked-child attendance views without sibling leakage; the first SP3-04 slice now provides school-admin in-app notices with guardian targeting; SP4-01 now provides preview-bound school/class/term fee schedules and idempotent charge posting with currency-aware display; SP4-02 now provides manually confirmed receipt recording and same-school partial allocations. Confirmed alerts, external delivery, retries, read state, opening balances, refunds/reversals, statements, and reconciliation remain planned.

AI features are deferred. The first school release does not depend on tutoring, generation, embeddings or automated AI marking. Existing alpha code/data are preserved; SP0 now contains provider dispatch for school and managed-learner job contexts by default, while the school modules that will supply those contexts remain planned. New school/Learn prices, providers and live-data readiness remain unresolved.

## Earlier research — dated context

The three studies below record the reasoning before the owner selected the school direction. Their statements that a pivot is unapproved or that schools are deferred are historical, superseded by D21; their unvalidated price and market hypotheses remain unvalidated.

[School management with connected e-learning](school-management-learning-strategy-study.md) (21 September 2026) compares the business models, day/boarding workflows, access/payment boundaries, competitors and economics. The owner subsequently accepted the direction; packages/prices remain proposed. See the [study plan](plans/school-management-learning-study.md).

[School, parent and educator market comparison](school-parent-educator-market-research.md) extends the original study to CBC/CBE grade bands, parent-funded learning, educator tools and marketplace options. Its consumer-versus-tertiary comparison is historical; curriculum/content and child-service findings remain useful for the selected school direction.

[Competitive landscape and Kenya market research](competitive-market-research.md) (17 September 2026) compares study software and Kenya's adult university/TVET market. Its campus launch recommendation is historical. See the [research plan](plans/competitive-market-research.md) for scope and verification.

## Repository baseline

The project began as a Laravel skeleton: Composer requires Laravel `^13.17` and PHP `^8.3`; the recorded local CLI baseline is PHP 8.4.24. P1 adds Breeze Blade authentication, Livewire, Tailwind CSS, verified account/profile flows, and a Nova Scholar dashboard. P2 adds plans, entitlements, usage accounting, seeded individual prices, and a read-only subscription page. P3 document ingestion remains in progress. P4 tutoring and P5 quizzes/flashcards have internal-alpha implementations, Groq development integration, and provisional generation allowances; production and quality gates remain open. Payments are not implemented. See the implementation log for actual verification and limitations. `.env.example` defaults to SQLite and database-backed queues/cache, while `.env.docker.example` supports the local PostgreSQL/Redis/Mailpit/MinIO stack.

Laravel Boost was installed as required by the original repository instructions. Its generated agent configuration and skills are development tooling, not product functionality.

Installed versions inspected on 21 September 2026: Laravel 13.32.0, PHP 8.4, Breeze 2.4.2, Livewire 4.4.5, Tailwind 4.3.3 and PHPUnit 12.5.35. No upgrades were made. The [legacy roadmap](plans/legacy-study-assistant-roadmap.md) preserves P0–P9; old completion evidence is not proof that school features exist.

## Documentation workflow

For each phase, create a focused plan under `plans/` before implementation. Include requirement IDs, scope, decisions, schema/UI/service changes, dependencies, acceptance tests, rollout concerns, and status. Update the roadmap and related documents as work progresses; record actual outcomes in the implementation log. Significant changes to accepted decisions need a dated rationale in the decision register.

Suggested statuses: proposed → ready → in progress → implemented → verified. Use blocked or deferred with a reason where appropriate. A phase is verified only when its acceptance evidence is recorded. The implementation log is authoritative for completed work; the roadmap describes intended work.
