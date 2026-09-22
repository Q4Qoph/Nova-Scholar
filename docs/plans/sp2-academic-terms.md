# SP2 academic years and terms

Status: implemented and locally verified.

## Scope

- Add school-scoped academic years and terms.
- Let school administrators create the academic year and its terms.
- Let active school staff view the academic structure.
- Validate date ranges and prevent overlapping terms within an academic year.
- Add a protected school academic page and navigation link.

## Requirements

- NS04: academic years and terms as historical school structure.
- NS17: school-scoped authorization and audit attribution.

## Dependencies

- Existing school context, scoped roles, audit events, and learner registry.
- Existing Laravel policies, Form Requests, services, and Blade/Tailwind conventions.
- No new package or external service.

## Decisions

- Academic years belong to a school and are identified by a unique school-local name.
- Terms belong to one academic year and use inclusive local dates.
- Terms in the same academic year may not overlap.
- School administrators manage structure; active staff can view it.
- Classes, subjects, teaching assignments, and progression remain later slices.

## Acceptance criteria

- Authorized staff can view only the selected school's years and terms.
- School administrators can create an academic year and terms.
- Invalid date ranges and overlapping terms are rejected.
- Cross-school and non-member access is denied without disclosure.
- Academic structure mutations create audit events.

## Verification

- Focused feature tests for creation, validation, overlap, role access, cross-school isolation, and auditing.
- Full PHPUnit suite, Pint, Vite build, and git diff check.

## Unresolved decisions

- School partner validation is still required for naming, term-count limits, promotion rules, and class/subject taxonomy.
- Historical corrections and locked terms will be designed with attendance, fees, and reports.
