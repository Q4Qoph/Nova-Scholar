# SP2 class groups and subjects

Status: implemented and locally verified.

## Scope

- Add school-scoped class groups attached to an academic year.
- Add school-scoped subjects with unique school codes.
- Let school administrators create classes and subjects.
- Let active school staff view the records through the academic structure page.
- Audit class and subject creation.

## Requirements

- NS04: classes and subjects as school academic structure.
- NS17: school-scoped authorization and audit attribution.

## Dependencies

- Implemented school context, roles, audit events, academic years, and terms.
- No new package or external service.

## Decisions

- A class group belongs to one school academic year and has a name, grade level, optional stream, and status.
- A subject belongs directly to a school and has a unique school-local code.
- Administrators manage structure; active school staff can view it.
- Teacher assignments and dated learner class memberships remain separate slices.

## Acceptance criteria

- Authorized staff can view only the selected school’s classes and subjects.
- Administrators can create class groups under an academic year and school subjects.
- Duplicate class names within an academic year and duplicate subject codes within a school are rejected.
- Cross-school and non-member access is denied without disclosure.
- Creation mutations create audit events.

## Verification

- Focused tests for creation, validation, role access, cross-school isolation, and auditing.
- Full PHPUnit suite, Pint, Vite build, and git diff check.

## Unresolved decisions

- Grade taxonomy, class capacity, subject curriculum metadata, teacher assignment rules, and learner promotion require school-partner validation.
- Dated learner class memberships and historical changes remain later work.
