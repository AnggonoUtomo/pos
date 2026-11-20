# Baseline, Boundary & Non-Regression Gate

## Rule 0 — Not a repository migration

This documentation companion is intentionally placed **inside** the existing POS repository at `<POS_REPO_ROOT>/pos-hardware-integration/`. Folder placement is not application integration: do not move files to `app/`, `src/`, `routes/`, `database/`, or other live folders without approval. Check the target documentation directory for conflicts before insertion. Every collision requires owner review.

## Discovery checklist — read only

Record evidence and exact paths (no edits):

- [ ] Repository root, branch, last commit, `git status --short`, existing user changes.
- [ ] Own `AGENTS.md`, architecture/blueprint, deployment instructions.
- [ ] Framework versions, package manager, actual React routing (REST/Inertia/etc.), app bootstrap.
- [ ] Current tenant and branch resolution, auth/RBAC, roles, authorization policies.
- [ ] Current sales checkout, payment settlement, inventory decrement, tax/discount, refund/void flows.
- [ ] Receipt printing, invoice/receipt template, cash drawer, scanner/input handling already implemented.
- [ ] Existing API routes, controllers, webhooks and idempotency mechanics.
- [ ] Current schema, migration policy, indexes, event/queue semantics and database vendor.
- [ ] Existing error/toast component, pagination, UI layout, responsive breakpoints.
- [ ] Existing test commands, fixtures, seeders, CI, release flags/feature toggles.
- [ ] Hardware usage already in production, known printers and operating systems.

## Compatibility assessment

| Existing surface | Allowed initially | Prohibited until explicitly approved |
|---|---|---|
| Sales/checkout/payment/stock | Read contract, observe event hook | Edit calculation/settlement, reorder hooks, change payload shape |
| Tenant + RBAC | Reuse existing verified policy interfaces | Replace auth, change existing grants |
| Existing DB tables | Read schema, add separate tables only after approval | Rename/drop/backfill existing data silently |
| REST API | Add versioned namespaced endpoints after collision check | Rename/deprecate/change existing endpoints |
| UI | Add opt-in workspace, new isolated routes/components | Replace existing screen/layout or navigation unapproved |
| Dependencies | Prefer what repo already has | Upgrade Laravel/React/packages for convenience |
| Operations | New optional gateway with documented onboarding | Add always-on service or expose LAN printer to internet without approval |

## Feature flag contract

Use repository's established feature flag convention after discovery. Proposed setting: `pos_hardware.enabled = false` by default at tenant/branch level. When disabled:

- No new hardware menu, listeners, hotkeys, network request, job, or cash drawer action.
- Existing checkout and old receipt print remain bit-for-bit equivalent in behavior (within legitimate nondeterminism).
- No required migration/service or mandatory gateway for existing users.
- No disruption if gateway is absent, offline, unpaired, or later disabled.

## Change authorization boundary

The **only** work authorized by this document package is placing/maintaining documentation **inside `<POS_REPO_ROOT>/pos-hardware-integration/`**. This does not authorize application code changes. A future PLAN may propose **new** hardware directories outside the companion. Any modification to an existing route registry, sales event dispatcher, permission seeder, menu config, or shared UI must be listed as a precise filepath with before/after contract and approved individually. Even a one-line existing file change is blocked otherwise.

## Go / No-Go gate

`GO` only if baseline verified, no unapproved conflict, impact matrix complete, tests defined, feature flag isolated, rollback practical, hardware platform feasible, and owner APPROVAL expressly says `APPROVED`.

`NO-GO` if any of the above is unknown, target repo absent, or required existing seam would be changed without a signed plan. In NO-GO, provide a report and ask the owner; do not implement an alternate workaround secretly.
