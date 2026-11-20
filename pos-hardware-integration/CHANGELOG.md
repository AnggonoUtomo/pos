# Changelog

## v1.3 — 20 November 2026 (In-Repo Placement)

- Clarified the intended destination: **`<POS_REPO_ROOT>/pos-hardware-integration/`**, a first-level subfolder of the existing POS repository; not a separate project and not `docs/`.
- Rewrote the Codex start prompt and workflow paths to be run from the POS repository root. Added `INTEGRASI_KE_REPO_POS.md` with a collision-safe copy/unzip checklist.
- Folder addition is documentation-only; no existing POS source, existing root `AGENTS.md`, dependencies, database, or behavior may change without separate approval.
- Source repository unavailable in this session: package prepared, not installed in the owner's project; virtual and hardware tests remain unexecuted.

## v1.2 — 19 November 2026

- Changed **root folder** to `pos-hardware-integration/` with direct `blueprint/`, `implementation/`, `scripts/`, `tasks/`. Removed `docs/` directory from bundle layout; updated all internal relative links.
- Added mandatory Virtual-First strategy, fake scanner, fake print transport, fake gateway, TSPL/CPCL command snapshots, label preview, Android browser emulation, error injection and defined `VIRTUAL_PASSED`/`PHYSICAL_PENDING` separation.
- Added `VIRTUAL_ACCEPTANCE_MATRIX.md` (VG0–VG4, PG1–PG4, VUAT-01–10), `VS-HW-10`, ADR-HW-007, BR-HW-028–030 and new invariants.
- Strengthened PLAN, REPORT, check-plan and check-report to require explicit software vs physical acceptance evidence.
- **No POS source code modified, no virtual tests executed, no printer/scanner connected or device compatibility claimed.**

## v1.1 — 19 November 2026

- Added `HARDWARE_PROFILE_CASHCOW.md` for user-owned **CASHCOW CT420D** direct thermal label/waybill printer: TSPL+CPCL, USB/Bluetooth/Serial, 20–120mm liner width, 203DPI. Explicitly **not** interchangeable with ESC/POS receipt printer.
- Added primary scanner profile **CASHCOW HC-4208DB**: 1D/2D, USB/2.4GHz/Bluetooth; runtime HID and scan suffix still require physical UAT.
- Added `CT-HW-011` new label print job proposal, `VS-HW-09` barcode label vertical slice, `BR-HW-025–027`, `INV-HW-13–14`, and `UAT-21/22` plus `G9` hardware acceptance.
- Updated architecture, UI/UX, Codex prompt, project status, traceability and next task to distinguish receipt/label printer protocols.
- No application/repository code changed. No installation, migration, permission changes, commit or deployment.

## v1.0 — 18 November 2026

- Created generic PIMS-style POS hardware integration baseline (Windows browser, Android Chrome, ESC/POS receipt printer and 1D/2D scanner, local print gateway).

The original v1.0, v1.1 and v1.2 archives remain separate historical deliverables. **Use v1.3 for new virtual-first Codex work inside the POS repository with the actual CASHCOW profiles.**

