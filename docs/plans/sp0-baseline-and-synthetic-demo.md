# SP0 baseline and synthetic demo fixture

Status: implemented and locally verified; hosted PostgreSQL CI execution remains pending. The fixture is ready for SP1 consumption.

## Scope

- Add a PostgreSQL test lane alongside the existing SQLite CI lane.
- Record the local application baseline and schema inspection result.
- Add a schema-neutral, synthetic two-school fixture for the internal demo and future SP1 factories/tests.

This slice does not create school records, memberships, learner accounts, or a seeder because those application entities are intentionally SP1 work. It does not use real child, school, contact, payment, or partner data.

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

- CI defines independent SQLite and PostgreSQL test jobs.
- Both jobs run the locked dependency install and application test suite; the PostgreSQL job migrates a clean database first.
- The fixture contains two distinct schools, distinct synthetic staff, learner records with one duplicated admission number across schools, and explicitly unsupported R0 features.
- The fixture is clearly marked as non-production input and is not loaded by the current `DatabaseSeeder`.
- Current application tests, formatting, and diff checks remain green.

## Verification

- Run the local PHPUnit suite and frontend build.
- Validate the fixture JSON syntax and expected top-level shape.
- Run Pint for modified PHP files and `git diff --check`.
- Confirm CI configuration parses structurally through the repository diff; hosted PostgreSQL execution remains pending until CI runs.

## Unresolved decisions

- SP1 will decide the final school/member field names and whether this fixture becomes a seeder, factory state, or test-only input.
- PostgreSQL service image pinning and hosted runner duration will be reviewed after the first CI run.
