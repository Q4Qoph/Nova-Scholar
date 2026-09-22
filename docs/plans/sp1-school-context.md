# SP1 school context and protected overview

Status: implemented and locally verified. Invitation and role-management services are implemented separately; school switching, nested resource policies, jobs, and support access remain in progress.

## Scope

Implement the first request boundary for school records:

- Resolve a bound `School` only through an active membership of the authenticated verified user.
- Return not-found for inactive or unauthorized school contexts so another school’s existence is not disclosed.
- Attach the authorized membership to the request for downstream controllers/services.
- Add a minimal protected school overview route and view as an observable proof of the boundary.

Invitations, role-management mutations, school switching UI, nested resource policies, jobs, and support access remain later SP1 work.

## Dependencies

- SP1 school/membership/role/audit migrations and `SchoolMembership::active()` scope.
- Laravel route model binding and existing session/verified middleware.
- No new dependency.

## Decisions applied

- The route’s school identifier selects a candidate context; it never grants access.
- Active membership and active school status are rechecked on every protected request.
- A missing membership, removed membership, or inactive school returns 404.
- The authorized membership is request-scoped and is not stored in global/static state.
- The overview displays only the selected school and the current user’s scoped roles.

## Acceptance criteria

- A verified member can view their school overview.
- A user with membership in school A receives 404 for school B.
- Removed memberships and inactive schools receive 404.
- Guests still follow the existing login redirect, and unverified users follow the existing verification boundary.
- The route does not accept a posted school ID or bypass membership authorization.
- Existing personal routes and full test coverage remain green.

## Verification

- Focused `SchoolContextTest` feature tests for valid access, cross-school denial, revocation, inactive schools, guest, and unverified access.
- Full PHPUnit suite.
- Vite build for the new Blade view, Pint, and `git diff --check`.

## Unresolved decisions

- School switching/navigation and a durable selected-school preference will be designed with invitations and role management.
- Role-specific route policies will be added when the first school-owned resources are implemented.
