# SP1 invitation and role HTTP flows

Status: implemented and locally verified. Email delivery, school switching, platform-support access, and broader school-resource routes remain in progress.

## Scope

- School-admin invitation form and submission for existing verified users.
- One-time invitation review and POST acceptance for the intended verified user.
- School-admin invitation revocation.
- Staff role assignment/removal and membership removal from the protected school overview.
- Scoped route binding, Form Request authorization, CSRF-protected Blade forms, and audit reuse through the existing services.

Email delivery, school switching UI, platform-support access, public user search, and learner/guardian relationship flows remain deferred.

## Dependencies

- `CreateSchoolInvitation`, `AcceptSchoolInvitation`, and `ManageSchoolRole` services.
- `EnsureSchoolContext` and active school memberships.
- Existing Breeze Blade layout and Tailwind v4 asset pipeline.

## Decisions applied

- Invitation creation targets an existing verified email and validates staff roles before service execution.
- The raw token is placed only in the inviter’s one-time session flash as a local/demo link; only its hash is persisted.
- Invitation review/acceptance reveals details only to the intended verified user and mutates state through POST.
- Nested school membership/invitation routes use scoped model binding and the school context middleware.
- Role and membership mutations remain service-authorized and audited; controllers only coordinate validation, authorization, service calls, and redirects.

## Acceptance criteria

- An authorized school administrator can invite a verified user and receive a one-time acceptance link.
- The intended user can review and POST-accept the invitation; another user cannot view or accept it.
- Expired, revoked, replayed, and invalid links fail safely.
- An administrator can revoke a pending invitation.
- An administrator can assign/remove staff roles and remove a membership, subject to last-admin protection.
- Cross-school nested URLs resolve as 404 and cannot mutate another school’s records.
- Existing personal routes and full test coverage remain green.

## Verification

- Focused `SchoolInvitationHttpTest` feature tests for valid flows, validation, authorization, scoped binding, token disclosure, and redirects.
- Full PHPUnit suite.
- Vite build, Pint, and `git diff --check`.

## Unresolved decisions

- Replace the local/demo flash link with a configured notification/mail channel after provider and privacy readiness.
- Add school switching/navigation and role-specific home screens after the first school resources exist.
