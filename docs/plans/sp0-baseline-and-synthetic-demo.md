# SP0 baseline and synthetic demo fixture

Status: the original SP0 baseline is implemented and locally verified; the local-only demo seeder has since been extended with synthetic teacher and managed-learner accounts, a published lesson, and a published assignment. Hosted PostgreSQL CI and frontend build passed in corrective run `36009434351` on commit `93229c4`. The assignment demo fixture passes focused SQLite feature coverage; VPS operations remain deferred.

## Scope

- Add a PostgreSQL test lane alongside the existing SQLite CI lane.
- Record the local application baseline and schema inspection result.
- Add a schema-neutral, synthetic two-school fixture for the internal demo and future SP1 factories/tests.

The original baseline slice did not create school records or a seeder because those application entities were SP1 work. The existing local-only `DemoSchoolSeeder` now seeds synthetic records to exercise the MVP end to end. It does not use real child, school, contact, payment, or partner data and returns without changes outside the local environment.

## Dependencies

- Existing Laravel 13/PHP 8.4 application and locked Composer dependencies.
- Existing SQLite test lane and migrations.
- SP1 school/membership schema, which will consume the fixture in application tests.

## Decisions applied

- SQLite remains the fast default CI lane.
- PostgreSQL is added for relational/concurrency compatibility checks before school schema work lands.
- The fixture uses stable logical keys, not database IDs, emails, phone numbers, or real personal data.
- Identical admission numbers across the two schools are intentional negative-test input for tenant isolation.

## Acceptance criteria

- CI defines independent SQLite and PostgreSQL test jobs. The recorded hosted run passed both jobs and the frontend build; this does not prove the later current worktree against PostgreSQL.
- Both jobs run the locked dependency install and application test suite; the PostgreSQL job migrates a clean database first.
- The fixture contains two distinct schools, distinct synthetic staff, learner records with one duplicated admission number across schools, and explicitly unsupported R0 features.
- The fixture is clearly marked as non-production input and is not loaded by the current `DatabaseSeeder`.
- Current application tests, formatting, and diff checks remain green.

## Verification

- Run the local PHPUnit suite and frontend build.
- Validate the fixture JSON syntax and expected top-level shape.
- Run Pint for modified PHP files and `git diff --check`.
- Confirm CI configuration parses structurally through the repository diff; hosted CI result is recorded above. Re-run after relevant CI changes. The current worktree's full PHPUnit suite passed against the isolated PostgreSQL container on 28 September (253 tests, 1,162 assertions).

## Unresolved decisions

- SP1 will decide the final school/member field names and whether this fixture becomes a seeder, factory state, or test-only input.
- PostgreSQL service image pinning and hosted runner duration will be reviewed after the first CI run.
