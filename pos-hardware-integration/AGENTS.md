# AGENTS.md — Mandatory instructions for Codex

## Mission and priority

Create **additive** hardware integration for the **existing** POS only after the project owner explicitly approves each task. This documentation by itself **does not authorize any implementation**. Preserve all existing functionality, business rules, database records, UI routes, public API contracts, dependencies, and deployment procedures.

## Non-negotiable safety rules

1. **Discovery first, read-only.** Read the repository's own `AGENTS.md`, README, architecture, migrations, routes, auth, sales lifecycle, tests, git status, and project conventions. If any instruction conflicts, report conflict and stop rather than overwrite.
2. **No changes before approval.** A task needs PLAN + reviewed impact list + APPROVAL (`Status: APPROVED`), then `check_plan.py --require-approved`. Do not presume earlier conversation grants code or deployment authorization.
3. **No touching existing functional modules** unless an explicitly listed, minimal integration seam has written approval. Any discovered seam outside the approved allowlist requires a revised plan and approval.
4. **Never run migrations, seeds, package installs, formatter over entire repo, destructive git commands, production deployments, or pushes** without separate explicit approval. Backups and rollback plan first for any stateful change.
5. **Preserve dirty worktree.** User modifications are owned by the user. Do not overwrite, revert, delete, reformat, or stage unrelated changes.
6. **Feature flag off by default.** New hardware feature must not alter login, product lookup, sales calculation, checkout, payment settlement, stock movements, or receipt display when disabled.
7. **REST API routes, tables, namespaces in this bundle are proposals.** Check real repository before naming/inserting endpoints. Do not duplicate or rename an existing contract.
8. **Never claim a physical print succeeded solely from a transport acknowledgment.** Distinguish `sent`/`accepted`/`verified` and unknown outcomes; no blind retry of uncertain print jobs.
9. **Tenant isolation mandatory.** Gateway/terminal/print jobs are always scoped to tenant/branch; no arbitrary printer commands or opening drawers without RBAC and audit.
10. **One vertical slice per task, including its UI/UX, demo seed data when applicable, tests, report, and documentation.** Never implement multiple modules in a single large unapproved change.
11. **Native deployment, no Docker requirement.** Browser frontend, Laravel REST API, and optional standalone local print gateway. Do not install background services on the user's computer without explicit authorization.
12. **No secrets or real user data in documentation.** Use humane fictional demo labels and test barcodes, never real customer receipts.
13. **Owned hardware classification:** `CASHCOW CT420D` is a TSPL/CPCL **label** printer, not ESC/POS receipt printer. `CASHCOW HC-4208DB` is a 1D/2D scanner. Read `blueprint/HARDWARE_PROFILE_CASHCOW.md` before any device work. Do not claim LAN, auto cutter, drawer, silent Android Bluetooth or receipt support for CT420D without genuine model-specific proof.
14. **Virtual-first mandatory:** before asking to connect hardware, use isolated `FakeBarcodeInput`, `FakePrintTransport`, `FakeGateway`, protocol snapshot tests, preview and browser emulation (only within an approved PLAN). Never substitute mocks for actual firmware/USB/Bluetooth/media behavior. Read `blueprint/VIRTUAL_FIRST_DEVELOPMENT.md` and `blueprint/VIRTUAL_ACCEPTANCE_MATRIX.md`.
15. **Independent acceptance status:** software may be `VIRTUAL_PASSED` while hardware remains `PHYSICAL_PENDING`. `HARDWARE_VERIFIED` requires objective physical UAT. No real printer service, driver or socket connection needed or allowed in virtual-only phase.
16. **In-repository directory boundary:** this companion lives at `<POS_REPO_ROOT>/pos-hardware-integration/`, with `blueprint/`, `implementation/`, `scripts/`, `tasks/` beneath it. It is not a separate project and never belongs in `docs/`. Do not create/overwrite existing POS root `AGENTS.md`, `docs/`, source modules, or another folder to follow this package. Root-level repository instructions remain in effect; conflicts require stopping and asking the owner.

## Mandatory workflow

`AGENTS.md → blueprint/INDEX → baseline discovery → UI mockup → task PLAN → check-plan → owner APPROVAL → one vertical slice → targeted tests → REPORT → check-report → STATUS/NEXT_TASK/SESSION_HANDOFF/TASK_INDEX`.

## Evidence required in every PLAN

- Existing repository baseline and clean/dirty git state (read only)
- List of **exact** new paths and **exact** existing paths needing touch, if any
- Current behavior vs intended additive behavior and feature flag OFF test
- Auth and tenant isolation impact, printing/scan failure behavior
- Compatibility table with existing contracts and regression tests
- Reversible rollback; stateful changes and handling of partial failure
- UAT device/environment coverage and user approval reference

## Stop conditions

Stop and ask the user if repo is missing/inaccessible, critical behavior is undocumented, required existing file is not approved for modification, no compatible printer is available for UAT, or the task would expand beyond documentation/approved slice. Never bypass approval gates to be helpful.

## Output language

Progress notes and task documentation in Indonesian; code identifiers follow the existing project's established conventions. Explain unusual terminology in plain language.
