# SP1 school switching and overview policy

Status: implemented and locally verified.

## Scope

- Expose active school memberships in the authenticated desktop and mobile navigation.
- Keep inactive schools and removed memberships out of the switcher.
- Add an explicit `SchoolPolicy::view` check to the protected school overview controller.
- Preserve the existing 404 behavior for unauthorized school contexts.

## Dependencies

- Existing SP1 schools, memberships, and protected context middleware.
- Existing Breeze navigation Blade components and Tailwind CSS v4 build.
- No new package or schema dependency.

## Acceptance criteria

- An authenticated user sees links for each active membership in an active school.
- Removed memberships and inactive schools are not shown as switch targets.
- The school overview invokes an explicit policy before loading school data.
- Cross-school and inactive-school requests remain not-found responses.
- Existing personal navigation, invitation, role, and full test coverage remain green.

## Verification

- Focused feature tests for active school links, filtered memberships, and policy access.
- Full PHPUnit suite, Pint, Vite build, and `git diff --check`.

## Unresolved decisions

- A durable selected-school preference and a dedicated switching endpoint remain deferred until multiple school-owned resources need a shared context.
- Resource-specific policies will be added with the first learner, attendance, or finance resource.
