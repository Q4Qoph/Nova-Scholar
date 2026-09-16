# P2 plans, entitlements, and usage foundation plan

Status: implemented and locally verified. Approved individual-plan prices are seeded; allowances, tax, trial policy, and production concurrency verification remain before P2 can be closed.

## Scope

Implement the provider-independent domain foundation required before documents, AI generation, and billing: a configurable plan catalogue, historical price records, feature allowances, subscription periods, entitlement resolution, and a durable usage reservation/settlement ledger. Add a verified-user subscription page that explains the current access state and displays configured public plans. It is read-only: P2 does not initiate M-Pesa, sell a plan, grant paid access, or create a trial automatically.

## Decisions and boundaries

- D05 accepts the BRD individual monthly prices: Student KES 299 and Pro KES 699. P2 seeds those public catalogue entries with a 1 July 2026 UTC effective date. The School KES 5,000–20,000/month range remains deferred because it is a negotiated institution range, not one fixed price.
- A subscription period is the source of truth for access. A user has an entitlement only when a current `active` period is attached to an active subscription and an active plan.
- Feature allowances are non-negative integer quantities. `null` means unlimited; absence of a feature means denied. Usage uses named feature codes rather than a premature product-wide enum so later modules can introduce their own explicitly documented codes.
- Usage is never inferred solely from a counter. A unique request key creates one reservation, which can later be settled with actual quantity/cost or released. Provider calls stay out of database transactions.
- P2 uses database locking for per-user/per-feature reservation consistency. Redis queue/cache integration and external provider adapters remain later work.
- There is no self-service plan mutation and no admin catalogue UI in P2. A protected operations workflow will be added with billing/admin work; tests create catalogue data directly through factories.

## Data model

| Table | Purpose and key constraints |
| --- | --- |
| `plans` | Stable plan code, name, description, active/public flags; code is unique |
| `plan_prices` | Immutable price snapshots in integer minor units and ISO currency; dates support future price versions |
| `plan_features` | Per-plan feature code and allowance; unique plan/feature pair |
| `subscriptions` | User-to-plan lifecycle record; active status is required for an entitlement |
| `subscription_periods` | Bounded entitlement window tied to a subscription; indexed for current-user lookup |
| `usage_reservations` | One logical request reservation, unique by request key; pending/settled/released state and expiry |
| `usage_records` | Settled, append-only usage/cost evidence associated with a reservation |

## Service behavior

1. Resolve the user’s latest current active period with its subscription, plan, and features.
2. Return an explicit denied result when there is no eligible period, no feature, or no remaining allowance.
3. Under a transaction, lock relevant pending reservations for the same user/feature/period, calculate reserved quantity, and create/reuse the request-key reservation.
4. Settle a reservation exactly once; persist actual usage in a usage record. Release abandoned pending reservations without generating usage.
5. Keep the public page to catalogue/status read operations; it must not expose private billing records or enable access changes.

## Acceptance criteria

- Configured plan codes, feature codes, and request keys cannot duplicate where their invariants require uniqueness.
- An active period grants only its configured feature allowance; inactive, expired, or missing periods deny access.
- Repeating the same reservation request key returns the original reservation without consuming more allowance.
- Concurrent-compatible reservation logic never allows the sum of pending reservations to exceed a finite allowance.
- Settlement creates one immutable usage record; release makes capacity available and cannot create a record.
- A verified student can view their own access state and public plans; guests and unverified students cannot access the page.
- Automated feature/service tests, formatting, migrations, and asset build pass.

## Out of scope

M-Pesa/Daraja, checkout, payment callbacks, renewals/cancellation UI, tax/invoice logic, a free plan, feature allowances, provider calls, queues, Redis locks, spend alerts, and admin catalogue management.

## Rollout notes

The migrations are additive and contain no commercial data. Do not use the services to gate a production feature until D05 establishes an approved catalogue/allowance policy and a controlled entitlement-granting workflow exists. Before P4, benchmark and revisit locking with the production PostgreSQL/Redis configuration.

## Implementation result

Implemented 16 September 2026: seven initial migrations/models/factories, `EntitlementService`, `UsageService`, and the verified-user read-only subscription page. The services use active-period/active-plan checks, unique request keys, transactions, and row locks. Follow-up catalogue work seeds the accepted Student KES 299/month and Pro KES 699/month public plans, with an explicit price interval/effective date. No payment integration, feature allowance, or entitlement has been granted.
