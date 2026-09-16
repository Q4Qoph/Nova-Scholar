# Verification and operations plan

Status: proposed release requirements. No production environment or product verification is claimed.

## Verification layers

- Unit tests: quota calculations, subscription periods, objective scoring, chunk boundaries, progress aggregation, and streak timezone rules.
- Feature tests: authentication, validation, ownership policies, private downloads, generation lifecycle, attempts, reminders, admin restrictions, and payment state transitions.
- Integration tests: PostgreSQL constraints/vector retrieval, Redis locks and concurrent reservations, private object storage, queued retries, provider adapter contracts. Use fakes in routine CI; never spend money or send real payment requests by default.
- Staging checks: controlled AI quality/cost evaluation, Daraja sandbox checkout and callback/reconciliation flow, mail delivery, representative file ingestion, scheduler and worker supervision.
- Browser/manual checks: landing → registration → verification → upload → grounded chat → quiz/cards → subscription → planner/progress, plus admin support. Check small screens, keyboard navigation, focus, labels, and understandable failures.

Use the [requirements matrix](requirements.md) to record evidence per FR/NFR in each phase's implementation record. Test existing behavior affected by a change as well as its new behavior.

## Critical failure cases

1. Student A cannot read, select, download, retrieve, delete, or generate from student B's data, including guessed identifiers and background jobs.
2. Missing verification, suspension, expired access, exhausted quota, and concurrent requests cannot bypass entitlement checks.
3. Corrupt/oversized/encrypted files, archive bombs, parser failure, embedding failure, retry exhaustion, and deletion during processing produce safe final states.
4. Prompt injection in uploaded notes cannot widen access or expose instructions/secrets. Missing evidence yields an honest response; citations resolve to authorized sources.
5. Provider timeout/rate limiting, invalid generated JSON, duplicate jobs, queue crashes, and stale reservations do not silently double-consume allowances or strand the UI.
6. Payment duplicates, spoofed/malformed inputs, wrong amounts, unknown references, missing callbacks, late success, status contradictions, and repeated renewals do not grant unverified access.
7. Quiz answer keys remain hidden until allowed; concurrent submission and repeated activity events do not inflate scores/time/streaks.
8. Admin actions require authorization and create useful audit records without logging passwords, full private prompts, files, or unnecessary phone/payment details.

## Performance and quality gates

Prepare a workload specifying concurrent users, requests per second, document sizes/counts, chat context lengths, provider/model, cache conditions, and worker resources. Proposed acceptance: normal request p95 below 3 seconds; average complete interactive AI response below 10 seconds. Also report p95 AI latency, queue age, error rate and saturation. Fast acknowledgement or first token alone does not satisfy complete-answer latency.

Use fixed representative questions with expected source passages, answer rubrics, unanswerable examples, and malicious document instructions. Set numeric grounding/citation/accuracy thresholds during P4 with the owner; run the same set when prompts, models, chunking or retrieval change. Fail launch on critical isolation failures regardless of quality averages.

Load-test the initial agreed active workload corresponding to 1,000 registered users. Document capacity limits and scaling triggers toward 100,000+; do not equate registered accounts to simultaneous AI calls. Include large document jobs while measuring tutoring latency.

## Deployment checklist

- Separate environment secrets, provider accounts and private buckets; HTTPS and debug disabled in production.
- Build assets and install locked production dependencies; keep development tooling out of the public runtime where feasible.
- Back up before migrations; test migrations and rollback/forward-fix procedures against production-like data.
- Supervise PHP/web processes, Redis-backed workers, and a single effective scheduler; restart workers on deployment and verify health.
- Check storage permissions, short-lived authorized downloads, upload limits, mail configuration, webhook reachability and reconciliation jobs.
- Smoke-test login, document readiness, an AI request, billing status, and admin access. Use controlled provider transactions only with appropriate production authorization.
- Assign an incident/support owner, alert destination, rollback trigger, and user communication process before launch.

## Monitoring and recovery

Monitor monthly 99.5% availability externally, HTTP error/latency rates, worker health, queue age, failed jobs, AI tokens/cost, payment pending age/mismatches, storage usage, database health, and reminder failures. Correlate events with internal request IDs and minimally necessary provider references.

Back up database and uploaded files daily and system snapshots weekly per SAD; encrypt backups, keep an independent destination, and monitor success. Proposed recovery point objective is at most 24 hours of loss, subject to owner approval; recovery time must be under 2 hours. Decide whether payment risk requires more frequent database recovery points.

Before launch, restore into an isolated environment and time the process: restore configuration securely, database, and files; validate record/object consistency; rebuild caches/indexes as needed; reconcile payment states; smoke-test; then record elapsed recovery time. Prevent restored jobs from sending old reminders or replaying charges. Repeat drills periodically and after infrastructure changes.

Create incident runbooks during P9 for provider outage, stuck ingestion, failed workers, callback backlog, suspected data exposure, database recovery, and deployment rollback. Specify actions, responsible owner, evidence to preserve, and recovery checks. The existence of a backup job alone is not proof of recoverability.
