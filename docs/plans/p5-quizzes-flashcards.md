# P5 quizzes and flashcards plan

Direction update, 21 September 2026: AI generation expansion is deferred. The [school plan](school-platform-implementation.md) adds teacher-authored/versioned practice and manual marking in SP5, reusing appropriate deterministic concepts with new class permissions and concurrency tests. Legacy personal sets and the alpha records below are preserved; no new school functionality is claimed.

Status: implemented for internal alpha; production hardening remains. Started 17 September 2026.

## Scope

Implement the first student-owned quiz and flashcard vertical slice for FR7–FR8: validated generation inputs, persistent quiz/question and flashcard-deck/card records, ownership authorization, and read-only study pages. Generation will use the existing Groq boundary and queued jobs; malformed provider output must fail without partial persistence.

## Dependencies

- P4 chat/provider boundary and provisional usage metering.
- Authenticated and verified users.
- PostgreSQL migrations and existing Laravel queue worker.

## Acceptance criteria

- Students can view only their own quizzes, questions, flashcard decks, and cards.
- Quiz requests validate topic, difficulty, type, and question count.
- Multiple-choice, short-answer, and true/false question types have distinct persisted answer data.
- Generated output is validated before records are committed; malformed output creates no partial quiz/deck.
- Answer keys are not exposed on a future attempt page; attempt scoring is the next P5 slice.

## Attempts and review slice (17 September 2026)

Add quiz attempts/answers and flashcard reviews. Multiple-choice and true/false answers are scored deterministically; short-answer responses are stored for later feedback and are not auto-awarded points. Attempt submission is idempotent, and the attempt view never includes answer keys before submission.

## Deferred decisions

- Exact token allowances and cost settlement for quiz/card generation will reuse the P4 provisional request meter until a dedicated unit policy is approved.
- Adaptive spaced repetition, AI short-answer grading, and document citations remain deferred within P5.

## Verification

Feature tests will cover ownership boundaries, request validation, persistence relationships, and malformed-output rollback. Run focused tests, the full PHPUnit suite, Pint, and `git diff --check`.

The attempts/review slice adds deterministic objective scoring, idempotent submitted attempts, answer-key-safe attempt forms, and persisted flashcard outcomes. AI short-answer grading and adaptive scheduling remain deferred.

## Generation metering slice (17 September 2026)

Quiz and flashcard generation reserve dedicated `quiz_generation` and `flashcard_generation` units before dispatch and settle one unit after successful persistence. Failed jobs release the reservation. Internal-alpha seed allowances are 20 Student / 100 Pro per feature per month and are not production policy.
