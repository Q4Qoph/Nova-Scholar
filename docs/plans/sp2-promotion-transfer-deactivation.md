# SP2 promotion, transfer and deactivation

Status: implemented and locally verified.

## Scope

- Let a school administrator promote an enrolled learner into a new same-school class using effective dates while closing the current placement.
- Let an administrator who has active admin authority in both schools transfer a learner profile to a destination school with a new destination admission number.
- Retain source enrolment and class-placement history after transfer; do not copy private school records.
- Let a school administrator withdraw a learner enrolment and deactivate its managed learner identity without deleting institutional history.
- Audit promotion, transfer, withdrawal, and managed-access deactivation with actor and school attribution.

## Requirements

- NS04: academic progression and historical enrolments/results survive term changes and transfers.
- NS17: scoped access, lifecycle controls, and audit attribution.

## Dependencies

- Existing school-scoped enrolments, dated class memberships, managed learner identities, school-admin authorization, and audit events.
- No new package or external service.

## Decisions

- Promotion is a dated class-placement transition: the current open placement ends the day before the new placement starts, and the old row is never overwritten.
- Transfer creates a new destination-school enrolment for the existing learner profile and withdraws the source enrolment; admission numbers remain unique within each school.
- A transfer actor must be an active school administrator in both the source and destination schools.
- Deactivation withdraws the enrolment, marks the learner profile inactive, and sets a managed learner's deactivation timestamp. No user, profile, enrolment, placement, or audit row is deleted.
- Deactivated managed learner sessions are rejected by learner middleware and future login attempts; reactivation is a later workflow.

## Acceptance criteria

- An authorized administrator can promote a learner without overlapping class placements; the prior placement ends one day before the new placement.
- Promotion rejects cross-school classes, inactive classes, invalid dates, and non-admin actors.
- A dual-school administrator can transfer a learner, retaining the source enrolment and history while creating a destination enrolment for the same profile.
- A source-only administrator, duplicate destination admission number, inactive destination school, or cross-school unauthorized request is rejected.
- Deactivation withdraws the enrolment, preserves history, blocks managed learner login/access, and records an audit event.
- Adult accounts and existing school isolation behavior remain unchanged.

## Verification

- Focused feature tests cover promotion history, transfer authorization/isolation, admission uniqueness, and deactivation/login rejection.
- Run PostgreSQL migrations, focused/full PHPUnit, Pint, Blade cache, Vite build, and `git diff --check`.

## Unresolved decisions

- Re-activation, bulk promotion/transfer, destination-school notification, boarding placement details, and private-resource migration require operational validation.
