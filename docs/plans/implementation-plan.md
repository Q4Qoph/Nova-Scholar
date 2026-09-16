# Implementation plan

Status: in progress. P0–P4 are active; P5–P9 are proposed. Documentation and Boost setup are recorded separately in the [implementation log](../implementation-log.md). This plan authorizes no production deployment or paid service provisioning.

## Delivery strategy

Build a modular Laravel monolith in small vertical slices: migrations, models/policies, service logic, UI, verification, and documentation together. Keep the installed Laravel 13 baseline. Establish usage accounting before enabling real AI calls, and finish payment reconciliation before selling subscriptions. Use fake provider adapters during development, then controlled staging integrations.

Dependency order: P0 → P1 → P2 → P3 → P4 → P5 → P6 → P7 → P8 → P9. Billing design and provider onboarding may begin after P2, while its launch gate still depends on verified entitlements and payment processing. Foundational admin authorization starts in P1; payment administration arrives in P6; P8 completes reporting/moderation.

Do not assign calendar dates until team capacity, scope decisions, and integration access are known. Size each phase after its detailed plan and technical spike. The release gates below are the basis for tracking progress.

## P0 — Foundation and feasibility

Dependencies: owner decisions D01–D05 and development environment access.

- Confirm naming, release boundaries, UI direction, and acceptance criteria.
- Set up PostgreSQL, Redis, private object storage, development mail, and matching CI services; verify a vector extension is available before committing to it.
- Select compatible Livewire/authentication and extraction packages using installed-version documentation; document each new dependency.
- Define configuration keys and secret handling for AI, Daraja, storage, and mail. Separate development, testing, staging, and production credentials.
- Build CI for existing PHPUnit tests and asset builds; add formatting checks when PHP work begins.
- Spike text extraction with representative 100 MB inputs and retrieval with a small evaluation set. Estimate per-active-user AI/storage costs against KES 299/699 plans.

Exit: reproducible development setup and CI, feasible extraction/retrieval prototype evidence, initial budget, provider prerequisites, and agreed plan allowances. Do not claim scalability or financial feasibility from a framework install.

## P1 — Identity and application shell

Requirements: FR1–FR3, authentication email portion of FR13, NFR4/NFR6.

- Create landing/authentication/profile flows and a responsive student dashboard shell with navigation.
- Implement verification, password recovery, profile images/preferences, and protected sessions.
- Introduce student/admin authorization, safe admin provisioning, and policies. Never permit self-assignment of admin privileges.
- Add common empty/loading/error states and accessible reusable UI components.

Exit: registration-to-verified-dashboard flow works; reset/logout/profile tests pass; students cannot access admin actions or another user's records.

## P2 — Plans, entitlements, and usage foundation

Requirements: groundwork for FR5–FR8/FR11/FR14.

- Create configurable plans, price versions, subscription periods, feature allowances, and server-side entitlement checks.
- Implement a usage ledger for requests/tokens, generation, documents/storage, and cost estimates. Reserve allowance atomically before provider calls, settle actual use, release abandoned reservations safely.
- Add provider interfaces and fake adapters; configure request budgets, retry ceilings, timeouts, and global spend alerts.
- Define the no-subscription/trial experience without assuming an approved free plan.

Exit: quota checks hold under concurrent requests, failed jobs do not repeatedly consume allowances, and plan changes cannot bypass metering.

## P3 — Document library and ingestion

Requirements: FR4, groundwork for FR6.

- Build upload/list/detail/delete UI, private storage, content validation and quotas for PDF/DOCX/TXT up to 100 MB.
- Align proxy, PHP, upload component, and storage limits. Stream large files; reject unsafe archives and enforce extraction resource limits.
- Queue validation/scanning, extraction, normalization, chunking, and embedding. Retain page/section locations for citations.
- Show processing states and actionable errors. Handle encrypted, corrupted, unsupported, and image-only documents explicitly; OCR is deferred unless approved.
- Add retry and deletion workflows that remove originals, extracted text, vectors, and scoped caches. Prevent an in-flight job from recreating deleted data.

Exit: representative supported files become searchable; failures are recoverable; size boundaries and cross-user isolation pass; deletion cleans derivatives.

## P4 — AI tutor and document questions

Requirements: FR5–FR6, NFR1.

- Build persistent conversations, messages, document selection, and contextual follow-ups.
- Add prompt orchestration, bounded history/context, user preferences, provider error handling, and usage settlement.
- Retrieve only the user's selected ready documents; return source citations and explain when notes do not support an answer.
- Treat uploaded text as untrusted content; keep provider instructions separate and avoid giving documents tool authority.
- Use queued requests with visible status initially; validate whether streaming improves the measured experience before adding complexity.

Exit / internal alpha: tutor and grounded QA pass fixed quality cases, isolation and quota tests; cancellation/timeouts recover; actual end-to-end latency and cost are recorded.

## P5 — Quizzes and flashcards

Requirements: FR7–FR8.

- Generate validated structured output for topic, difficulty, question count, and optional owned source documents.
- Store quiz questions/options/answer keys separately from student attempt responses. Score MCQ and true/false deterministically.
- Define short-answer rubrics; label AI grading as feedback, retain explanation, and support review of questionable results.
- Add attempt history, results, flashcard decks, and front/back review sessions. Basic review history comes first; adaptive spaced repetition is a later enhancement.
- Validate generated counts, option uniqueness, answer consistency, and ownership before persistence.

Exit: all three quiz types and flashcard study work end to end; malformed outputs fail safely; answer keys are not exposed before submission; duplicate submissions do not inflate progress.

## P6 — M-Pesa billing and paid beta

Requirements: FR11–FR12, billing FR13, payment administration.

- Complete merchant onboarding and sandbox tests; confirm supported Daraja verification/reconciliation controls before writing the provider adapter.
- Build plan selection, normalized phone entry, checkout status, payment history, and receipt views.
- Implement durable payment attempts, callback receipt, reconciliation, idempotent activation, renewal, cancellation, expiry, and audited correction workflows.
- Keep a payment pending until verified; match expected account/order, amount, currency and unique provider references.
- Proposed v1 policy: customer-initiated monthly renewal, cancellation effective at period end, upgrades scheduled for the next paid period. Confirm these rules with the owner before implementation; do not assume automatic M-Pesa debits or prorations.

Exit / paid beta: replayed/out-of-order/missing callbacks, user cancellation, late success, mismatch, and simultaneous renewals pass tests; sandbox payment-to-entitlement works; quotas, basic admin support, monitoring, privacy notices, and recovery procedures are ready before accepting real payments.

## P7 — Planner, progress, and notifications

Requirements: FR9–FR10, remaining FR13.

- Build goals, target dates, available study hours, schedules, reminders, rescheduling, and completion.
- Record active study intervals, quiz performance, card reviews, and daily activity; define timezone and idle-session rules.
- Build progress views and streak computation with idempotent activity events.
- Queue reminders with preferences, deduplication, delivery failure visibility, and timezone-aware scheduling.

Exit: sample activity reproduces displayed totals; timezone boundaries and repeated jobs do not duplicate reminders or streaks; schedule changes update future reminders.

## P8 — Administration and business analytics

Requirements: FR14 and BRD measurement.

- Complete user suspension, subscription inspection, payment reconciliation, moderation reports/actions, and audit trails.
- Report active subscriptions, collected revenue, reversals, usage/cost, acquisition, retention, and paid conversion using documented definitions.
- Restrict access to private learning content to a justified moderation/support workflow with audit records.
- Add useful date filters and aggregates without allowing analytics endpoints to bypass role checks.

Exit: reported amounts reconcile to payment records; suspended users are denied product actions; all sensitive admin mutations are authorized and auditable.

## P9 — Hardening and version 1 release

Requirements: all NFRs and full FR acceptance.

- Run end-to-end student/admin journeys, accessibility/mobile review, retrieval evaluation, security review, and realistic load tests.
- Tune database queries/indexes, queue separation, worker memory/concurrency, provider budgets, and private cache scoping.
- Establish deployment, migration, rollback, incident, reconciliation, and backup/restore runbooks.
- Verify HTTPS, production secret isolation, queue/scheduler supervision, external uptime checks, alert routing, and restored file/database consistency.
- Run a controlled release with a limited cohort, measure feedback and costs, then expand capacity.

Exit / version 1: every requirement has recorded evidence; recovery completes in under two hours; budget and support ownership are agreed; unresolved critical risks block launch.

## Later institution phase

After version 1, define organizations, memberships, school-admin scope, license seats, institution billing, student visibility/consent, and tenant isolation. Revisit data ownership before introducing sharing. Native apps, card payments, voice, collaboration, and marketplace features each require a separate plan and budget.

## Definition of done for every phase

Relevant acceptance checks pass; ownership and failure paths are tested; migrations and operational impact are documented; the UI exposes meaningful status/errors; no credentials or private payloads enter logs; the roadmap, decisions, and implementation log reflect actual results. Passing existing example tests alone never completes a product phase.
