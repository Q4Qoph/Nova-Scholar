# School management repository case studies

## 30 September 2026 — Page, visual and operation comparison

Owner requested a new comparison of the three case-study repositories from public landing/login through page navigation and small operations, including screenshots where available, evaluated against current Nova Scholar. Status: study complete and documentation verified; authorized report only, no application changes.

Scope: inspect pinned clone routes, layouts, menus, forms, controllers/actions and operation outcomes; locate repository/readme screenshots or public demonstration evidence; inspect current Nova landing, role destinations, admin setup, attendance, notices, learning/submission/feedback and small record actions. Use synthetic local Nova browser captures where practical. Do not install or execute any cloned application, claim screenshots of unrun reference pages, copy source, or include credentials/real student information.

Dependencies: existing requirements/active school trial plan, pinned clone index and prior code findings, Web Interface Guidelines, current Nova code and recorded acceptance evidence. External visual sources are attributed and marked promotional/unverified where applicable. No new package is proposed.

Deliverable: `docs/case-study-page-flow-report.md`, with a comparison matrix, per-project page-to-operation traces, visual evidence inventory, current Nova strengths/gaps with file/line references, and a short prioritized set of trial-focused recommendations. If synthetic Nova captures are produced, keep them under `docs/assets/case-study-page-flow/` and reference them from the report.

Acceptance: distinguish source-observed, screenshot-observed, locally browser-verified and planned functionality; verify clone commit identities; trace at least sign-in/navigation, record creation/editing, teacher assessment/learning and parent paths per applicable repository; explain click/state transitions without inventing measured counts; distinguish a missing screenshot from a missing feature; include concrete Nova navigation/form findings and next steps. Verify report links and documentation diff, then record actual outcomes in the implementation log. Application tests need not be rerun for a documentation-only study.


Outcome: [report](../case-study-page-flow-report.md) completed with pinned source traces, four retrieved publisher images, 16 synthetic Nova screenshots and locally exercised subject creation, attendance save/guardian view, submission and feedback release. Browser also verified guardian detour, managed-learner workspace 403, review selection lost after refresh and narrow landing overflow. Reference applications were not run. Relative report/index links and documentation whitespace were checked; application suites were not rerun. Recommendations remain proposed.


Date: 24 September 2026. Status: approved clones, per-repository static study, and a proposed SP3–SP7 application sequence complete; feature implementation remains planned.

## Scope

Compare relevant public school-management repositories with Nova Scholar's existing architecture, inspect available project and license evidence, and clone the three owner-approved repositories into the local-only `casestudies-examples/` directory for architecture, workflow, and presentation study. Do not execute cloned code or transfer implementation into Nova Scholar as part of cloning. Keep third-party repositories out of the Nova Scholar Git history while keeping a short index of sources and study limits.

## Dependencies

- User-provided GitHub school-management topic page and candidate list.
- Nova Scholar's documented Laravel/Filament versions, architecture, and active SP roadmap.
- Public GitHub repository pages and available license metadata.

## Review-stage acceptance criteria

- Compare candidates with Nova Scholar's Laravel/Filament versions, multi-school tenancy, role model, and current module gaps.
- Record feature fit, maintenance/activity caveats, and license evidence or gaps.
- Recommend a bounded approval set and record the owner's selection before cloning.
- Prepare a local index and ignore rule so future approved clones remain outside the main repository history.
- Record the review and explicitly state that no repository has been cloned.

## Post-approval clone criteria

- Clone only repository URLs the owner approves.
- Confirm each clone's remote URL, pinned commit, and local license evidence; record missing or ambiguous license text.
- Do not install dependencies or execute source scripts as part of cloning.
- Record actual clone results, first-pass implementation findings, and licensing limitations in the index and implementation log.

## Review-stage verification

- Confirm the candidate matrix includes stack/domain comparison and source-backed limitations.
- Confirm no partial repository clone exists before approval.
- Confirm the future clone path is ignored while the source index remains trackable.
- Run `git diff --check` on project documentation/configuration changes.

## Result

The owner approved `App-School-Management`, `Skuul`, and `LAVSMS`. All three were cloned shallowly under `casestudies-examples/`, their remotes and HEAD commits were checked, and their manifests, license evidence, and selected implementation files were inspected without executing repository code. The static study now traces sign-in, role navigation, assessment/marks operations, parent report access, fee receipt operations, and the records each flow reads or writes. The Skuul checkout is Laravel 13 / Livewire 4 despite its stale README describing Laravel 9. The App-School-Management README and Composer metadata claim MIT, but the checkout has no license text file; resolve that before copying code. Skuul and LAVSMS include MIT license files. Detailed per-repository findings are in [case-study-code-findings.md](../case-study-code-findings.md); the proposed page/data-flow and application order is in [case-study-informed-implementation.md](case-study-informed-implementation.md); clone metadata remains in [the case study index](../../casestudies-examples/README.md).

## Unresolved decisions

- Any source reuse requires checking license and attribution obligations against the exact material reused. In particular, App-School-Management's license text remains unverified.
- A future feature plan must compare its relevant Nova Scholar code with the corresponding reference files before proposing implementation; these clones do not authorize wholesale adoption or dependency/architecture changes.
