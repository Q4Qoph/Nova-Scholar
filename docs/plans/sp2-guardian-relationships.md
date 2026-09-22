# SP2 verified guardian relationships

Status: implemented and locally verified.

## Scope

- Add school-scoped guardian links between verified users and learner enrolments.
- Let school administrators create and revoke links for existing verified users.
- Provide a restricted guardian learner view containing only actively linked learners.
- Audit link and revocation mutations.
- Recheck relationship status on every guardian request.

## Requirements

- NS03: guardian relationships and managed child access.
- NS17: authorization, revocation, and audit boundaries.
- NQ01: no cross-school or sibling leakage.

## Dependencies

- Implemented learner profiles, enrolments, school context, scoped roles, and audit events.
- Existing verified-user authentication and Blade routes.
- No new package or external service.

## Decisions

- A link belongs to a school enrolment, not only a global learner profile, so the same learner can be represented safely across schools.
- Only an existing verified user can be linked.
- School administrators explicitly verify the relationship when creating it; automatic email invitations are deferred.
- Active guardians see limited identity and enrolment details only; school staff screens remain separate.
- Revocation is soft and immediately blocks future guardian reads.

## Acceptance criteria

- A school administrator can link a verified user to an enrolment with a relationship label.
- A guardian can see only their active linked learners.
- Multiple learners and multiple guardians are supported.
- Revoked links disappear from guardian access immediately.
- Unverified users, unrelated users, cross-school enrolments, and non-admin link mutations are rejected.
- Link and revocation actions create school audit events.

## Verification

- Focused feature tests for creation, guardian read scope, multiple links, revocation, authorization, verification, and cross-school isolation.
- Full PHPUnit suite, Pint, Vite build, and git diff check.

## Unresolved decisions

- Email invitation/delivery, guardian acceptance tokens, disputed relationships, and formal evidence/retention procedures require operational/privacy review.
- Attendance, notices, statements, and released learning results remain later guardian portal slices.
