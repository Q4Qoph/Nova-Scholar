# SP3 attendance registers

Status: in progress; SP3-01 through SP3-03 and the first SP3-04 in-app notices slice are implemented and locally verified. External delivery, retries, read state, and alert confirmation remain open.

## Scope

Implement the first SP3 slice for school attendance:

- Create school-scoped attendance sessions for an assigned class and teaching assignment on a school-local date.
- Materialize one entry for each learner with an active class placement on that date.
- Keep `unmarked` distinct from `present`, `absent`, `late`, and `excused`.
- Let the assigned teacher or school administrator save the complete register.
- Make retries idempotent for the same session and reject stale versioned saves.
- Expose a protected attendance page and add focused authorization, validation, tenant-isolation, and persistence tests.

Alert confirmation/suppression, external delivery/retries, and read/acknowledged state remain planned follow-up slices.

## SP3-04 notices slice

Add school-admin draft/send notices targeted to all active guardians or active guardians of a selected class. Sending resolves the current active guardian relationships in a transaction and creates one in-app delivery ledger row per recipient. Re-sending the same announcement is idempotent. The guardian portal shows only delivered notices for the authenticated guardian. Email, SMS, delivery retries, and read/acknowledged actions remain separate operational work.

Acceptance additions:

- A school administrator can create a draft and send a valid notice.
- Send targeting is school-scoped and, for class notices, uses active dated class placements.
- Revoked guardian links, inactive enrolments, and guardians from other schools receive no delivery.
- Re-sending an announcement does not create duplicate recipient/channel rows.
- Guardians see only their own in-app deliveries; delivery state is distinct from future read state.

## SP3-03 guardian attendance slice

Extend the existing guardian portal with a selected linked child and school-scoped attendance history. Resolve the active guardian relationship from the authenticated user on every request; a client-supplied learner ID may select only one of those active links. Attendance is read-only and shows `unmarked` distinctly from recorded outcomes.

Acceptance additions:

- A verified guardian sees attendance only for an active linked child.
- A guardian can switch between multiple active linked children without sibling leakage.
- Revoked relationships, inactive enrolments, inactive schools, and unknown learner selections cannot return attendance.
- Attendance from another school or an unrelated enrolment is excluded even if IDs are submitted or records exist.

## SP3-02 correction slice

Add an immutable correction history for changes to an existing attendance entry. A correction requires a non-empty reason, records the actor and timestamps, and is committed in the same transaction as the updated entry and session version. The correction history is the source for later alert suppression and reconciliation; this slice does not send alerts yet.

Acceptance additions:

- Changing an existing status requires a reason and records the previous and new status.
- A correction without a reason is rejected without changing the entry or creating history.
- Authorized staff can correct only attendance in their school and permitted assignments.
- Stale correction versions are rejected without creating history or changing the current entry.
- Re-saving an unchanged status does not create a correction record.

## Dependencies and decisions

- Depends on SP1 school context and SP2 classes, teaching assignments, enrolments, and dated class memberships.
- Attendance uses the existing school timezone for the displayed date; timestamps remain application/database timestamps.
- A session is keyed by school, class, assignment, and date. The database uniqueness constraint is the final duplicate protection.
- A session version is incremented after a successful register save. A supplied old version is rejected so a stale browser cannot silently replace a newer register.
- Attendance records are institutional records; they are not added to the legacy personal-study or AI models.

## Planned changes

- Add `attendance_sessions` and `attendance_entries` migrations, models, factories, and school relationships.
- Add attendance policy, form request, service, controller, routes, and a Blade/Tailwind register page.
- Add an Attendance link to the existing school navigation.
- Add `SP3-01` feature coverage for valid saves, unmarked state, retry/version behavior, assigned-teacher authorization, cross-school isolation, and invalid learners.

## Acceptance criteria

- A teacher can open attendance for an assigned active class and date.
- A teacher can save a full register and later see the saved statuses.
- Learners with no submitted status remain `unmarked`; they are not converted to absent.
- Re-submitting the same session with the current version updates entries once and increments the version once.
- A stale version is rejected without changing any entry.
- A teacher cannot open or save another teacher's class; a school administrator can manage registers in their school.
- A user cannot access another school's attendance through a school-scoped URL or submitted IDs.

## Verification

- Run the focused attendance feature test.
- Run Laravel Pint on modified PHP files.
- Run the full PHPUnit suite and frontend build if the focused slice is green.
- Update the roadmap, architecture/data-model notes if behavior changes, and implementation log with actual results.

## Unresolved follow-up decisions

- Whether sessions need a finalized/locked state before alert confirmation and later reporting.
- Which attendance statuses and alert thresholds the pilot school will accept.
- Whether printable attendance is delivered in SP3-01 or a later authorized export slice.
