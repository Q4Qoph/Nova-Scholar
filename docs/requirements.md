# Requirements and scope

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
