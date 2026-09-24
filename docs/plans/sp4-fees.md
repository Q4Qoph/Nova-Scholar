# SP4-01 fee schedules and charge posting

Status: in progress; SP4-01 schedule and preview-bound charge posting, including PostgreSQL roster-write concurrency protection, are locally verified. SP4-02 manual receipt and allocation workflow is implemented and locally verified. On 24 September the full local suites passed on SQLite (221 tests, 956 assertions, 2 PostgreSQL-only skips) and PostgreSQL (221 tests, 971 assertions). A focused 600-learner per-school benchmark recorded batch and single-writer wait durations; NQ03 mixed-load performance, opening balances and downstream ledger workflows remain planned/open.

## Scope

Add school-scoped fee schedules and a controlled charge-batch workflow. A schedule stores the currency and integer minor-unit amount that applies to a school, optional class, and optional term. Posting snapshots the schedule amount into one charge per eligible active enrolment and is idempotent by an explicit batch key. Posted charges are immutable through this slice.

Opening-balance import/sign-off, receipts, allocations, credits, refunds, statements, and reconciliation remain later SP4 slices.

## Dependencies

- SP1 school context, roles, and audit events.
- SP2 active schools, terms, classes, and enrolments.
- PostgreSQL-compatible additive migrations and the existing test factories.

## Acceptance criteria

- A school administrator can create a valid school-scoped fee schedule using integer minor units and an ISO currency code.
- A schedule may target all active enrolments, a class, or a term/class combination without accepting another school's records.
- A charge batch previews the eligible active enrolments and total before posting.
- Posting creates one immutable posted charge per eligible enrolment and records the schedule amount snapshot.
- Posting requires a saved preview snapshot that still matches the current schedule and eligible enrolments; a stale preview must be refreshed.
- Repeating the same batch key returns the existing batch without duplicate charges.
- Reusing a posted batch key for another schedule is rejected.
- Money is displayed in major currency units using the currency's minor-unit exponent while stored and calculated as integer minor units.
- A schedule change does not mutate already-posted charges.
- Teachers and users outside the school cannot create, preview, or post charges.

## Verification

- Focused feature tests for validation, class/term scope, preview, posting, idempotency, immutability, and authorization.
- Full PHPUnit suite, Pint, Vite build, Docker migration, and migration status.

## SP4-02 school receipts and allocations

Status: implemented and locally/browser verified for the manual-entry slice, 23 September 2026. Receipt recording and charge allocation use shared school services through both the canonical workflow and native tenant Filament actions. Focused/full SQLite and isolated PostgreSQL suites passed, as did a PostgreSQL allocation race test and synthetic Chrome receipt/partial-allocation journey. Live provider verification, opening balances, reversal/refund flows, statements, reconciliation and NQ03 mixed-load performance remain open; FI-06 fee preview/post versus roster-write concurrency is verified.

Scope: record confirmed school receipts with their source/evidence and allocate them against same-school posted charges. A receipt may cover multiple learners; an allocation may be partial; total allocations must never exceed receipt funds. Keep school tuition isolated from Nova subscription/payment records. This slice does not initiate or verify live M-Pesa/bank payments, import opening balances, post refunds/credits, or publish statements.

Dependencies: SP4-01 posted charges and fee schedules; school-scoped authorization; additive PostgreSQL-compatible schema; local PostgreSQL verification.

Acceptance: school-authorized staff can record a receipt with currency, integer minor-unit amount, received date, source type/reference and verification actor/time; duplicate references within the defined school/source scope are rejected; allocations reference only posted charges in the same school and currency; partial and sibling allocations work; available receipt balance is shown separately from fee receivables; over-allocation and cross-school allocation fail transactionally; replayed requests do not duplicate receipt/allocation entries; records are auditable and append-only through this slice.

Verification: focused feature tests for source/reference validation, authorization, partial/sibling allocation, duplicate/replay, cross-school/currency denial and over-allocation; full suite, Pint and migration checks; PostgreSQL transaction/locking checks for concurrent allocation. A PostgreSQL-only process race test and full PostgreSQL CI suite pass. Browser acceptance must cover receipt entry, partial/sibling allocation and remaining balances. Do not claim production receipt verification or cutover from synthetic/local evidence.

Implementation defaults for this slice: retain the existing school-admin-only finance policy; support cash, bank, and M-Pesa as manually confirmed sources; generate an internal reference for cash and require a source reference for bank/M-Pesa; store a short evidence note/reference only, with no private proof-file upload; enforce reference uniqueness per school/source; defer reversal behavior to SP4-03. These defaults do not represent live provider verification. Revisit only if a pilot school supplies a concrete conflicting workflow.

## Unresolved decisions

- Opening-balance source evidence and school sign-off roles.
- Residence/boarding charge dimensions and mixed day/boarding rules.
- Period locking and amendment/reversal workflow.
