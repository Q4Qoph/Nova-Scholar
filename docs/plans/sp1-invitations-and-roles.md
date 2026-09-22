# SP1 invitations and role lifecycle

Status: implemented and locally verified for services and HTTP flows. Email delivery, school switching, and broader resource policies remain in progress.

## Scope

Implement the school membership lifecycle needed for NS02, NS17, and NQ01:

- Expiring, one-use invitations bound to an existing verified user and school.
- School-admin authorization for creating and revoking invitations.
- Invitation acceptance that creates an active membership and scoped role atomically.
- Controlled role assignment/removal within the school.
- Protection against removing or demoting the last active school administrator.
- Minimal audit events for invitation creation, acceptance, revocation, role assignment, and role removal.

The notification/email delivery channel, public user search, school switching UI, platform-support access, and broader school-resource routes remain deferred.

## Dependencies

- `School`, `SchoolMembership`, `SchoolRoleAssignment`, `AuditEvent`, `SchoolRole`, and `EnsureSchoolContext`.
- Existing verified user accounts and session authentication.
- No new dependency or payment/provider integration.

## Decisions applied

- Invitations target an existing verified user email; no unverified account is silently activated.
- Raw invitation tokens are returned only to the caller that creates the invitation; only a hash is stored.
- Acceptance requires the authenticated user to match the intended invitee and rechecks expiry/revocation/status inside a transaction.
- A user cannot be invited into a school where an active membership already exists.
- School roles cannot modify `UserRole`; platform administrator authority remains separate.
- Removing the last active `school_admin` assignment is rejected.

## Acceptance criteria

- A school administrator can invite a verified user with a valid scoped role.
- Non-members, non-admin members, cross-school actors, unverified invitees, duplicate active memberships, expired tokens, revoked tokens, and replayed tokens are rejected.
- Acceptance creates exactly one active membership, role assignment, and audit event.
- Role assignment/removal is same-school and admin-authorized.
- Last-school-admin protection applies to both role removal and membership removal paths implemented in this slice.
- Existing school context, personal routes, and full test coverage remain green.

## Verification

- Focused `SchoolInvitationTest` feature tests for authorization, token lifecycle, role boundaries, replay/expiry/revocation, and audit attribution.
- Full PHPUnit suite.
- Pint and `git diff --check`.

## Unresolved decisions

- Email notification/provider configuration and invitation-link presentation are deferred.
- School switching and role-specific navigation will follow once invitation acceptance is stable.
