# Project agent instructions refresh

Date: 24 September 2026. Status: implemented and verified.

## Scope

Replace the introductory project-specific section of the existing `AGENTS.md` with the requested project-instructions structure, adapted to the repository's actual stack, architecture, roles, conventions, and workflow. Preserve the Laravel Boost generated section unchanged and do not create a second `agents.md`.

## Dependencies

- Existing `AGENTS.md` and repository documentation.
- Verified project stack, authentication, role, and architecture details from repository inspection.

## Acceptance criteria

- The document uses the requested Project, Stack, Architecture, Core Roles, Development Rules, Workflow, and Reference Repositories headings.
- Stack and roles describe the actual repository, including the absence of Spatie Permission.
- Existing project documentation obligations remain present.
- The generated Laravel Boost section remains unchanged.
- Add an implementation-log record with actual verification and limitations.

## Verification

- Inspect the resulting `AGENTS.md` and compare its Boost section with the pre-change copy.
- Run `git diff --check` on changed documentation.

## Unresolved decisions

None. The user's requested workflow is adopted as written, with approval requested before implementation after presenting the proposal and affected files.
