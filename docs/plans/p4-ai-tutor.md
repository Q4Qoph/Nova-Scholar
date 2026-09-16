# P4 AI tutor and document questions plan

Status: in progress. Started 16 September 2026.

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
