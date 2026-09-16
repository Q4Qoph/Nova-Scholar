# P0 foundation and feasibility plan

Status: in progress. Started 16 September 2026. This plan implements the foundation portion of the roadmap without purchasing services, using real provider credentials, or changing application dependencies.

## Scope

Deliver a reproducible local-services definition, environment-variable contract, baseline CI, and recorded feasibility gaps. It covers roadmap P0 and decisions D01–D05, D08–D10, D12, and D14. It does not implement student-facing functionality or choose paid providers.

## Work items

| Item | Status | Acceptance evidence |
| --- | --- | --- |
| Confirm framework baseline | complete | Laravel `^13.17`, PHP 8.4.24, PHP PostgreSQL extension, and Docker Compose v5.1.4 observed locally |
| Provide local PostgreSQL + vector capability | verified locally | PostgreSQL is healthy on host port 5433; `vector` extension version 0.8.6 is installed; Laravel's default migrations passed |
| Provide local Redis, Mailpit, and MinIO services | verified locally | Redis and Mailpit health checks pass; MinIO runs on ports 9000/9001 and its HTTP health endpoint passes |
| Define safe environment contract | complete | `.env.example` names Nova Scholar and lists blank external credentials; `.env.docker.example` carries local-only service values |
| Add PHP test and frontend-build CI | verified locally, pending hosted execution | `.github/workflows/ci.yml` uses locked Composer install, SQLite test setup, PHPUnit, and Vite build; `package-lock.json` now makes `npm ci` reproducible |
| Make frontend build network-independent | verified locally | Removed the starter Bunny Fonts Vite integration after it made the local build depend on `fonts.bunny.net`; Vite builds with a local system-font stack |
| Choose framework dependencies | blocked by standing repository rule | Livewire/auth, Redis client, S3 filesystem adapter, and extraction libraries need an approved dependency change after compatibility review |
| Run extraction, retrieval, load, and cost spikes | pending | Requires selected packages/models, representative material, and an agreed evaluation rubric/budget |
| Resolve product scope and commercial allowances | pending owner decision | D04/D05 accepted with documented plan limits, trial/tax policy, and cost model |

## Local use

1. Keep the existing `.env` for the default SQLite workflow, or copy `.env.docker.example` to `.env` only when using the Docker services.
2. Run `docker compose up -d`; Docker downloads the declared service images the first time. PostgreSQL is exposed on host port 5433 to avoid replacing an existing local PostgreSQL service, so `.env.docker.example` uses `DB_PORT=5433`. MinIO uses `quay.io/minio/minio:latest` because the previously published Docker Hub and dated Quay tags are no longer available. Resolve and pin an image digest before a shared or production environment uses it.
3. Generate an application key, migrate the PostgreSQL database, and confirm `vector` is available before implementing retrieval.
4. Mailpit is available at `http://localhost:8025`; MinIO's console is at `http://localhost:9001`. Create the `nova-scholar` bucket only when S3 filesystem support is intentionally added.

Local Docker credentials are deliberately public development values. They are not usable in staging or production and must never be copied to a deployed environment.

## Dependency decision needed

The project presently has no Redis PHP extension and no installed Redis/S3/extraction/Livewire packages. The generated Laravel Boost rule says application dependencies must not change without approval. Before P1/P3, review current Laravel 13-compatible versions and approve a minimal package set for authentication/UI, a Redis client, object-storage filesystem support, document extraction, and the chosen AI provider integration.

## Verification plan

- Validate `docker compose config` and service definitions.
- Confirm Laravel can run the current tests and the Vite build against locked frontend dependencies.
- Inspect service health, run `CREATE EXTENSION IF NOT EXISTS vector`, and migrate PostgreSQL.
- Verify MinIO's health endpoint; development mail and S3 filesystem access remain pending their application-level implementation and approved dependencies.

## Completion criteria

P0 is verified only after all services start successfully, PostgreSQL exposes the needed vector extension, CI completes in its hosted environment, package choices are approved and tested, a representative extraction/retrieval spike produces evidence, and D04/D05 are decided. Local-services and baseline-build work is verified; P0 remains open for the outstanding product and provider decisions.
