# Data model: school platform and legacy records

Updated 22 September 2026. The first SP1 school foundation, SP2 registry/academic tables, and SP3 attendance, guardian-view, and in-app notice tables are now implemented as additive migrations. Implemented school tables include `schools`, `school_memberships`, `school_role_assignments`, `school_invitations`, `audit_events`, `learner_profiles`, `enrolments`, `academic_years`, `terms`, `class_groups`, `subjects`, `teaching_assignments`, `import_batches`, `import_rows`, `learner_class_memberships`, `learner_activations`, `attendance_sessions`, `attendance_entries`, `attendance_entry_corrections`, `announcements`, and `message_deliveries`. Learner enrolment history now supports audited promotion, dual-school transfer, and deactivation without deleting source records. Remaining school tables below are proposed and not migrated. Existing repository files and P5 records include quiz attempts/answers and flashcard reviews as well as identity, entitlements, usage, chats, documents and generated learning sets. Retrieval chunks/citations remain deferred. The schema tool returned an empty PostgreSQL table listing during planning; local PostgreSQL migration evidence is now recorded, while hosted PostgreSQL schema evidence remains pending.

## Proposed school entities by implementation phase

Names are implementation candidates; finalize exact column types in focused phase migrations. Use UTC timestamps, local school dates/timezone for attendance/terms, foreign keys and integer minor units plus currency for money. Add only the tables used by each implemented slice.

| Phase | Candidate records and core fields | Invariants |
| --- | --- | --- |
| SP1 | Implemented `schools`: name, slug, school type, timezone, status; `school_memberships`: school, user, status, validity dates; `school_role_assignments`: membership, role | Slug unique; membership unique per school/user; roles scoped to membership; platform privileges separate |
| SP1 | Implemented `school_invitations`: school, inviter/invitee, intended email, hashed token, scoped role, expiry, accepted/revoked times; implemented `audit_events`: school/context, actor, event, subject, metadata, time | One-use invitation; verified intended user; no raw credential/private content in audit |
| SP2 | Implemented `learner_profiles`: names, optional preferred name/date of birth, status; implemented `guardian_links`: school/enrolment, guardian user, relationship, verifier, status and effective dates | School admin verifies existing verified user; revoked links deny future reads; payment alone cannot verify guardianship; global learner linkage is not globally browsable |
| SP2 | Implemented `enrolments`: school, learner profile, admission number, status, entry/exit; implemented `academic_years`, `terms`, `class_groups`, `subjects`, `teaching_assignments`, and `learner_class_memberships` | Admission number unique per school; term dates do not overlap within an academic year; active assignments and dated placements reference same-school records; overlapping placements for one enrolment are rejected; promotion closes the prior placement; transfer creates a destination enrolment and retains source history |
| SP2 | Implemented `import_batches`, `import_rows`: school, source checksum, source filename, row number, parsed payload, validation state/errors, commit linkage, and actor/status metadata | Unique batch/checksum and batch/row; staged content is school-scoped; committed row linkage and admission constraints prevent replay duplicates; invalid rows remain uncommitted |
| SP2 | Implemented additive `users` changes: account type, activation metadata, unique learner login identifier, nullable email and deactivation timestamp; implemented optional `learner_profiles.user_id` and hashed `learner_activations` | Existing adults retain email requirements; learner identities cannot inherit adult roles; activation is one-use/expiry-bound; deactivation blocks learner login without deleting institutional history |
| SP3 | Implemented `attendance_sessions`: school, class, teaching assignment, local session date, state, version, actors; implemented `attendance_entries`: session, enrolment, status, marker/time; implemented `attendance_entry_corrections`: entry/session, old/new status, reason, actor/time, session version; guardian portal reads active linked enrolment attendance | One logical session per school/class/assignment/date; one entry per expected dated enrolment; missing/unmarked is not absence; stale updates and reasonless corrections are rejected; correction history is append-only; guardian reads require an active verified link. Alerts remain later |
| SP3 | Implemented `announcements`, `message_deliveries`: school, audience, publication, recipient, channel, logical event, state | Recipients resolved/authorized deliberately; one logical delivery per recipient/channel; sent ≠ read; external channels remain deferred |
| SP4 | Implemented first slice `fee_schedules`, `fee_charge_batches`, `fee_charges`: school/enrolment, optional term/class, batch key, purpose, currency, integer minor-unit amount, posting state | Charge snapshot immutable after posting; batch key idempotent; schedule changes never rewrite posted history; opening balances and fee accounts remain deferred |
| SP4 | `school_receipts`, `receipt_allocations`, `fee_adjustments`, `school_refunds`, `reconciliation_items`: merchant/source reference, verified state, allocation/compensating amounts, approver | No cross-school allocation; net allocations ≤ available receipt; matched verification before posting; corrections trace original entry |
| SP5 | `courses`, `course_enrolments`, `lessons`, `lesson_versions`, `learning_resources`, `content_licences`: owner school or explicit Nova catalogue, subject/grade/outcome, publication/rights | School-private and licensed catalogue are explicit scopes; no ambiguous null-school = public rule; immutable published versions |
| SP5 | `assignments`, `assignment_recipients`, `submission_versions`, `submission_files`, `feedback_releases`: published version, learner, due/cutoff/extension dates, status | Recipient snapshot; later enrollee added deliberately; one effective final submission per allowed attempt; release explicit |
| SP5 | `learning_assessments`, versioned `assessment_items`, `learning_attempts`, `learning_answers`, `marking_records` | New school-learning boundary separate from legacy owner-only quizzes; fixed keys private; manual marks nullable until marked; finalization atomic |
| SP6 | `school_assessment_periods`, `grading_rule_versions`, `school_marks`, `report_publications`, `report_items` | Practice distinct from official school record; report snapshot frozen; absent/exempt/not-assessed distinct from zero; amendments versioned |
| SP7 | `billing_accounts`, `school_contracts`, Nova `payment_orders`, `payment_attempts`, `payment_events`, `access_grants` | Nova payer/account separate from benefiting learner/school; external event unique within provider/merchant scope; tuition references cannot fund Nova grants |
| SP8 | `boarding_houses`, `dormitories`, `beds`, `boarding_allocations`, `roll_call_sessions`, `roll_call_entries`, `leave_requests`, `gate_events` | Date-range occupancy cannot overlap; no physical release without active approval; actual event and recording times separate |
| SP9 | `catalogue_products`, product/content mappings, `learner_purchases`, personal grant/payment linkage | Licensed edition/period snapshotted; independent learner needs no customer-school membership; school overlap handled explicitly |

## Key invariants for implementation

1. Every school-owned foreign relationship is same-school. Use composite uniqueness/foreign keys where practical and explicit transactional validation for temporal relationships; route scoping alone does not protect jobs/imports.
2. Global account/learner identity is not permission to list or merge school records. Guardian verification and school enrolments establish different access relationships; disputed merges require reviewed correction.
3. Effective access is actor authorization AND resource visibility AND valid contract/grant where required. Never use a public school-name input or payer phone as proof of membership/guardianship.
4. Fee receivable balance = posted charges + signed receivable adjustments − net valid receipt allocations. Unallocated receipt credit is shown separately and is not subtracted twice. Refund/reversal operations explicitly reverse affected allocations and record money movement. Formal entry signs/state transitions are frozen in SP4 tests before use.
5. Availability of funds and attempt submission are serialized in database transactions. Keys/constraints make repeats safe; queue uniqueness is not a substitute for posted-record idempotency.
6. Store author/reviewer/version/rights metadata. Content revisions do not alter prior submitted attempts or published school reports. Withdrawn files become inaccessible according to policy without destroying required historical assessment evidence.
7. No cascade from login deletion to posted finance, enrolment or published reports. Deactivate identity/linkage and apply documented retention/anonymization where appropriate; make each delete rule explicit per relation.
8. A whole school's contract cannot share legacy personal chats/documents. Preserve existing `user_id` ownership; sharing requires an explicit authorized publication/import path with rights.
9. Boarding overlaps require a database-backed invariant or serialized checks with a locked stable parent row; querying “no active allocation exists” without a lock is insufficient under concurrency.
10. Index actual access paths: school+status/date, class+term, enrolment+charge date, delivery event+recipient, assignment+learner, and grant beneficiary+period. Verify query plans on realistic synthetic records before optimizing broadly.

## Migration and compatibility

Use additive new migrations, fresh/upgrade tests and bounded restartable backfills. Do not populate old adult records with a fake school or change existing subscription prices/history. The current user-centric entitlement and quiz-generation design stays isolated until a tested compatibility layer or focused reuse refactor is delivered. Use new school-learning assessment tables initially rather than weakening private ownership policies.

Inspect real schema/indexes through Boost before writing migrations; SP0 must resolve the empty schema response. Validate money/time/locking behavior against PostgreSQL, not SQLite alone. Production rollback that drops populated school data is not safe merely because a `down()` method exists; prefer forward fixes and reconcile post-cutover deltas.

## Legacy adult study-assistant model

The remaining table and invariants preserve the original individual-product design. Proposed billing/retrieval/planner entries are not proof of existing tables. Statements about institutions being later or one user subscription apply to that old design and are superseded above for the school release.

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
| Quizzes | `quizzes`: user, title, type, difficulty, question count, generation status, request key; `questions`: quiz_id, type, prompt, options JSON, answer, explanation | Version or freeze questions once attempts exist; answer keys never serialized to unsubmitted student views |
| Attempts | `quiz_attempts`: quiz_id, user_id, status, submitted_at, score; `quiz_answers`: attempt_id, question_id, response, awarded points, feedback | Unique answer per attempt/question; question must belong to attempted quiz; submission idempotent |
| Flashcards | `flashcard_decks`: owner, title, generation status, request key; `flashcards`: deck, front, back; `flashcard_reviews`: card, user, outcome, reviewed_at | Deck/card ownership; retain review history for later spaced repetition |
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
