# PLAN — TASK-HW-XXX

**Status:** DRAFT
**Owner:** TODO
**Vertical Slice:** TODO
**Date:** TODO

## Baseline read-only

TODO: repository/branch/commit, `git status --short` (tanpa mengubah), real POS `AGENTS.md`, actual stack, contracts, tests and current behavior. If repository absent, STOP.

## Scope

TODO: one slice only, positive scope, explicitly excluded work, tenant/branch pilot, flag OFF as default.

## File allowlist

TODO: exact new files and exact existing files to touch. For existing paths specify why new isolated code cannot avoid modifying them, before/after contract, and owner approval. NO wildcard root.

## Contract and regressions

TODO: existing API/business/UI behavior baseline, narrow integration seam, RBAC, data and tenant implications, feature flag OFF assertion.

## Implementation steps

TODO: small ordered reversible steps, no unapproved installs, migration, dependency bump or global formatting.

## Test/UAT

TODO: exact commands, existing regression subset, negative cases, device/OS/browser, mocked vs real status, manual screenshots/evidence plan.

## Virtual testing and physical gate

TODO: fake scanner/gateway/print transport strategy, mock isolation from production, virtual acceptance IDs, explicit `PHYSICAL_PENDING` hardware list, real-device test criteria, never claim mock equals actual device.

## Rollback

TODO: disable flag, undo newly added optional paths, stateful data/backup plan and handling of partial deployments; no destructive rollback assumed.

## Risk & mitigation

TODO: legacy contract collisions, printer uncertain ack, tenant leak, Android no-gateway behavior, explicit stop criteria.

## Owner approval

TODO: reference the adjacent `APPROVAL.md` and wait for `Status: APPROVED`; no coding before approval.
