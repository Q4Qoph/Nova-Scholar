# Decision and risk register

All entries created 16 September 2026. “Proposed” means a planning assumption, not stakeholder approval. Resolve decisions by the indicated phase and record the date, evidence, owner, and consequences.

| ID | Topic | Proposed direction / known fact | Gate |
| --- | --- | --- | --- |
| D01 | Framework baseline | Observed Laravel `^13.17`, PHP requirement `^8.3`, local PHP 8.4.24; retain installed major version | P0 |
| D02 | UI and auth | **Accepted 2026-09-16 for P1:** Blade + Livewire + Alpine; session auth for the first-party web app, with Sanctum deferred until an API/mobile client needs tokens | Revisit before API/mobile work |
| D03 | Database/search | PostgreSQL + pgvector candidate, Redis, private S3-compatible storage | P0 compatibility and retrieval spike |
| D04 | Release scope | All FR1–FR14 by v1; schools after v1 to reconcile source conflict | Owner before P0 exit |
| D05 | Commercial allowances | **Accepted 2026-09-16:** Student KES 299/month and Pro KES 699/month, per BRD. The School KES 5,000–20,000/month range remains institution-specific and deferred with the later institutions module. Quotas, trial/free access, tax treatment, and unit economics remain unresolved. | Resolve remaining items before P4/P6 |
| D06 | Payment lifecycle | Manual monthly M-Pesa renewal; end-of-period cancellation and next-period upgrade; no assumed auto-debit or proration | Owner before P6 |
| D07 | Billing edge cases | Decide refund/reversal handling, grace periods, late payment allocation, failed upgrade behavior, and support authority | Before paid beta |
| D08 | AI provider choices | OpenAI per sources; models, SDK, vector dimensions, context limits and quality thresholds selected by evaluation | P0/P4; verify current official docs then |
| D09 | Large files and OCR | Support text-bearing PDF/DOCX/TXT ≤100 MB; explain image-only files; OCR is additional scope | Extraction spike before P3 |
| D10 | Provider onboarding | Confirm Daraja merchant setup, callback verification/status capabilities, email domain, storage region, and AI account limits | Before integration; production access before paid beta |
| D11 | Privacy and retention | Define consent/notices, data export/deletion, provider processing, retention, and minor access policy | Privacy review before real-user beta |
| D12 | Performance | Propose p95 normal <3 s and mean interactive AI completion <10 s including queue delay; define concurrency and dataset | Before P4 evaluation/P9 load test |
| D13 | Educational scoring | Deterministic objective grading; rubric-based, clearly labeled AI short-answer feedback | Before P5 |
| D14 | Hosting and operations | Benchmark source minimum; choose host, backup destination, region, alert recipient and incident owner | Before paid beta, validate at P9 |
| D15 | Analytics semantics | Define active sessions, engagement, paid conversion denominator, cohort retention and recognized versus collected revenue | Before P8 |
| D16 | Document extraction | **Accepted 2026-09-16:** use Apache Tika as a private internal extraction service for PDF/DOCX/TXT. OCR is deferred. Use the pinned `apache/tika:3.3.1.0` image in local development and pin a digest before shared/production use. | P3 implementation and benchmark |
| D17 | Document allowances | **Accepted 2026-09-16:** Student: 20 documents/1 GB; Pro: 100 documents/10 GB; all uploads max 100 MB. Apply after plan feature allowances are seeded; no free entitlement is implied. | Before P3 enforcement |
| D18 | Development AI provider | **Accepted 2026-09-16:** use Groq's OpenAI-compatible API for development behind a provider boundary; default to `openai/gpt-oss-20b` where available. Do not use free-tier capacity as production evidence. Rotate any key exposed outside local secret storage. | Revisit before production |

## Main delivery risks

| Risk | Planned mitigation | Evidence required |
| --- | --- | --- |
| AI cost exceeds low subscription prices | Per-plan quotas, reservation ledger, bounded tokens/retries, spend alerts | Representative monthly usage cost model |
| Unsupported/hallucinated tutoring | Grounded citations, insufficient-evidence responses, fixed evaluation cases and feedback | Quality review with expected sources and scoring rubric |
| Private data leakage | Policies, owner-filtered retrieval, private storage, scoped caches, safe logging | Cross-user negative tests across each layer |
| Incorrect paid access | Durable events, verified correlation, unique receipts, atomic periods, reconciliation | Duplicate/late/missing callback and concurrent renewal tests |
| 100 MB uploads overload workers | Streaming, archive limits, process limits, separate queues, quotas | Memory/time measurements for representative worst cases |
| Vendor outages/rate limits | Timeouts, bounded backoff, recoverable states, global budget limits | Failure injection and recovery evidence |
| Low adoption/retention | Campus pilot, useful onboarding, feedback and cohort analysis | Pilot outcomes; owner-led marketing plan |
| Hosting loss/downtime | Daily independent backups, restore drills, supervision and external alerts | Timed restore and incident rehearsal |
| Privacy or institutional scope grows | Review before minors, schools, sharing or international launch | Agreed policy and separate scope plan |

Budget, provider pricing, legal obligations, and vendor API details have not been externally verified in this planning task. Confirm current official documentation and obtain appropriate specialist input when those decisions are implemented; do not treat these proposals as legal or financial conclusions.
