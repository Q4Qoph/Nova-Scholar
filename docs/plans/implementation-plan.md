# Implementation roadmap

Updated 23 September 2026. **Direction accepted: school administration plus e-learning; AI deferred.** The owner confirmed that individual learning subscriptions target students at non-subscribing schools and estimates should assume one developer, with school recruitment required. SP0 implementation has started with the AI boundary slice; SP1 access foundation, lifecycle web flows, active-school navigation, and overview policy are implemented and locally verified. SP0–SP9 school modules remain incomplete. The resumed school roadmap continues in staged slices alongside FI-08; native Filament finance actions and manual receipt/allocation browser acceptance are locally verified. Existing P0–P5 work remains legacy/internal-alpha functionality, not a school release.

The executable backlog, estimates, first ten working days, ownership and acceptance criteria are in the [school platform implementation plan](school-platform-implementation.md). The supporting [workflow study](../school-platform-workflow-study.md) explains the product and delivery choices. Read [current requirements](../requirements.md), [architecture](../architecture.md), [data model](../data-model.md) and [verification/operations](../verification-and-operations.md) before implementation.

## Active delivery sequence

| Phase | Deliverable | Dependencies | Status |
| --- | --- | --- | --- |
| SP0 | Establish test/environment baseline; contain AI in new school release; synthetic demo fixtures | None | In progress; AI boundary slice implemented |
| SP1 | Schools, memberships, scoped roles, invitations and audits | SP0 | In progress; persistence, context, lifecycle services, navigation, policy, and validated web flows implemented |
| SP2 | Learner/guardian registry, managed learner access, terms/classes and CSV import | SP1 | In progress; learner, guardian-link, academic year/term, class, subject, teaching-assignment, staged-import, dated-placement, restricted learner-access, promotion, transfer, and deactivation foundations implemented |
| SP3 | Attendance, guardian portal and notices | SP2 | In progress; SP3-01 through SP3-03 and the first in-app notices slice implemented and locally verified; external delivery/read state remain |
| SP4 | School fee subledger, statements, corrections and reconciliation | SP2 | In progress; SP4-01 preview-bound posting locally verified; SP4-02 manual receipt/allocation and native Filament actions tested and browser-verified with PostgreSQL race coverage; opening balances, corrections, statements, and reconciliation remain |
| SP5 | Teacher-authored lessons, assignments, submissions, objective practice and manual feedback | SP2 | Planned |
| SP6 | Moderated assessment records and published reports | SP3, SP5 | Planned |
| SP7 | Payment integration, school contracts and controlled day-school pilot readiness | SP3–SP6; dependent provider/readiness gates | Planned |
| SP8 | Boarding allocation, roll call and leave/release/return | Core plus boarding partner and operating readiness | Planned |
| SP9 | Independent learner catalogue/subscriptions and school-cover transition | SP5, SP7; content readiness | Planned; can precede SP8 if ready |

One-developer recommended order is SP0–SP7, followed by SP8/SP9 according to readiness. Recruitment, educator review and provider onboarding run alongside engineering as owner-led workstreams, not additional developer capacity. R1 day-school pilot needs SP0–SP7; R2 adds boarding; R3 adds independent Learn. All three belong to the intended initial commercial direction. AI is not a dependency for any of them.

Planning estimate: 84–125 developer-days for R1; 102–152 for all phases before contingency/external waits. With 25% contingency, approximately 21–32 and 26–38 fully staffed development weeks respectively. These are sizing assumptions, not delivery promises. Re-estimate after SP2. Real child data, live payments, boarding operations and paid catalogue access each require their own readiness evidence described in the detailed plan.

## Immediate work

Owner pause, 22 September 2026; roadmap resumed in staged slices by owner instruction, 23 September 2026. FI-01–FI-07 first slices are locally verified and permanent Filament routing is implemented. Native attendance, communications, overview, learner, academic and fee workflows use shared services and tenant-scoped policies. FI-06 preview-bound posting and PostgreSQL school-row concurrency protection are verified, including stale-preview rejection during concurrent admission; SP4-02 receipt/allocation is locally browser-checked. Synthetic Chrome has verified representative platform-admin, school-admin, teacher and guardian paths, CSV import/replay, academic setup, fee actions, attendance and notices, learner transfer between two schools, class-targeted notice delivery/replay, tenant switching/two-tab isolation, keyboard activation, and 390px plus 1024px role/device layouts. The focused role/access rerun passed (27 tests, 136 assertions), and Chrome confirmed bursar denials at 390px plus platform-admin, school-admin, teacher and bursar navigation/overflow at 1024×768. Wider role/device/task parity remains. The full current-worktree suite now passes against isolated Docker PostgreSQL (224 tests, 977 assertions); SQLite passes 222 tests with 962 assertions and 2 PostgreSQL-only skips. The local NQ03 mixed profile completed 270 requests with 2.33-second p95 and zero errors after 50-row tenant pagination; hosted capacity remains open. The latest observed hosted CI run failed on older SHA `4b0ecbf`; a fresh run on the reviewed worktree and hosted NQ03 capacity evidence remain required. Next: finish FI-08 parity and workflow review, obtain hosted CI and capacity evidence, then continue the next school-platform slice. SP3 alert/external delivery/read-state, and SP4 opening balances/reversals/statements/reconciliation remain open. Recruitment may continue independently.

## Previous work and scope changes

The [legacy P0–P9 roadmap](legacy-study-assistant-roadmap.md) preserves the original adult AI-assistant delivery plan. P1 authentication, P2 plans/usage, P3 files and P5 practice offer reusable foundations; user-owned access and AI generation must not be mistaken for classroom sharing. P4 and unfinished AI generation/retrieval work are deferred. No historical records, existing code, migrations or accepted subscription periods are deleted by the pivot.

Prior study prices are hypotheses; the existing Student/Pro prices remain historical catalogue records until a controlled billing migration. School fees and Nova subscriptions are separate accounting domains. The [decision register](../decisions.md) records the accepted direction and remaining design choices.

## Definition of done

Each task includes observable behavior, authorization/failure checks, applicable migration/rollout evidence, actual verification and documentation updates in the same change. External gates block the affected live release, not unrelated safe work. The [implementation log](../implementation-log.md) is authoritative for completed work; unrun tests and future functionality remain labelled planned/deferred.
