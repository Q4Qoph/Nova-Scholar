# Requirements and scope

## Current baseline — school administration and e-learning

Updated 21 September 2026. **Owner-accepted direction:** administration for day, boarding and mixed schools, with included e-learning for their students; a small independent learning subscription for students at non-subscribing schools. AI is deprioritized and excluded from the initial school release dependencies. One-developer planning baseline; no school partner confirmed. Detailed requirements below operationalize that direction and remain unimplemented until phase evidence is recorded.

The previous adult-AI source baseline is retained below as historical traceability. Its “schools later,” adult-only launch and all-FR-by-v1 boundaries are superseded by this section and D21. Existing user data, code and accepted historical subscriptions are preserved. No new prices, paid providers or deployment are authorized merely by documenting the plan.

### Business outcomes and buyers

Schools buy dependable administration and included teaching workflows; families at non-customer schools buy learning access without institutional administration. Success measures: reliable statements, reduced staff task time, usable assignments/feedback, active school use, school renewal, independent purchases/renewals and contribution after service/content costs. The old consumer sign-up/session targets are not acceptance gates for the school release.

School tuition/boarding fee receipts belong to the school and are never Nova revenue. Optional direct learning purchases cannot be prerequisites for required schoolwork. School or personal subscriptions do not grant rights to other schools' content. New prices, limits, grace/exit and overlap-credit policy must be settled before paid offers, rather than inferred from earlier research examples.

### Active release boundaries

- R0: synthetic internal demo; safe foundation work can start without a pilot partner.
- R1: controlled day-school pilot after SP0–SP7: registry, attendance, fee subledger, parent communication, non-AI lessons/assignments, marking, selected school report template, institution billing and recovery readiness.
- R2: boarding/mixed pilot adds residence capacity/allocation, roll call and leave/release/return with human escalation and outage procedures.
- R3: independent Learn beta adds guardian-led purchase, licensed content and personal access lifecycle for learners at non-subscribing schools.
- Deferred: AI tutoring/generation/grading/retrieval, all-grade content, full accounts/payroll, clinic, transport GPS, biometric attendance, open marketplaces, native apps, live video and offline synchronization. Boarding and independent learning are staged intended scope, not indefinite deferrals.

### Functional traceability for the new direction

| ID | Required behavior | Phase | Acceptance evidence |
| --- | --- | --- | --- |
| NS01 | Deliver all new school/learning workflows without AI; contain deferred legacy generation | SP0 and every release | No provider call from school routes/jobs with AI disabled; legacy data preserved |
| NS02 | Schools, scoped staff roles, membership lifecycle and controlled provisioning | SP1 | Cross-school lists/records/files/jobs denied; role/invitation replay and revocation tests |
| NS03 | Learner records, enrolment, guardians and managed child access | SP2 | No child email required; relationship verification/revocation; no inherited adult privileges |
| NS04 | Academic years, terms, classes, subjects, teaching assignments and progression | SP2 | Historical enrolments/results survive term changes and transfers |
| NS05 | Validated staged imports and authorized exports | SP2 | Repeat import is idempotent; errors/duplicates reviewed; exported rows correctly scoped |
| NS06 | Attendance sessions, corrections, exceptions and confirmed alerts | SP3 | Unmarked differs from absent; race/retry and stale-alert cases pass |
| NS07 | Fee schedules/charges, verified receipts, allocations, adjustments, statements and reconciliation | SP4 | Exact balances; sibling/partial/refund/reversal/concurrent cases pass; no tuition in Nova revenue |
| NS08 | Guardian portal, scoped notices and delivery status | SP3, SP6, SP7 | Only verified linked children/released records visible; failed delivery visible and retries deduplicated |
| NS09 | Teacher-authored courses, lessons and authorized resources | SP5 | Class-specific publication, content version/rights and withdrawal enforced without extraction/AI |
| NS10 | Assignments, draft/final submissions, deadlines/extensions and feedback | SP5 | Durable acknowledgement; cutoff/late/retry/resubmission rules; released feedback only |
| NS11 | Manually authored objective practice plus manually marked open work | SP5 | Hidden answer keys; deterministic scoring; no final zero for ungraded work; concurrent finalization safe |
| NS12 | Learner/teacher progress and reviewed independent catalogue | SP5, SP9 | Completion/submission/marking distinguished; content licence/coverage verified before personal sale |
| NS13 | Teacher assessment records, moderation and published report versions | SP6 | Approved pilot grading rules, missing/zero distinction, immutable publication and audited amendments |
| NS14 | Nova school contracts and personal subscriptions with explicit access grants | SP7, SP9 | Valid school cover includes learning; independent purchase targets beneficiary; no double charge on overlap |
| NS15 | Boarding allocation, roll call, leave, actual release/return and escalation | SP8 | No overlapping bed assignment; lawful scoped access; physical event/approval distinct; outage drill passes |
| NS16 | Verified payment flows, deduplication and reconciliation | SP7, SP9 | Recipient/merchant/amount matched; wrong/missing/late/reversed events handled; manual verification audited |
| NS17 | Audit, privacy, restricted support access, lifecycle/retention and data portability | SP1–SP9 | Sensitive mutations traceable; login deletion cannot erase retained institutional records; exports isolated |
| NS18 | Operational onboarding, help, monitoring, recovery and controlled cutover | SP0, SP7–SP9 | Signed import reconciliation, role training, restore evidence, support owner and exit process |

Target navigation: School overview; Learners/Guardians; Academics; Attendance; Fees; Learning; Reports; Communications; Boarding (enabled schools); Settings. Teacher: assigned classes, register, courses, marking. Guardian: linked children, notices, statements, released progress. Learner: courses, tasks, feedback. Platform operator: schools, contracts, scoped support/audit. Native tenant overview, registry/detail, academic structure, attendance, fee and communications pages are implemented and locally verified. The overview shows the selected school and current scoped membership, with an admin-only staff-management entry. Browser parity and release gates remain open; this list remains a target, not a claim that all screens exist.

### Non-functional requirements

| ID | Requirement | Proposed acceptance target |
| --- | --- | --- |
| NQ01 | Authorization and privacy | All school/relationship boundaries tested across HTTP, jobs, files, exports, cache and support; zero unresolved critical access defects; review before real child data |
| NQ02 | Integrity and recoverable side effects | Integer money, immutable posted corrections, idempotent finance/submissions, PostgreSQL concurrency tests; exact reconciliation before cutover |
| NQ03 | Performance and availability | Initial workload: 3 schools × 600 learners, 30 simultaneous staff and 60 learner sessions; p95 ordinary requests <3 seconds, monthly external uptime target 99.5%. Validate and revise workload before contracts; no AI latency gate |
| NQ04 | Recovery and continuity | Proposed production database recovery point ≤1 hour, files ≤24 hours, tested recovery <2 hours; finance reconciliation and boarding paper/phone fallback required. Host capability/cost must be established before commitment |
| NQ05 | Usability, maintainability and observability | Responsive low-data/keyboard/shared-device flows; explicit pending/failed states; existing Laravel stack and meaningful tests; measurable task time/support burden |

An initial one-hour database recovery point is a proposed strengthening of the old daily-backup baseline because receipts and school records are now operationally important. It is not a claim that backups or point-in-time recovery are configured. File recovery gaps must be identifiable and reflected as unavailable attachments, with replay/re-upload procedures. Exact service commitments need owner/host validation before sale.

Release evidence is recorded against NS/NQ identifiers in the implementation log. Required real-data safeguards and payment readiness block their corresponding live releases; they do not block synthetic-data development. See the [active implementation plan](plans/school-platform-implementation.md).

## Historical July 2026 source baseline — superseded release scope

The remaining sections preserve the original adult study-assistant interpretation. FR/NFR IDs remain useful for legacy regression and historical decisions; they do not require completing AI before the school platform. Current NS/NQ requirements take precedence where scope differs.

## Source baseline

This is a consolidated interpretation, not a verbatim archive, of three owner-supplied documents dated July 2026: SRS v1.0, BRD v1.0 (prepared by Fredie Obiero), and SAD v1.0. Original requirement identifiers are retained below. Additions are design proposals and are identified in the [decision register](decisions.md).

Business purpose: reduce the time students spend searching notes, improve understanding and study organization, and provide affordable personalized learning. The revenue model is monthly subscriptions: Student KES 299, Pro KES 699, and future School KES 5,000–20,000. Feature allowances, taxes, trials, refunds, and school pricing bands remain undecided.

Business targets: 5,000 users in year one, 20% paid conversion, average session duration above 15 minutes, monthly retention above 70%, and positive cash flow by year two. These are targets, not forecasts. Define a qualified active session, the conversion denominator, and the retention cohort before reporting them. Track acquisition, activation, engagement, paid conversion, renewal, churn, collected revenue, and AI/storage costs without recording private learning content in analytics.

## Release boundaries

- Internal alpha: identity, private documents, metered AI tutoring and document questions.
- Paid beta: alpha plus quizzes, flashcards, confirmed M-Pesa payments, entitlements, and essential administration.
- Version 1: all FR1–FR14, including planner, progress, notifications, full administration, and operational acceptance.
- Later: school administration and licenses, cards, voice tutoring, native apps, multiplayer study rooms, exam simulator, recommendation engine, live tutoring marketplace, real-time collaboration, and international expansion.

The SRS lists School Administrator as a user class but also calls institution management a future enhancement. The proposed resolution is to defer institution workflows and tenant permissions until after version 1; owner confirmation is needed before treating this as an accepted scope change. Initial users are adult university/college students. High school/minor onboarding requires a separate privacy and consent review before expansion. Offline learning, video conferencing, and cryptocurrency payments are excluded from the initial release by the BRD.

## Functional traceability

| ID | Required behavior | Phase | Acceptance evidence |
| --- | --- | --- | --- |
| FR1 | Register with name, unique valid email, password of at least 8 characters | P1 | Valid registration succeeds; invalid/duplicate email and short password fail |
| FR2 | Login, logout, password reset, email verification | P1 | Session lifecycle, reset expiry, verification, and access restrictions tested |
| FR3 | Edit name, photo, password, learning preferences | P1 | Valid changes persist; image validation and ownership enforced |
| FR4 | PDF, DOCX, TXT uploads up to 100 MB; store, extract, embed | P3 | Each format, size boundary, invalid file, processing failure, and retry tested |
| FR5 | AI concept explanation and contextual follow-up chat | P4 | History and preferences used safely; failures recover; users cannot read others' chats |
| FR6 | Answer from uploaded notes using retrieved content | P4 | Ready documents only; ownership filtering, citations, and insufficient-evidence behavior verified |
| FR7 | Topic/difficulty/count-based MCQ, short answer, true/false quizzes | P5 | Valid generation, attempt submission, grading, feedback, and history tested |
| FR8 | Flashcards with question/front and answer/back | P5 | Generated cards persist and can be studied by their owner |
| FR9 | Timetable, reminders, goals | P7 | Conflict handling, timezone-aware schedule, completion, and reminders tested |
| FR10 | Study time, completed quizzes, scores, streaks | P7 | Reproducible totals; idle time and duplicate events excluded |
| FR11 | Purchase, upgrade, cancel, renew subscriptions | P6 | Confirmed payment grants correct period; expiry/cancellation/upgrade rules tested |
| FR12 | M-Pesa payment processing; cards later | P6 | Initiation, failure, timeout, replay, reconciliation, and amount mismatch tested |
| FR13 | Email, subscription reminders, study reminders | P1/P6/P7 | Delivery retries, preferences, and deduplication tested |
| FR14 | Admin users, subscriptions, payments, revenue, usage, moderation | P8 | Role restrictions, reconciled reporting, and audited actions tested |

Required pages: landing, login, registration, dashboard, chat, document library, quizzes, flashcards, study planner, subscriptions, and admin dashboard. Add password reset/verification/profile screens to support FR2–FR3. All student screens need mobile layouts and loading, empty, error, and quota-exhausted states.

## Non-functional traceability

| ID | Source requirement | Delivery and measurement |
| --- | --- | --- |
| NFR1 | Normal responses under 3 seconds; AI under 10 seconds average | P4/P9: propose normal-request p95 <3 s and mean complete interactive AI response <10 s at a declared load; report queue delay separately and in end-to-end latency |
| NFR2 | 99.5% availability | P9: external uptime monitoring, monthly availability report, outage alerts and runbook |
| NFR3 | Initially 1,000 users; target 100,000+ | P9: agree active/concurrent workload, benchmark it, capacity plan; registered users alone do not define throughput |
| NFR4 | HTTPS, hashing, CSRF, rate limits, role permissions | Every phase: framework controls, policies, secrets management, security and isolation tests |
| NFR5 | Daily backup; recovery under 2 hours | P9: database/files backup and timed restore drill; proposed RPO ≤24 h, RTO <2 h |
| NFR6 | Responsive, mobile-friendly, simple navigation | Every UI phase: small-screen, keyboard, focus, labeling, contrast and usability checks |
| NFR7 | Modular architecture, service classes, API abstraction | Every phase: thin request handlers, business services, provider adapters, isolated tests |

The source minimum server is 4 CPU / 8 GB RAM / 100 GB SSD. Treat this as a starting estimate to benchmark, not a guarantee, especially for concurrent 100 MB extraction jobs. Internet access, cloud services, email, AI provider availability, and payment-provider availability remain external dependencies. API costs/rate limits, hosting/storage limits, budget, adoption, competition, and privacy are ongoing delivery risks.

## Approval gates

BRD approval requires accepted scope, validated financial and technical feasibility, an initial budget, and an accepted roadmap. SAD approval requires demonstrated functional coverage, security, performance, scalability, and extensibility. Version 1 acceptance requires all functional checks above and the operational release gate in [verification and operations](verification-and-operations.md). None of these business approvals is implied by creating this documentation.
