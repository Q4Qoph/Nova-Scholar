# Repository hygiene plan

Status: implemented and locally verified. Started 16 September 2026.

## Scope

Keep application source, migrations, tests, deployment templates, and project documentation available to Git while excluding local secrets, generated agent tooling, dependency directories, build output, logs, and runtime uploads.

## Acceptance criteria

- `.env` and runtime artifacts cannot be newly staged through normal `git add`.
- Codex/Claude/Boost local configuration and skill caches are ignored.
- `.env.example`, `compose.yaml`, `AGENTS.md`, and `docs/` remain eligible for review and commit.
- Existing tracked files are not silently removed from history.

## Verification

Run `git check-ignore -v <path>` for excluded files and `git status --short` before staging. Review the staged file list with `git diff --cached --name-status`.
