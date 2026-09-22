# SP2 learner registry foundation

Status: in progress.

## Scope

- Add learner profile and school enrolment persistence following the documented SP2 model.
- Admit a learner with a school-scoped, unique admission number.
- Provide nested school routes for listing and viewing learners.
- Allow active school staff to view learners and school administrators to admit learners.
- Enforce school ownership through nested binding and an explicit enrolment policy.
- Record learner admission in the existing school audit stream.

## Requirements

- NS03: learner records and school-scoped admission.
- NS17: authorization and audit boundaries.
- NQ01: tenant isolation and no cross-school disclosure.

## Dependencies

- Existing schools, active memberships, scoped roles, audit events, and school context middleware.
- Existing Laravel migrations, Form Requests, policies, and Blade conventions.
- No new package or external service.

## Decisions

- A learner profile is reusable identity data; an enrolment binds it to one school and carries the school admission number.
- Admission numbers are unique within a school, not globally.
- School administrators may admit learners; active staff may view learners.
- Learner profiles have no login or email in this slice.
- Deletion, transfers, guardians, classes, imports, and managed learner activation remain deferred.

## Acceptance criteria

- An authorized active staff member can list and view only the selected school's learners.
- A school administrator can admit a learner with validated data and an audit event.
- Duplicate admission numbers in the same school are rejected.
- The same admission number is allowed in another school.
- Teachers cannot admit learners; non-members and cross-school nested routes return 404/403 without disclosure.
- Existing SP1 and personal routes remain green.

## Verification

- Focused feature tests for listing, admission, validation, policy roles, duplicate scope, and cross-school isolation.
- Full PHPUnit suite, Pint, Vite build, and git diff check.

## Unresolved decisions

- Guardian relationship verification, class/term enrolments, learner accounts, CSV staging/import, and transfer history are later SP2 slices.
- The final learner profile fields and retention policy require school-partner validation before production use.
