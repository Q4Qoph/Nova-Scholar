# SP2 teaching assignments

Status: implemented and locally verified.

## Scope

- Add school-scoped teaching assignments connecting a teacher, class group, and subject.
- Allow school administrators to create assignments for active school teachers.
- Let active school staff view assignments on the academic structure page.
- Enforce same-school and active-record constraints.
- Audit assignment creation.

## Requirements

- NS04: teaching assignments across classes, subjects, and teachers.
- NS17: school-scoped authorization and audit attribution.

## Dependencies

- Implemented school context, scoped roles, academic years, class groups, subjects, and audit events.
- No new package or external service.

## Decisions

- An assignment belongs to one school, class group, subject, and teacher membership/user.
- Only users with an active school Teacher role may be assigned.
- School administrators manage assignments; active staff can view them.
- Removal, effective dates, workload limits, and historical assignment versions remain deferred.

## Acceptance criteria

- An administrator can assign an active school teacher to an existing same-school class and subject.
- Staff can view assignments only inside an authorized school context.
- Non-teachers, inactive teachers, cross-school classes/subjects, and non-admin mutations are rejected.
- Duplicate teacher/class/subject assignments are rejected.
- Assignment creation creates an audit event.

## Verification

- Focused tests for creation, role/record validation, duplicate protection, authorization, cross-school isolation, and auditing.
- Full PHPUnit suite, Pint, Vite build, and git diff check.

## Unresolved decisions

- Assignment effective dates, substitutes, workload caps, subject/class taxonomy, and teacher transfer behavior require operational validation.
- Historical assignment retention will be designed with dated learner class memberships.
