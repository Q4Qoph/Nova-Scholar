# P4 AI tutor and document questions plan

Direction update, 21 September 2026: further AI delivery is deferred under the owner-accepted administration/e-learning pivot. Existing alpha status and records below remain historical evidence. See SP0 in the [school plan](school-platform-implementation.md) for planned containment; this documentation has not disabled any running feature.

Status: implemented for internal alpha; production exit deferred. Started 16 September 2026.

## Reliability follow-up (16 September 2026)

The first live worker run reached Groq successfully but sent an empty `messages` array because the pending user prompt was excluded from the history query. The job will include the current prompt, mark exhausted provider failures as `failed`, and add regression coverage for both behaviors.

## Scope

Build the provider-neutral conversation foundation for FR5/FR6: owned chats and messages, a verified-user tutor page, private document selection, queued generation lifecycle, and request-key usage reservations. The first implementation will not call OpenAI until D08 selects a model, credentials, token limits, and evaluation threshold.

## Safety boundaries

- A chat and every selected document must belong to the signed-in user; cross-user resources return not found.
- Uploaded document text is untrusted reference material, not instructions. It cannot alter the system prompt or grant tools/access.
- Messages persist `pending`, `complete`, `failed`, and cancellation-safe request keys. A provider failure is visible but does not create a duplicate completed answer.
- General tutoring is separate from grounded document answers. Grounded answers require ready owned documents and later include stored citations.

## Dependencies

P3 Tika is currently unhealthy locally, so document-grounded answers remain unavailable until its health check/extraction is verified. General conversation schema/UI can proceed independently. D05 needs AI request/token allowances. Groq is the approved development provider via its OpenAI-compatible chat-completions API; default development model is `openai/gpt-oss-20b`, subject to account availability/rate limits. Never commit `GROQ_API_KEY`; use a local environment variable and rotate any key exposed outside the local environment.

## Acceptance

- Verified students can create and view only their own chats/messages.
- Guest, unverified, and cross-user access is denied.
- Prompt validation, idempotency, and pending/failed UI states are tested.
- Live provider requests are disabled without explicit configured credentials and allowance policy.

Assistant replies use an escaped server-side Markdown subset for readable headings, emphasis, lists, quotes, separators, and inline code. Raw model HTML is never rendered.

## Verification slice (17 September 2026)

The tutor access and queue boundary is covered by feature tests: verified access, guest/unverified denial, cross-user ownership denial, prompt validation, message persistence, and queued job dispatch. Grounded document context, allowance reservation/settlement, and cancellation remain the next P4 slices.

## Grounded context slice (17 September 2026)

Chats can select up to five owned documents in `ready` status. The queued generation job passes a bounded, explicitly untrusted reference block to the provider. Cross-user, processing, and failed documents are rejected. Chunk retrieval, embeddings, page-level citations, and insufficient-evidence scoring remain deferred until the P3 ingestion benchmark is complete.

## Usage and recovery slice (17 September 2026)

Every chat request reserves one provisional `ai_chat` allowance before dispatch. Successful jobs settle one usage record; exhausted failures release the reservation and mark the message failed. Expired pending reservations are released during the next reservation attempt. Student and Pro development seed data use 100 and 500 requests per monthly period respectively; these values are not production pricing policy.
