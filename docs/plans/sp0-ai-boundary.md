# SP0 school-release AI boundary

Status: implemented and locally verified for the scoped AI job boundary. SP0-01 baseline verification and SP0-03 synthetic demo fixtures remain in progress.

## Scope

Implement SP0-02's server-side boundary for deferred AI execution without deleting or rewriting the legacy individual-user/internal-alpha AI features. The boundary must distinguish the existing personal context from future school or managed-learner contexts, refuse provider execution for the latter by default, and safely release any pending usage reservation when a blocked queued job is handled.

SP0-01 baseline evidence and SP0-03 synthetic demo fixtures remain separate follow-up slices. No school tenancy, learner, guardian, or real child data is introduced by this change.

## Dependencies

- Existing chat, quiz, flashcard, usage-reservation, and queue behavior.
- `docs/plans/school-platform-implementation.md`, especially SP0-02 and the boundary tests.
- No new package or provider credential.

## Decisions applied

- Personal/internal-alpha AI remains available through the current routes while the product boundary is established.
- School and managed-learner AI is denied by configuration default; navigation hiding is not the control.
- A queued job carries its execution context and rechecks the boundary immediately before provider dispatch.
- Blocked jobs do not call Groq, do not create assistant output, and release a pending usage reservation once.

## Implementation

- Add a configuration contract for the school AI flag.
- Add a small AI availability service with named contexts and an explicit denial exception.
- Guard chat, quiz-generation, and flashcard-generation jobs before provider calls.
- Preserve the existing personal job constructor behavior for backwards compatibility; future school dispatchers must pass the school context explicitly.
- Add feature coverage for blocked direct job handling and reservation recovery, plus regression coverage for personal execution.

## Acceptance criteria

- Personal chat jobs retain current provider-dispatch behavior.
- A school-context chat job cannot call the provider and leaves no assistant message.
- A blocked school-context chat job releases a pending `ai_chat` reservation.
- A school-context quiz or flashcard job cannot call the provider and releases its pending reservation.
- The default school AI configuration is disabled, and enabling it is an explicit environment/configuration change.
- Existing P1–P5 tests remain green.

## Verification

- Run focused AI boundary tests.
- Run the full PHPUnit suite.
- Run Pint for modified PHP files.
- Run `git diff --check`.
- Update architecture/operations notes if the implemented boundary differs from the proposed design.

## Unresolved decisions

- The final school-context value object and authorization source will be completed with SP1 school memberships.
- Whether any school release will ever enable AI remains deferred pending product, privacy, safeguarding, and cost review.
