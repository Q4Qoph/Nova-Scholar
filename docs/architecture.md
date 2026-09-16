# Proposed architecture

Status: P1 identity and P2 entitlement/usage architecture implemented; later module boundaries remain proposed. Source: SAD v1.0, refined to cover SRS behaviors and failure paths.

## Application boundaries

Use one Laravel application with Blade, Livewire, and Alpine interactions. P1 uses Laravel Breeze's Blade authentication scaffold and conventional controllers; Livewire is installed for later interactive learning features. Keep HTTP controllers/Livewire components thin: validate input, authorize access, invoke a business service, and present a result. Use Form Requests where appropriate, policies for owned resources, Eloquent for persistence, and jobs for slow/retryable operations.

Use the existing Laravel 13 skeleton conventions rather than recreating every directory in the illustrative SAD tree. Add services under `app/Services/{AI,Documents,Quizzes,Flashcards,Payments,StudyPlans}` as needed, jobs under `app/Jobs`, policies under `app/Policies`, and framework notifications under `app/Notifications`. Provider interfaces belong near the services that use them. Add repositories only when a real persistence abstraction is needed; avoid generic wrappers around Eloquent. These are proposed additions for future implementation, not folders created now.

| Component | Responsibility | Boundary |
| --- | --- | --- |
| Identity and policies | Sessions, verification, profiles, roles, ownership | Every route, download, job, and retrieval operation |
| Entitlement/usage service | Implemented P2: resolves active periods, reserves/releases/settles feature use, and records usage/cost | Before every chargeable provider operation |
| Document service | Private files, processing, chunk lifecycle | Extraction and embedding jobs |
| AI provider adapter | Text generation and embeddings | Provider-neutral inputs/results, timeouts, request IDs |
| Tutoring service | History, retrieval, prompt composition, citations | Never trusts client-supplied ownership |
| Quiz/flashcard services | Structured generation, persistence, attempts/reviews | Validate AI output before use |
| Payment adapter and billing service | Initiation, verification, reconciliation, entitlement periods | Provider payloads cannot directly grant access |
| Planner/progress service | Goals, sessions, aggregation, streaks | Idempotent events with timezone rules |
| Admin/reporting | Authorized operations and aggregate reporting | Audited sensitive actions |

## Storage and authentication

Propose PostgreSQL as selected by the SAD, with a vector extension such as pgvector subject to host support and a retrieval benchmark. Store embeddings per chunk with model/version and dimensions; changing models requires a deliberate reindex. Use Redis for cache, queues, and locks; private S3-compatible storage for uploads; and an external email provider.

For the first-party Blade/Livewire web app, P1 implements session authentication with CSRF protection, Laravel's password-reset and email-verification flows, and verified middleware on the student dashboard. The SAD's Sanctum requirement is reserved for a future API/mobile use case unless an actual token-authentication need emerges. P1 profile images are stored on the private local disk and returned only from an authenticated endpoint for the current account. Never expose long-lived provider credentials to browsers.

## Document processing and grounded answers

1. Authorize upload and reserve storage allowance; validate declared and detected format/size, then store privately with a generated key.
2. Dispatch processing after persistence commits. Track uploaded → validating → extracting → embedding → ready, with failed/deleting alternatives.
3. Scan and extract in resource-limited workers. Reject malformed archives, encrypted/unreadable files and unsafe paths. Report scanned/image-only files as requiring OCR when text cannot be extracted.
4. Normalize and chunk extracted text, preserving page/section location. Embed bounded batches with retry ceilings. Mark ready only when the expected chunks are durable.
5. At question time, authorize the conversation and selected document IDs, filter candidates by owner and ready state, and retrieve bounded relevant chunks. Build context within a token budget.
6. Generate an answer with citations to stored chunk locations. If evidence is insufficient, say so; distinguish general tutoring from document-grounded responses.
7. On deletion, tombstone the document immediately, prevent new retrieval and late jobs, then remove objects/chunks/embeddings/caches. Explain backup retention separately from immediate product deletion.

Uploaded instructions are untrusted source material. They cannot override system instructions, invoke tools, or widen retrieval scope. Render user/AI content with escaping or a safe Markdown pipeline. Keep private response caches scoped to user, source versions, and model configuration; do not share answers across students by question text alone.

## AI execution and cost controls

P2 supplies the local accounting boundary before any provider integration: an active subscription period and active plan feature are required to reserve finite usage. Each reservation uses a unique logical request key, and settlement produces one usage record. Database row locks protect the normal per-period reservation path; verify its behavior under the production PostgreSQL/Redis deployment before using it for chargeable traffic. P2 intentionally provides no provider adapter, plan seed, or commercial access grant.

Persist a pending message/generation and reserve quota before dispatch. Use queued processing and status polling for the first implementation; evaluate streaming separately. Measure submission-to-complete latency including queue wait. Provider errors should produce retryable user-visible states without creating duplicate completed generations.

Bound tokens, conversation history, question/card counts, extraction pages/time, embedding batches, and retries. Record provider request IDs and actual token usage where supplied. Retrying an uncertain external request can incur another charge; distinguish internal job idempotency from provider billing guarantees. Reconcile stale reservations and alert on spend spikes. Select concrete models, SDKs, dimensions, retention settings, and pricing only after current provider documentation and an evaluation in P0/P4.

## Payment processing

Create a local order and pending attempt with a server-calculated price snapshot before calling Daraja. Persist provider references and return a pending state to the student. A successful initiation or browser redirect is not evidence of payment.

Receive callbacks durably, validate format, correlate to the expected attempt, and apply the provider-supported authenticity/verification controls confirmed during the integration spike. Do not assume a callback signature is available. Resolve uncertain results through supported status queries/reconciliation and manual review where necessary.

After verified success, lock the billing records and atomically record the unique receipt plus entitlement period. Duplicate notifications must be harmless; amount/account mismatches must enter review. Handle pending → succeeded/failed/cancelled/expired, with late or contradictory results routed through reconciliation. Repeated renewals must not overwrite or double-extend a period. Notify the customer after commit. Schedule checks for stale pending payments and expose them to administrators.

## Deployment and scaling

Start with the SAD's Ubuntu/Nginx/PHP-FPM environment or an equivalent managed host, PostgreSQL, Redis, object storage, supervised workers, and one scheduler. Confirm resource sizing through load tests. Keep document, AI, and notification work in separate queues so large extraction jobs cannot starve tutoring or payment handling. Ensure webhook receipt remains responsive.

Scale stateless web instances and queue workers independently, centralize sessions/cache, index ownership/date queries, and tune retrieval before adding infrastructure. Add load balancing and database capacity as metrics justify them. The 100,000-user target is a capacity-planning goal, not a reason to introduce microservices initially.
