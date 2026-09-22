# SP2 managed learner access

Status: implemented and locally verified.

## Scope

- Add a managed learner account type without requiring an email address.
- Link one learner profile to one restricted user identity.
- Let a school administrator issue a one-time activation link with an opaque expiry-bound token.
- Let the learner choose a password and sign in with a generated learner login identifier.
- Provide a restricted learner dashboard showing only that learner's active school placements.
- Prevent managed learners from using adult profile, school administration, guardian, personal-study, or platform routes.

## Requirements

- NS03: no child email requirement, managed learner identity, and no inherited adult privileges.
- NS17: scoped access, lifecycle controls, and audit attribution.

## Dependencies

- Existing users, learner profiles, enrolments, dated class memberships, school-admin authorization, and session authentication.
- No new package or external service; email delivery remains deferred, so activation is exposed as a local/demo one-time link.

## Decisions

- Existing `users` remain the authentication table; `account_type` distinguishes adult and managed learner identities.
- Managed learners authenticate with a unique generated `learner_login_id`, not an email or fabricated placeholder address.
- Activation tokens are stored hashed, expire after 24 hours, are one-use, and are never stored or logged in raw form.
- The learner dashboard is intentionally minimal until school learning assignments exist; it shows authorized class placements only.
- Managed learners cannot use adult `verified` routes, school context, guardian portal, profile, documents, chat, quizzes, flashcards, or subscription screens.

## Acceptance criteria

- A school administrator can provision access for an enrolled learner once and receives a one-time activation URL in the local/demo response.
- A teacher, guardian, inactive member, or cross-school actor cannot provision or view another school's learner access.
- The activation page accepts a new password, consumes the token, marks the identity active, and signs the learner into the restricted dashboard.
- Expired, used, or malformed activation tokens cannot activate an account.
- A managed learner can sign out and sign in with the generated learner login identifier/password.
- Adult authentication, registration, email verification, profile, and school tests remain green.

## Verification

- Focused PHPUnit feature tests for provisioning, activation, login, token replay/expiry, role authorization, school isolation, and adult-route rejection.
- PostgreSQL migration, full PHPUnit suite, Pint, Blade cache, Vite build, and `git diff --check`.

## Unresolved decisions

- Email/SMS delivery, guardian-led activation, password recovery, device/session policy, and account deactivation UI require operational and privacy validation.
- The final learner navigation for courses, assignments, attendance, and feedback depends on later SP3/SP5 slices.
