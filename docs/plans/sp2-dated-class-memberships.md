# SP2 dated learner class memberships

Status: implemented and locally verified.

## Scope

- Record a learner enrolment's class placement with effective start and optional end dates.
- Preserve historical placements when a learner changes class.
- Allow school administrators to create same-school placements from the learner record.
- Display placement history to authorized school staff.
- Prevent overlapping placements for one enrolment and audit each assignment.

## Requirements

- NS03: learner enrolments and managed school records.
- NS04: class assignment and progression history.
- NS17: school-scoped authorization and audit attribution.

## Dependencies

- Existing enrolments, class groups, school context, policies, and audited learner registry.
- No new package or external service.

## Decisions

- A placement belongs to one school, enrolment, and class group with `starts_on` and nullable `ends_on` dates.
- Only one placement may overlap another placement for the same enrolment; changing class requires ending the previous placement explicitly or through the assignment service.
- School administrators create placements; active school staff view placement history.
- Attendance, promotion automation, transfer matching, and learner login remain separate workflows.

## Acceptance criteria

- An administrator can assign an enrolled learner to a same-school active class group for a valid date range.
- Overlapping date ranges for the same enrolment are rejected, including open-ended current placements.
- Cross-school enrolments/classes and non-admin mutations are rejected.
- Historical placements remain readable after a later placement is created.
- Creation is audited and does not expose another school's learner or class data.

## Verification

- Focused PHPUnit feature tests for creation, overlap validation, cross-school isolation, staff read access, and admin authorization.
- PostgreSQL migration, full PHPUnit suite, Pint, Blade cache, Vite build, and `git diff --check`.

## Unresolved decisions

- Promotion rules, bulk placement imports, class capacity, and automatic end-date behavior require school-operations validation.
