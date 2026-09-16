# Implementation log

## 2026-09-16 — Planning baseline and required tooling setup

Status: documentation complete; product implementation not started.

Changes:

- Inspected the fresh Laravel skeleton and dependency declarations.
- Verified PHP 8.4.24 and Composer 2.7.1 are available. Composer emitted deprecation warnings with this PHP version; dependency installation succeeded.
- Installed Laravel Boost v2.9.0 as a development dependency with its locked dependencies. Ran Boost installation and reread the generated `AGENTS.md`.
- Initial sandbox restrictions blocked network access and writes to protected agent directories. Approved retries completed installation, including Codex and Claude guidance/skills/MCP configuration.
- Added requirements traceability, staged delivery plan, proposed architecture/data model, decision register, and verification/operations planning under `docs/`.
- Added a standing documentation rule outside the generated Boost section in `AGENTS.md` requiring plans and implementation records for future changes.

Verification:

- Boost installation reported successful guidelines, skills, and MCP setup for both configured agents.
- Composer reported no security vulnerability advisories during installation; this is not a full application security review.
- `php artisan test --compact`: passed the 2 existing example tests (2 assertions). This verifies the skeleton baseline only, not planned product functionality.
- `git diff --check`: passed with no whitespace errors in tracked changes.
- Checked all 11 local Markdown links under `docs/`: every target exists.

Limitations: no authentication UI, learning, payments, institutional features, infrastructure, or external integrations have been implemented. All P0–P9 product phases remain proposed. Commercial allowances, scope conflicts, provider access, privacy policy, and operational ownership remain decisions to resolve.

Next step: work through P0 feasibility and decisions, then create the detailed P1 implementation plan before building identity and the application shell.

## 2026-09-16 — P0 local foundation and CI baseline

Status: in progress.

Changes:

- Added `compose.yaml` for local PostgreSQL with pgvector, Redis, Mailpit, and MinIO.
- Added `.env.docker.example` for those local services while preserving the default SQLite `.env.example` workflow.
- Named the application Nova Scholar in `.env.example` and added blank environment-variable placeholders for AI and M-Pesa credentials.
- Added a GitHub Actions workflow that installs locked dependencies, runs the existing test suite against SQLite, and builds Vite assets.
- Added the detailed P0 plan in `docs/plans/p0-foundation.md`.

Verification:

- `docker compose config --quiet`: passed.
- Downloaded and started the local stack. PostgreSQL could not use host port 5432 because it was already occupied, so the compose mapping and Docker environment template use host port 5433. Existing local services were not modified.
- PostgreSQL is healthy. `CREATE EXTENSION IF NOT EXISTS vector` succeeded and reports pgvector 0.8.6. Laravel's three default migrations completed against the isolated compose database.
- Redis and Mailpit health checks pass. MinIO runs on ports 9000/9001; `curl --fail http://127.0.0.1:9000/minio/health/live` passed.
- `php artisan test --compact`: passed the 2 existing example tests (2 assertions). This is a skeleton baseline, not product verification.
- `npm install --ignore-scripts`: installed the declared frontend development dependencies, created `package-lock.json`, and reported 0 vulnerabilities. The lockfile lets CI use `npm ci`.
- The initial `npm run build` failed because the Laravel starter font plugin attempted to resolve `fonts.bunny.net`. Removed the remote font integration and retained the system font stack; the final Vite build passed (three modules transformed).
- Final `php artisan test --compact`: passed the 2 existing example tests (2 assertions).
- Final `git diff --check`: passed with no whitespace errors.

Limitations: MinIO's prior Docker Hub repository and dated Quay tags were unavailable at setup time, so local development uses the currently published `quay.io/minio/minio:latest` image. Pin its resolved digest before a shared or production use. No provider credentials, provider calls, production resources, or paid services were created.

Livewire and the Laravel Breeze authentication scaffold were added for P1 after owner approval. The PHP Redis extension and application dependencies for Redis, S3, document extraction, and AI integration remain absent. P0 remains in progress pending hosted CI execution, feasibility spikes, and D04/D05 decisions.

## 2026-09-16 — P1 identity and application shell

Status: implemented and locally verified; manual responsive/accessibility review and configured-mail delivery remain before P1 can be closed.

Created `docs/plans/p1-identity-and-shell.md` before implementation. The owner approved Laravel Breeze Blade authentication plus Livewire. P1 now provides a Nova Scholar landing page, registration/sign-in/sign-out, password reset, email verification, a verified dashboard, profile name/email/password settings, private local profile photos, learning preferences, and server-controlled student roles.

Changes:

- Installed `laravel/breeze` v2.4.2 as a development dependency and `livewire/livewire` v4.4.5 as an application dependency, then generated Breeze's Blade authentication scaffold.
- Added `App\\UserRole`, with `student` assigned only by server-side registration logic and `admin` reserved for a future controlled workflow.
- Extended `users` with `role`, `profile_photo_path`, and `learning_preferences`; updated the model and factory casts/defaults and enabled Laravel email verification.
- Added validated profile preference/photo handling. JPEG, PNG, and WebP images up to 2 MB are stored privately and served only to their signed-in owner.
- Replaced the starter entry page with a responsive Nova Scholar landing page and a verified student dashboard shell.
- Restored the project to Tailwind CSS v4 after Breeze generated a v3 configuration, retaining the Tailwind Vite plugin and Breeze's Alpine behavior.

Verification:

- Applied the profile migration to the isolated PostgreSQL/pgvector compose database.
- Targeted P1 feature tests: 14 passed, 50 assertions.
- Full test suite: 29 passed, 80 assertions.
- `vendor/bin/pint --dirty --format agent`: passed after applying one import-order fix.
- `npm run build`: passed with Tailwind CSS v4 and no remote font dependency.

Limitations: No real email provider has been configured, so delivery outside local development is untested. Manual screen-reader, keyboard, and small-device testing is also outstanding. Admin provisioning, suspension, and policies for future owned learning resources remain later P1/P8 work; no user can self-assign an administrator role.

## 2026-09-16 — P2 plans, entitlements, and usage foundation

Status: implemented and locally verified; P2 remains open for approved commercial policy and production concurrency validation.

Changes:

- Added plans, price versions, plan features, subscriptions, subscription periods, usage reservations, and usage records with foreign keys, uniqueness constraints, ownership/status indexes, UTC timestamps, and integer minor units for costs.
- Added model relationships, casts, and factories for the P2 domain.
- Added `EntitlementService` to resolve only a user's current active period/plan and `UsageService` to reserve idempotently, settle once, or release feature usage. Finite allowances count both pending reservations and settled records.
- Added a verified-only, read-only `/subscription` page and dashboard link. It displays access status and any public plans but deliberately has no checkout, trial, or entitlement-granting action.
- Added P2 feature tests for access boundaries, entitlement windows, request-key idempotency, allowance exhaustion, settlement, and release behavior.

Verification:

- Started the local Docker services and applied all seven P2 migrations to the isolated PostgreSQL database.
- Focused P2 tests: 9 passed, 20 assertions.
- Full test suite: 38 passed, 100 assertions.
- `vendor/bin/pint --dirty --format agent`, `npm run build`, and `git diff --check`: passed.

Limitations: D05 remains unresolved, so no plan, price, allowance, trial, tax rule, or payment behavior is seeded. Row locking is implemented and tested functionally with the SQLite suite, but real concurrent reservation behavior must be stress-tested on production-like PostgreSQL/Redis before charging or gating live provider calls. P2 does not integrate M-Pesa, a provider adapter, queues, Redis locking, or spend alerts.

### P2 catalogue update — accepted BRD prices

The owner accepted the BRD's individual monthly prices: Student KES 299 and Pro KES 699. Added an idempotent `PlanSeeder`, invoked by `DatabaseSeeder`, that creates the two public monthly catalogue records and their KES minor-unit price versions effective 1 July 2026 UTC. Added the `billing_interval` field and displays current prices on the read-only subscription page. The School KES 5,000–20,000 monthly range remains deferred as institution-specific pricing. Allowances, trial policy, tax, payment behavior, and actual entitlements remain unresolved and unseeded.

### P3 decisions — document extraction and allowances

The owner selected Apache Tika as the private PDF/DOCX/TXT extraction service; OCR is deferred. The recommended local container is the pinned `apache/tika:3.3.1.0` image, to be digest-pinned before shared deployment. The owner also approved Student storage of 20 documents/1 GB and Pro storage of 100 documents/10 GB, with a 100 MB per-file limit. These allowances require plan-feature seeding and extraction benchmarking before enforcement.

### P3 upload foundation

Added the private document schema, user ownership relation/policy, validated verified-user upload/list/delete routes, and document-library page. Uploads accept PDF/DOCX/TXT up to 100 MB and use generated private local-storage paths; processing is dispatched after commit. Automated suite: 40 tests, 105 assertions passed; Pint passed. Tika service wiring, extraction status transitions, download endpoint, allowance enforcement, migration verification, and document-specific tests remain in progress.

### P3 Apache Tika local service

Added the pinned `apache/tika:3.3.1.0` Docker Compose service, restricted to loopback port 9998, with a health check and application configuration (`TIKA_URL`, `TIKA_TIMEOUT_SECONDS`). `docker compose config --quiet` and Laravel configuration inspection pass. Extraction job wiring, a real service start/pull verification, and document processing tests remain in progress.

### P3 queued extraction implementation

Implemented `ProcessDocument`: it streams the private original to Tika with a bounded connection/request timeout, records `extracting`, marks successful non-empty text as `ready`, records `no_extractable_text` when appropriate, and safely marks exhausted jobs as `failed`. It does not update a deleted document. Pint and the existing full test suite pass (40 tests, 105 assertions). The Tika image download had not completed during the local Docker startup attempt, so real-container extraction remains unverified.

### P4 Groq development-provider foundation

Owner selected Groq for development. Added non-secret configuration placeholders and `GroqChatService`, which uses Groq's OpenAI-compatible chat-completions endpoint, bounded timeouts, and fails clearly when no local key is configured. No key was written to the repository. Conversation persistence/controller wiring and a live request remain in progress; rotate the key supplied in chat before setting a replacement in the ignored local `.env`.

### P4 authorization compatibility fix

Laravel 13's generated base controller does not include the legacy `authorize()` helper. Chat and document controllers now use explicit `Gate::authorize()` calls, preserving ownership-policy enforcement. Full tests pass (41 tests, 106 assertions).

### Dashboard navigation map

Updated the authenticated desktop and mobile navigation plus dashboard quick-action cards to link the implemented AI Tutor, Document Library, Subscription, and Profile pages. Updated dashboard copy to distinguish available tools from upcoming quizzes, flashcards, and planner features. Frontend build, full tests, and diff checks pass.

### P4 chat worker payload fix

Status: implemented and targeted-verified; a live Groq request remains environment-dependent.

The 19:17:59 worker log showed Groq returning HTTP 400 because the request contained an empty `messages` array. The pending user prompt had been excluded by the job's `status = complete` history filter. The job now appends the current pending prompt, and exhausted provider failures mark that message `failed` instead of leaving it indefinitely pending. Chat and message factories now provide valid defaults for tests and local fixtures.

Verification:

- `php artisan test --compact tests/Feature/GenerateChatResponseTest.php`: 2 tests passed, 4 assertions.
- `vendor/bin/pint --dirty --format agent`: passed.
- The local `.env` contains a non-empty `GROQ_API_KEY` (the value is intentionally not recorded here).

Operational next step: clear cached configuration and start a fresh queue worker before submitting a new chat prompt. A live provider response is not claimed until that request succeeds.

### P4 assistant Markdown rendering

Status: implemented; visual browser verification remains recommended.

Assistant replies now render an escaped Markdown subset while user content remains escaped plain text. Failed messages display a clear retry-oriented status.

### Repository hygiene

Status: implemented and locally verified.

Added ignore rules for local Codex/Claude skill directories and Boost MCP configuration while retaining reviewable project files such as `AGENTS.md`, `.env.example`, `compose.yaml`, and `docs/`. Existing tracked `CLAUDE.md` remains tracked and was not removed automatically.
