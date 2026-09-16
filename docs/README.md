# Nova Scholar documentation

Nova Scholar is a proposed subscription study assistant for Kenyan university and college students, with institutions and wider markets planned later.

This documentation translates the owner's July 2026 BRD, SRS, and SAD (all version 1.0) into an implementation baseline. Created 16 September 2026. The spelling **Nova Scholar** is used consistently; “Nova Sholar” in the SRS is treated as a typo. These are planning documents, not evidence that product features already exist.

## Reading order

1. [Requirements and scope](requirements.md): consolidated source requirements, release boundaries, and traceability.
2. [Implementation plan](plans/implementation-plan.md): delivery phases, dependencies, tasks, and completion gates.
3. [Architecture](architecture.md): application boundaries, security, and integration flows.
4. [Data model](data-model.md): proposed entities, relationships, and invariants.
5. [Verification and operations](verification-and-operations.md): acceptance checks, deployment, monitoring, and recovery.
6. [Decision register](decisions.md): assumptions and choices requiring resolution.
7. [Implementation log](implementation-log.md): what actually changed and what was verified.

## Repository baseline

The project began as a Laravel skeleton: Composer requires Laravel `^13.17` and PHP `^8.3`; the local CLI runs PHP 8.4.24. P1 adds Breeze Blade authentication, Livewire, Tailwind CSS, verified account/profile flows, and a Nova Scholar dashboard. P2 adds provider-free plans, entitlements, usage accounting, and a read-only subscription page. Learning features, payments, seeded commercial plans, and real provider integrations are not implemented. `.env.example` defaults to SQLite and database-backed queues/cache, while `.env.docker.example` supports the local PostgreSQL/Redis/Mailpit/MinIO stack.

Laravel Boost was installed as required by the original repository instructions. Its generated agent configuration and skills are development tooling, not product functionality.

## Documentation workflow

For each phase, create a focused plan under `plans/` before implementation. Include requirement IDs, scope, decisions, schema/UI/service changes, dependencies, acceptance tests, rollout concerns, and status. Update the roadmap and related documents as work progresses; record actual outcomes in the implementation log. Significant changes to accepted decisions need a dated rationale in the decision register.

Suggested statuses: proposed → ready → in progress → implemented → verified. Use blocked or deferred with a reason where appropriate. A phase is verified only when its acceptance evidence is recorded. The implementation log is authoritative for completed work; the roadmap describes intended work.
