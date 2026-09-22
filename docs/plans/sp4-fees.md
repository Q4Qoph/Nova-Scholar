# SP4-01 fee schedules and charge posting

Status: in progress; the schedule and charge-batch slice is implemented and locally verified. Opening balances and downstream ledger workflows remain planned.

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
- Repeating the same batch key returns the existing batch without duplicate charges.
- A schedule change does not mutate already-posted charges.
- Teachers and users outside the school cannot create, preview, or post charges.

## Verification

- Focused feature tests for validation, class/term scope, preview, posting, idempotency, immutability, and authorization.
- Full PHPUnit suite, Pint, Vite build, Docker migration, and migration status.

## Unresolved decisions

- Opening-balance source evidence and school sign-off roles.
- Residence/boarding charge dimensions and mixed day/boarding rules.
- Period locking and amendment/reversal workflow.
