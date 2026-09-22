# Local PostgreSQL availability recovery

Status: implemented and locally verified.

## Scope

- Restore the existing Compose PostgreSQL service required by the current local environment.
- Run pending Laravel migrations against the local database.
- Verify the development HTTP endpoint can load with database-backed sessions.

## Cause

The application was configured for PostgreSQL at 127.0.0.1:5433, while the Compose PostgreSQL container was stopped. Because sessions use the database driver, even the public route attempted a database query and failed before rendering.

## Acceptance criteria

- The postgres Compose service is running and healthy on host port 5433.
- Laravel migrations complete successfully.
- GET / returns HTTP 200 from the local development server.

## Verification

- docker compose ps postgres reports healthy and 0.0.0.0:5433->5432/tcp.
- php artisan migrate --force --no-interaction completes.
- curl -I http://127.0.0.1:8000/ returns HTTP/1.1 200 OK.

## Operational instruction

Start the project database before the development server when using the PostgreSQL environment:

    docker compose up -d postgres

If the project should run without Docker, switch the local environment deliberately to a configured SQLite setup and use a compatible session/cache configuration; do not silently change the shared PostgreSQL baseline.
