# SP2 staged learner imports

Status: implemented and locally verified.

## Scope

- Accept a school-admin CSV upload with a fixed learner/enrolment header.
- Stage each non-empty row privately with validation state and errors.
- Show a school-scoped preview before committing valid rows.
- Commit valid rows into learner profiles and enrolments without duplicating admission numbers.
- Keep invalid rows available for correction/review and audit staging/commit actions.

## Requirements

- NS03: learner records and enrolments.
- NS05: validated staged imports, idempotent replay, and scoped exports foundation.
- NS17: school-scoped authorization and audit attribution.

## Dependencies

- Existing school context, school-admin policy, learner registry, admission service, and PostgreSQL unique admission constraint.
- No new package or external service; use PHP's CSV reader and database JSON fields.

## Decisions

- CSV headers are `first_name,last_name,preferred_name,date_of_birth,admission_number`.
- Uploads are retained as parsed database payloads rather than publicly accessible files; the original filename and SHA-256 checksum are retained for traceability.
- A repeated checksum in the same school reuses the existing batch and cannot create duplicate learners.
- Valid rows can commit while invalid rows remain visible as invalid; a batch with invalid rows is marked partially committed.
- Assignment/class membership columns are deferred until dated learner class memberships exist.

## Acceptance criteria

- Only an active school administrator can stage or commit a batch.
- Missing headers, malformed dates, duplicate admission numbers, and duplicate rows are reported per row without creating learners.
- A preview displays row status and validation errors within the authorized school only.
- Committing valid rows creates learner profiles, enrolments, and audit events; invalid rows remain uncommitted.
- Replaying the same uploaded file or committing the same batch does not duplicate learners.
- Cross-school batch access returns 404 and non-admin mutation is forbidden.

## Verification

- Focused PHPUnit feature tests for staging, validation, preview, commit, replay, role authorization, and cross-school isolation.
- Blade cache, Pint, Vite build, full PHPUnit suite, and `git diff --check`.

## Unresolved decisions

- Maximum CSV size/row count and retention period require operational validation; the first implementation uses conservative limits.
- Header versioning, class assignment columns, transfer matching, and authorized exports remain deferred.
