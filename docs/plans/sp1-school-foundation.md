# SP1 school foundation

Status: implemented and locally verified for the provisioning/audit foundation. Protected context, invitations, and role lifecycle are implemented in separate slices; scoped resource routes remain in progress.

## Scope

Implement the persistence and controlled-provisioning foundation for NS02, NS17, and NQ01:

- `schools`, `school_memberships`, `school_role_assignments`, and `audit_events` tables.
- Eloquent models, relationships, factories, and explicit role values.
- A service that allows only a platform administrator to provision a school and its first school administrator atomically.
- Audit attribution for the provisioning action.

Invitations, school-context middleware, role-management HTTP routes, support access, and school UI remain later SP1 slices. No public self-promotion or user-controlled global role mutation is added.

## Dependencies

- Existing verified `users` and server-controlled `UserRole::Admin`.
- SP0 synthetic fixture at `tests/Fixtures/synthetic-school-demo.json`.
- PostgreSQL-compatible migrations; SQLite remains the fast test database.

## Decisions applied

- A school membership is distinct from the global user role and may carry multiple scoped roles.
- A user may belong to multiple schools, but one membership is unique per school/user pair.
- School role keys are `SchoolAdmin`, `Teacher`, `Bursar`, `Guardian`, and `Learner`; this slice assigns only `SchoolAdmin`.
- Provisioning requires an existing verified platform administrator and a verified first school administrator.
- Audit metadata stores minimal structured context and never passwords, private prompts, files, or payment payloads.
- School records are additive and separate from personal documents, chats, subscriptions, and historical records.

## Acceptance criteria

- Migrations create the four tables with foreign keys, unique constraints, indexes, and useful status/timestamp fields.
- Relationships expose a school’s memberships, role assignments, and audit events, and a user’s school memberships.
- A non-admin or unverified actor cannot provision a school.
- A valid admin provisioning request creates one school, one active membership, one school-admin role, and one audit event atomically.
- Repeated provisioning with the same slug fails without a partial school/member/audit set.
- The first school administrator cannot receive platform-admin authority through provisioning data.
- Existing full test coverage remains green.

## Verification

- Focused `SchoolFoundationTest` feature tests for authorization, persistence, uniqueness, and audit attribution.
- Full PHPUnit suite.
- Pint and `git diff --check`.
- Local migration status and PostgreSQL CI lane remain separate evidence; hosted PostgreSQL execution is still pending.

## Unresolved decisions

- Final school lifecycle states and suspension workflow will be decided with SP1 invitations/context middleware.
- Exact platform-support role and time-limited audited access are deferred until the support workflow is implemented.
