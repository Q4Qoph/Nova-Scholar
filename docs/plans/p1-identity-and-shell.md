# P1 identity and application shell plan

Status: in progress. Started 16 September 2026 after owner approval to proceed with the outstanding dependency and implementation work.

Implementation update (16 September 2026): the scoped authentication, profile, and shell work is implemented and locally verified. P1 remains in progress until manual responsive/accessibility review and configured-mail delivery are exercised in an environment intended for users.

## Scope

Implement FR1–FR3, the account-email part of FR13, and the foundation for NFR4/NFR6. The first release uses Laravel’s session-based web authentication. It provides a public Nova Scholar landing page, registration, sign-in/out, password reset, email verification, a verified student dashboard, profile name/email/password settings, profile photo upload, learning preferences, and role-aware access boundaries.

The implementation uses Laravel Breeze’s Blade scaffold for standard authentication and installs Livewire for later interactive learning features. This is a deliberate P1 boundary: no Livewire component is required solely to render a conventional authentication form. The existing SRS/SAD choice of Blade + Livewire + Alpine remains intact for the application as it grows.

## Decisions applied

- D02 is accepted for P1: session authentication and CSRF-protected Blade forms serve the first-party web app; Sanctum remains deferred until an API/mobile client needs tokens.
- The existing `users` migration has only run locally in the isolated compose database, so it may be extended before shared deployment. The test database starts clean.
- User-controlled fields are limited to name, email, password, photo, and learning preferences. Role assignment, suspension, and billing state are server-controlled.
- New accounts are students. Administrator creation is deferred to a protected operational workflow; users cannot select or alter a role through registration/profile requests.
- Profile images are private application data. P1 stores them on the default local disk and serves them through an authorized endpoint. Public avatars and cloud object storage await P3/provider setup.
- Learning preferences are a small validated JSON document: preferred subjects, daily study goal minutes, and timezone. The planner may use them later.

## Delivery slices

| Slice | Scope | Verification |
| --- | --- | --- |
| Authentication scaffold | Complete: installed Laravel 13-compatible Breeze Blade scaffold and Livewire; built assets | Login, registration, reset, verification, and logout routes exist; asset build passes |
| Account model | Complete: student role, preference JSON, private photo path, factory support, verification interface | Migration applies; mass assignment excludes privileged state |
| Shell and dashboard | Complete: Nova Scholar landing and verified dashboard navigation | Automated guest and verified-student access tests pass; manual visual review remains |
| Profile | Complete: name/email/password, photo, and preferences through validated requests | Persistence, validation, and private-photo tests pass |
| Security and tests | Complete for automated P1 boundary coverage | Unverified dashboard redirect, role protection, and private-photo access tests pass |

## Acceptance criteria

- Registration rejects duplicate/invalid email and passwords under eight characters; a valid registration creates a student and sends verification mail.
- Login, logout, reset request, reset completion, verification link, and verification notice work with Laravel’s built-in protections and rate limiting.
- Only verified users access the dashboard and later student routes; unauthenticated users redirect to sign-in.
- Profile changes persist only validated intended fields. Valid profile image formats are JPEG, PNG, and WebP up to 2 MB. Dangerous/unsupported files are rejected.
- Changing email requires re-verification. Preferences validate subjects, daily goal 0–720 minutes, and a valid IANA timezone.
- The dashboard and auth pages have usable mobile layouts, labels, focus states, and meaningful validation/status feedback.

## Out of scope

Admin management UI, institution accounts, subscriptions, documents, AI calls, S3 storage, notifications beyond built-in account email, and product analytics. Password-reset and verification mail use Laravel’s configured mailer; in local development this is Mailpit/log output, not a live email provider.

## Dependencies and risks

Laravel Breeze and Livewire must resolve against Laravel 13. The starter scaffold can modify routes, views, migrations, tests, and frontend configuration; review the generated diff before customization. Profile uploads need an explicit authorized serving route to avoid exposing private student data. Do not mark the phase verified until relevant feature tests, formatting, and asset build pass.
