# Product UI synchronization

Status: implemented and locally verified.

## Scope

- Align the public landing page with the accepted school administration plus e-learning direction.
- Make implemented school workspaces, learner registry, academics, teaching assignments, and guardian access discoverable after login.
- Preserve existing personal study tools as a separate secondary area.
- Keep all navigation responsive and consistent with the current Blade/Tailwind conventions.

## Dependencies

- Implemented school routes and active-membership navigation composer.
- Existing authenticated app layout and personal study routes.
- No new package, database table, or external service.

## Decisions

- School workspaces are the primary authenticated destination when a user has a school membership.
- AI and personal study tools remain available but are described as secondary/legacy learning tools while AI is deferred for the school release.
- Dashboard cards link only to implemented routes; planned attendance, fees, reports, and imports are not presented as available features.
- The landing page describes current direction and avoids promises about unimplemented workflows.

## Acceptance criteria

- Public copy reflects school administration, teaching workflows, learner records, and guardian visibility.
- Authenticated users can reach each implemented school area from dashboard/navigation without manually entering URLs.
- Users without school memberships retain access to personal study tools.
- Guardian users can reach the guardian portal from dashboard/navigation when active links exist.
- Layouts remain usable on small screens and preserve accessible labels/focusable links.

## Verification

- Blade view compilation through the Vite build.
- Existing focused feature tests and full PHPUnit suite remain green.
- `git diff --check` passes.
- Manual browser review of `/`, `/dashboard`, a school overview, academic page, learner registry, and guardian portal.

## Unresolved decisions

- Final product branding and public pricing remain unresolved.
- A durable selected-school preference is still deferred until more school resources require it.
