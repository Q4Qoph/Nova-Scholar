# Proposed data model

Status: P1 implements the `users` role, private profile-photo path, and learning-preferences fields. P2 implements the plan, entitlement, and usage tables below; the remaining areas are logical design for future migrations. Choose exact column types/indexes during each phase; use foreign keys, unique constraints, UTC timestamps, explicit statuses, and factories. Use integer minor units plus currency for money, never floating-point amounts.

| Area | Proposed tables and key fields | Relationships / constraints |
| --- | --- | --- |
| Identity | `users`: name, email, password, verified_at equivalent, profile image key, role, timezone, learning preferences, suspended_at | Unique email; use Laravel's existing email verification field; no self-assigned admin role |
| Catalog | `plans`, `plan_prices`, `plan_features`: code, name, currency, amount, effective dates, allowances | Stable plan identity and versioned prices; never rewrite historical payment amounts |
| Access | `subscriptions`: user_id, plan_id, status, cancellation fields; `subscription_periods`: subscription_id, starts_at, ends_at, payment_id | One effective entitlement policy per user; unique payment-funded period; serialize renewal updates |
| Billing | `payment_orders`, `payment_attempts`: user, price snapshot, expected amount/currency, status, provider request IDs, receipt, verified_at | Multiple attempts may belong to an order; provider receipt and relevant request IDs unique |
| Provider events | `payment_events`: attempt_id, provider event key/hash, received_at, processing status, minimal protected payload | Replay detection; minimal retention; unknown references remain reviewable |
| Usage | `usage_reservations`, `usage_records`: user, feature, request key, quantity, token counts, model, estimated cost, status, expiry | Unique logical request key; atomic allowance reservation; actual settlement separate from estimates |
| Documents | `documents`: user, title, private disk/key, detected type, byte size, checksum, processing status/version, failure code, deleted_at | Owner-scoped queries; checksum does not imply permission to reuse another user's file |
| Retrieval | `document_chunks`: document_id, index, text, page/section, content hash, vector, embedding model/version | Unique document/version/chunk index; vector dimensions fixed per model/index strategy |
| Conversations | `chats`: user_id, title; `chat_documents`: chat_id, document_id; `messages`: chat_id, role, content, status, request key, token usage | Authorized document selection; message roles restricted; generation keys unique |
| Citations | `message_citations`: message_id, document_id/chunk_id, locator, source version | References must belong to the chat owner; deleted sources shown as unavailable |
| Quizzes | `quizzes`: user, title, topic, difficulty, generation status; `questions`: quiz_id, type, prompt, rubric/answer; `question_options`: question_id, label, text, correctness | Version or freeze questions once attempts exist; answer keys never serialized to unsubmitted student views |
| Attempts | `quiz_attempts`: quiz_id, user_id, status, submitted_at, score; `quiz_answers`: attempt_id, question_id, response, awarded points, feedback | Unique answer per attempt/question; question must belong to attempted quiz; submission idempotent |
| Flashcards | `flashcard_decks`, `flashcards`: owner/deck, front, back, source; `flashcard_reviews`: card, user, outcome, reviewed_at | Deck/card ownership; retain review history for later spaced repetition |
| Planning | `study_plans`, `study_goals`, `study_tasks`: user/plan, target dates, topic, scheduled start/end, completion | Owner-scoped; enforce valid time ranges; rescheduling preserves completion history |
| Progress | `study_sessions`, `learning_events`: user, source, start/end, active duration, event key, occurred_at | Deduplicate event keys; reject impossible durations; derive streaks using user's timezone |
| Notifications | Framework `notifications` plus reminder/delivery records as needed | Unique recipient/event/channel/scheduled occurrence prevents repeated delivery |
| Administration | `moderation_reports`, `audit_logs`: actor, target, action, reason, timestamp | Restrict sensitive reads; retain a trace of billing/moderation/admin changes |

## Design invariants

- A user's effective subscription is derived from valid subscription periods, not an independently editable `users.subscription_status`. Cache only with reliable invalidation.
- No public filesystem paths. Access to downloads, chunks, vectors, messages, attempts, and background jobs must be scoped to an authorized owner.
- A financial event is append-only in intent: corrections/reversals receive explicit records and audit entries. Do not erase payment history when a subscription ends.
- Keep large extracted content in chunk records or private extraction artifacts rather than repeatedly loading a monolithic `documents.extracted_text` field.
- Distinguish generated learning content from attempts/reviews. This supports accurate progress and prevents regeneration from rewriting past grades.
- Database transactions must cover local invariants, not slow network calls. Dispatch jobs after commit and use request keys to recover from crashes.
- Account/document deletion must define cascading cleanup and retention exceptions before launch. Retained financial/audit records should contain only necessary data; retention periods need owner/privacy review.
- Institutions later require `organizations`, `memberships`, `licenses`, and seat assignments plus explicit visibility policies. Do not add a global school-admin bypass now.

## Index and migration approach

Index foreign keys and common owner/status/date filters; enforce uniqueness for emails, receipts, request keys, chunk positions, and attempt answers. Benchmark the vector index with owner filtering and representative document sizes. Avoid hardcoding one embedding model without a migration path.

Introduce tables with the phase that uses them, not a single speculative mega-migration. Test migrations against PostgreSQL in CI; SQLite does not verify vector queries, PostgreSQL constraints, or locking semantics. Document data backfills and reversible rollout steps before schema changes reach production.
