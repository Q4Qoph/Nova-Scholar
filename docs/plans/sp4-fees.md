# SP4-01 fee schedules and charge posting

Status: in progress; SP4-01 schedule and preview-bound charge posting, including PostgreSQL roster-write concurrency protection, are locally verified. SP4-02 manual receipt and allocation workflow is implemented and locally verified. SP4-03 charge-credit request/review, full-allocation reversal, and cash-refund slices are verified against Docker PostgreSQL and synthetic Chrome. SP4-04 statements/reconciliation pass focused Docker PostgreSQL behavior tests and synthetic Chrome acceptance at 390px. Opening balances, live-provider evidence, and NQ03 mixed-load performance remain planned/open.

## Scope

Add school-scoped fee schedules and a controlled charge-batch workflow. A schedule stores the currency and integer minor-unit amount that applies to a school, optional class, and optional term. Posting snapshots the schedule amount into one charge per eligible active enrolment and is idempotent by an explicit batch key. Posted charges are immutable through this slice.

Opening-balance import/sign-off, receipts, allocations, credits, refunds, statements, and reconciliation remain later SP4 slices.

## Dependencies

- SP1 school context, roles, and audit events.
- SP2 active schools, terms, classes, and enrolments.
- PostgreSQL-compatible additive migrations and the existing test factories.

## Acceptance criteria

- A school administrator can create a valid school-scoped fee schedule using integer minor units and an ISO currency code.
- A schedule may target all active enrolments, a class, or a term/class combination without accepting another school's records.
- A charge batch previews the eligible active enrolments and total before posting.
- Posting creates one immutable posted charge per eligible enrolment and records the schedule amount snapshot.
- Posting requires a saved preview snapshot that still matches the current schedule and eligible enrolments; a stale preview must be refreshed.
- Repeating the same batch key returns the existing batch without duplicate charges.
- Reusing a posted batch key for another schedule is rejected.
- Money is displayed in major currency units using the currency's minor-unit exponent while stored and calculated as integer minor units.
- A schedule change does not mutate already-posted charges.
- Teachers and users outside the school cannot create, preview, or post charges.

## Verification

- Focused feature tests for validation, class/term scope, preview, posting, idempotency, immutability, and authorization.
- Full PHPUnit suite, Pint, Vite build, Docker migration, and migration status.

## SP4-02 school receipts and allocations

Status: implemented and locally/browser verified for the manual-entry slice, 23 September 2026. Receipt recording and charge allocation use shared school services through both the canonical workflow and native tenant Filament actions. Focused/full SQLite and isolated PostgreSQL suites passed, as did a PostgreSQL allocation race test and synthetic Chrome receipt/partial-allocation journey. Live provider verification, opening balances, reversal/refund flows, statements, reconciliation and NQ03 mixed-load performance remain open; FI-06 fee preview/post versus roster-write concurrency is verified.

Scope: record confirmed school receipts with their source/evidence and allocate them against same-school posted charges. A receipt may cover multiple learners; an allocation may be partial; total allocations must never exceed receipt funds. Keep school tuition isolated from Nova subscription/payment records. This slice does not initiate or verify live M-Pesa/bank payments, import opening balances, post refunds/credits, or publish statements.

Dependencies: SP4-01 posted charges and fee schedules; school-scoped authorization; additive PostgreSQL-compatible schema; local PostgreSQL verification.

Acceptance: school-authorized staff can record a receipt with currency, integer minor-unit amount, received date, source type/reference and verification actor/time; duplicate references within the defined school/source scope are rejected; allocations reference only posted charges in the same school and currency; partial and sibling allocations work; available receipt balance is shown separately from fee receivables; over-allocation and cross-school allocation fail transactionally; replayed requests do not duplicate receipt/allocation entries; records are auditable and append-only through this slice.

Verification: focused feature tests for source/reference validation, authorization, partial/sibling allocation, duplicate/replay, cross-school/currency denial and over-allocation; full suite, Pint and migration checks; PostgreSQL transaction/locking checks for concurrent allocation. A PostgreSQL-only process race test and full PostgreSQL CI suite pass. Browser acceptance must cover receipt entry, partial/sibling allocation and remaining balances. Do not claim production receipt verification or cutover from synthetic/local evidence.

Implementation defaults for this slice: retain the existing school-admin-only finance policy; support cash, bank, and M-Pesa as manually confirmed sources; generate an internal reference for cash and require a source reference for bank/M-Pesa; store a short evidence note/reference only, with no private proof-file upload; enforce reference uniqueness per school/source; track allocation-reversal behavior in the SP4-03 slice below. These defaults do not represent live provider verification. Revisit only if a pilot school supplies a concrete conflicting workflow.

## SP4-03 charge credits, allocation reversals, and refunds

Status: charge-credit request/review, full-allocation reversal, and cash-refund slices are implemented and verified against Docker PostgreSQL. Credit coverage includes focused feature tests, an independent-connection approval race, migration application, and synthetic Chrome request, self-review denial, approval, rejection, and balance checks. Reversal coverage includes balance, authorization, idempotency, immutable-history, workspace, independent-connection race, and synthetic Chrome acceptance. Cash refund behavior and its Chrome acceptance are recorded in the subsection below.

### Full-allocation reversal

Scope: let an active school administrator reverse a mistaken allocation while preserving the original allocation as history. A reversal releases the full allocated amount back to the receipt's unallocated balance and restores that amount to the charge's outstanding receivable. It does not return cash or alter the posted receipt, charge, or original allocation. Partial reversals, cash refunds, provider integrations, and changes to the existing reviewer policy are deferred.

Dependencies: SP4-01 posted charges; SP4-02 receipts and immutable allocations; the verified SP4-03 credit services, school-scoped finance authorization, audit events, and Docker PostgreSQL test database.

Implemented design: the additive `fee_receipt_allocation_reversals` table links the school and original allocation and records actor, full amount snapshot, unique school-scoped reversal key, reason, and reversal time. A unique allocation constraint permits at most one full reversal. The shared transaction locks school, receipt, charge, and allocation, checks same-school ownership and unreversed state, writes the reversal and audit event atomically, and returns identical key retries idempotently. Charge outstanding and receipt availability use the net allocation after reversals. The tenant Filament fee workspace invokes the shared service and shows append-only reversal history.

Acceptance criteria:

- Only an active school administrator for the allocation's school can reverse it; foreign-school and non-admin access is denied.
- The reversal must include a reason and must use a unique request key; an identical retry returns the existing reversal, while conflicting key reuse is rejected.
- A successful reversal records the full original allocation amount once and leaves the receipt, charge, and original allocation unchanged.
- The charge's outstanding balance increases by the reversed amount, and the receipt's available unallocated amount increases by the same amount.
- An allocation cannot be reversed a second time; concurrent attempts produce one reversal and one audit event.
- The reversal and audit event commit or roll back together, and the UI history distinguishes original allocation from reversal.
- The reversal does not issue or claim a cash refund.

Affected areas: additive reversal migration; allocation/reversal models and relationships; `FeeCharge::outstandingMinor()` and receipt availability queries; a school reversal service; tenant Filament fee action and history view; focused feature and PostgreSQL concurrency tests; SP4 plan, architecture, data model, verification notes, and implementation log.

Verification: Docker PostgreSQL focused reversal suite passed (6 tests, 36 assertions), including authorization, tenant boundaries, reason/key validation, idempotency, balance restoration, immutable records, audit history, workspace history, and reallocation of released funds. The independent-connection PostgreSQL race passed (1 test, 6 assertions). Existing fee, credit, and native fee-page regression suites passed (30 tests, 198 assertions). The dedicated Docker database reports all migrations applied, including the reversal migration. No SQLite verification was run for this slice. Synthetic Chrome acceptance passed in a new isolated Docker PostgreSQL database: a posted KES 100 charge and KES 40 receipt allocation were reversed through the Filament page; the charge returned to KES 100 outstanding, receipt to KES 40 available, original rows remained unchanged, and the reversal/audit records persisted.

Owner decision: full-allocation-only reversal by an active school administrator, without a second reviewer; preserve the receipt, charge, and original allocation; record reason, actor, amount snapshot, and audit metadata; do not issue a cash refund. Synthetic browser acceptance, cash refund authorization/evidence/payout references, and reconciliation remain separate later work.

### Cash refund slice — implemented and locally verified

Status: the additive `school_refunds` migration, model, shared request/review/completion services, and native Fee Operations actions/history are implemented. Focused Docker PostgreSQL refund tests cover authorization, amount bounds, request/review/payout transitions, idempotency, immutable source rows, native workspace actions, and an independent-connection race. Synthetic Chrome acceptance passed: it found a conditional cash payout reference that stayed browser-required, and the reactive selector fix corrected it. Keep this separate from allocation reversal: reversal restores an amount to a receipt; refund records money actually paid back from school funds.

Scope: let authorized school finance staff refund some or all of the currently refundable balance on a manually verified receipt. A refund is a school disbursement, never a Nova subscription/payment and never a reduction to the learner's charge. Do not initiate or verify an external payment provider in this slice.

Owner approval (26 September 2026): use a second active school-admin reviewer, then a separately recorded manual payout-completion step. Retain school-admin-only finance access; support cash, bank, and M-Pesa as manual payout methods; require a reference for bank/M-Pesa and allow it to be blank for cash. No provider is called and no proof file/account details are stored.

Implemented behavior and controls:

- Require an active school administrator and a reason, request key, amount in the receipt currency, refund method, and payout reference/evidence appropriate to that method. Preserve the original receipt, allocations, and reversals unchanged.
- Permit refunds only from receipt funds that are not net-allocated. An allocation must first be reversed before its funds can be refunded. Reject amounts above the receipt's refundable balance.
- Record a refund as a separate append-only school-ledger entry linked to the receipt. Recommended fields: school, receipt, amount/currency snapshot, request key, reason, payout method/reference, requester, reviewer, status, and request/review/payment timestamps. Add an audit event for each state transition. Do not store private proof files or sensitive account details in this slice.
- A second active school administrator reviews the request; the requester cannot approve their own request. Approval reserves the amount against further allocation/refund, rejection releases it, and only a separately recorded completed payout counts as refunded. Recording completion captures who confirmed payout and when. No provider call is made.
- Serialize refund reservation/completion, receipt allocation, and allocation reversal on the same school and receipt locks. Use unique school-scoped idempotency keys and make identical retries safe; conflicting key reuse must fail. Enforce the balance again inside the transaction and with database constraints where practical.
- Update receipt availability so approved refund reservations cannot be reallocated, and show requested, approved/reserved, paid, and available amounts distinctly. Keep refund history visible in the tenant Fees workspace; never silently alter the receipt amount or charge balance.

Acceptance criteria:

- Non-members, non-admin school roles, foreign-school receipt IDs, and self-approval are denied.
- Valid partial and full refunds can be requested only up to the receipt's unallocated net balance; allocation reversals restore refundable balance, while active allocations do not.
- Pending requests do not count as paid. Approval reserves funds; rejection releases them; payout completion reduces remaining receipt funds exactly once. Replayed requests/completions are idempotent, and conflicting key reuse fails.
- Concurrent allocation, reversal, and refund attempts cannot spend the same receipt funds twice; independent-connection PostgreSQL tests prove the one-winner balance invariant.
- Refund and audit records are append-only; the receipt, allocations, reversals, and charges remain unchanged. Charge receivables are unaffected by a cash refund.
- The Filament page displays amount, method, status, reason, actors, dates, payout reference, and resulting available balance without exposing unrelated schools or sensitive proof data.
- Focused feature coverage exercises request, rejection, approval, payout completion, idempotent retry, and balance effects. Synthetic Chrome acceptance covers request, separate-admin approval, cash payout completion with an empty reference, and balance display. No real cash, bank, or M-Pesa transaction is used.

Affected areas: additive `school_refunds` migration and model/receipt relationship; separate school refund request/review/completion services; receipt availability and allocation selector balance queries; tenant Filament fee actions and append-only refund history; architecture, data model, verification notes, roadmap, and implementation log. Authorization reuses the existing school-admin fee policy.

Verification: refund behavior and the independent-connection race passed in the combined focused Docker PostgreSQL run (12 tests total across refund, statement, and reconciliation files). Synthetic Chrome on 28 September covered the request/review/payout journey. After the live-selector fix, the focused refund suite passed (4 tests, 38 assertions), the payout reference became optional when cash was selected, and the synthetic refund was recorded as paid with a null reference. No real payout was made. Cash payout acknowledgement numbering and bursar access remain deferred; the current slice follows the approved school-admin-only scope.

### SP4-04 statements and internal reconciliation — implemented and locally verified

Status: implemented from the approved design. On 27 September 2026, the owner confirmed school-admin-only access for school statements and reconciliation. Focused Docker PostgreSQL behavior coverage and synthetic Chrome acceptance at 390px passed on 28 September 2026.

Implemented state: posted charges and charge-credit adjustments are school-scoped and append-only; receipts, allocations, allocation reversals, and manual school refunds are separate linked records. `FeeCharge::outstandingMinor()` and `SchoolReceipt::availableMinor()` calculate current balances. School statements, stable export references, verified-guardian child statements, CSV exports, print output, and read-only reconciliation are implemented. Chrome acceptance confirmed the balances and child scoping against synthetic records. The case studies informed the bursar's page sequence but do not provide the authoritative ledger; Nova's scoped services and record history remain authoritative.

Implemented scope and behavior:

- Build one read-only statement service from posted charges, approved credits, receipt allocations, and allocation reversals. Show a chronological transaction list, opening balance, period activity, and closing receivable in integer minor units with currency-aware display. Use the school timezone for displayed dates and filter boundaries. Derive statement rows from the existing source records; never write statement totals back into charges or receipts.
- Generate a stable statement reference from the school, enrolment, selected period, and canonical ordered source-entry set. Identical statement contents and period yield the same reference; new ledger entries produce a new reference. Include the reference and generated-at time in print and CSV output. Keep exports request-authorized and school/learner scoped; escape spreadsheet formula-leading values in CSV fields.
- Add a school staff statement path and a guardian child-statement path. Guardian access must resolve an active verified `GuardianLink` for the current adult on each request and scope entries to that one enrolment, so linked siblings cannot leak into each other's statements. Show receipt allocations applied to the child; do not expose household-level unallocated balances or refunds on a child's statement because receipts may cover multiple siblings and a refund does not alter the child receivable.
- Add a read-only reconciliation view grouped by currency for the current ledger. Show posted charges, approved credits, net allocations, outstanding receivables, manually verified receipts, approved refund reservations, paid refunds, and available receipt credit. Check both identities: posted charges minus approved credits = net allocations + outstanding receivables; verified receipts = net allocations + available receipt credit + approved refund reservations + paid refunds. Pending/rejected refunds and any receipt lacking manual verification are excluded from settled totals and shown separately where present. Surface non-zero differences as review items; never silently create a credit, reversal, refund, or edit to make totals match.
- Keep this an internal ledger reconciliation view. Do not add bank-statement imports, provider callback reconciliation, payment verification, general-ledger journals, or settlement claims; those remain separate SP7/provider work. Print output is browser-printable HTML; do not add PDF dependencies.
- Preserve school fee authorization and current tenant context. School statement/reconciliation access stays on the existing school-admin finance boundary; active bursars remain denied. Guardian statements stay limited to verified linked guardians.

Acceptance criteria:

- Statement transaction rows and running/opening/closing balances reconcile to the same posted source entries as the school ledger; approved credits, allocation reversals, and date boundaries are applied exactly once.
- A statement reference is stable for identical inputs/content and changes when the included ledger set changes. Print and CSV exports contain the same reference, amounts, currency, ordered rows, and closing balance; CSV formula injection is prevented.
- A school user without the approved finance-read role, a foreign-school enrolment, an unverified guardian, a revoked/inactive guardian link, or a guessed sibling enrolment cannot view or export the statement.
- A guardian sees only the selected linked child's charges, credits, allocations, and reversals. It does not reveal another child's rows, shared receipt balance, or household refund details.
- Reconciliation reports exact totals by currency and shows pending refund requests separately from available/settled receipt amounts. A paid/approved refund does not change a learner's receivable. Deliberately mismatched synthetic source records are flagged without automatic ledger mutation.
- The reconciliation view is read-only and tenant-scoped; exports are authorized and use the same snapshot/reference as their rendered statement.
- Focused isolated Docker PostgreSQL feature coverage passed as part of the combined suite (12 tests, 93 assertions), covering refund lifecycle/race; school and verified-guardian statement access; linked-child isolation; local date boundaries; stable references; CSV formula safety; reconciliation totals across credits and refund states; discrepancy reporting without source mutation; and admin-only reconciliation page access. Synthetic Chrome at 390px verified school reconciliation, statement, print action, CSV download, guardian linked-child statement/CSV, and a 404 for an unrelated enrolment without exposing learner details. The run caught and fixed nullable cash payout reference handling and malformed `FeeStatementRequest` imports. Pint, PHP syntax checks, route registration, and `git diff --check` passed. No SQLite database was used.

Implemented touchpoints: reusable school statement and reconciliation services under `app/Services/Schools`; scoped school/guardian statement controllers and routes; native school Filament reconciliation; guardian and print Blade views; school CSV rendering; focused PostgreSQL feature coverage; and updates to architecture, data model, verification notes, roadmap, and implementation log. No dependency or schema change was needed for the content-derived reference design.

Owner decision, 27 September 2026: school admins only may view and export school statements/reconciliation. Guardian access remains separately scoped to active verified links.

## Unresolved decisions

- Opening-balance source evidence and school sign-off roles.
- Residence/boarding charge dimensions and mixed day/boarding rules.
- Period locking and amendment/reversal workflow.
