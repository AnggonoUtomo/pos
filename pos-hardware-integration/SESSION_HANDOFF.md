# SESSION_HANDOFF

## Context

User Ino chose browser POS on PC/laptop and Android tablet. Asked for reusable Codex documentation strictly **without changing or impacting existing working systems/modules**. The companion documentation **must live inside the existing POS repository** at `<POS_REPO_ROOT>/pos-hardware-integration/`, never outside it or under `docs/`. The actual POS source has not been made available in this workspace; placement in the owner's repo remains their next step.

On 19 November 2026 user supplied actual hardware info: **CT420D = direct thermal label/waybill printer, TSPL+CPCL over USB/Bluetooth/Serial, not ESC/POS receipt printer**; **HC-4208DB = 1D/2D scanner USB/2.4GHz/Bluetooth**. It is now mandatory to distinguish label and receipt print adapters. Physical UAT remains pending.

The user also decided **virtual-first development without connecting devices while shipping**. v1.3 uses an **in-repository** subfolder `pos-hardware-integration/` without `docs/` and includes `blueprint/VIRTUAL_FIRST_DEVELOPMENT.md` plus `blueprint/VIRTUAL_ACCEPTANCE_MATRIX.md`. Virtual test status `NOT_RUN`; actual hardware `PHYSICAL_PENDING`; neither is claimed complete.

## Architecture snapshot

- Windows Chrome/Edge + Android Chrome; thermal 80 mm LAN preferred, USB through optional gateway, 58 mm fallback.
- Barcode scanner via USB/Bluetooth keyboard HID; Android camera is optional.
- Receipt print through browser dialog or optional outbound HTTPS gateway/validated vendor adapter.
- SaaS existing sales state is source-of-truth; immutable snapshot and idempotent print jobs, safe `uncertain` state.
- No Docker, no mandatory Android native app, no offline sales promise.
- Owned CT420D label TSPL/CPCL adapter and product label scan-back are separate VS-HW-09. ESC/POS receipt printer has NOT yet been supplied.
- All early scanner/label/gateway tests may use fake adapters after approval; mock success must be `VIRTUAL_PASSED`, never `HARDWARE_VERIFIED`.

## Critical constraints

- Documentation READY; no actual source inspected, no implementation authorized.
- Read-only discovery gate and strict existing-file allowlist + signed approval per task.
- No checkout/payment/stock changes; feature flag OFF by default; pilot limited to approved branch.
- Follow AGENTS.md, blueprint INDEX and PLAN → APPROVAL → REPORT workflow.

## Next action

Extract `pos-hardware-integration/` under the **actual POS repository root** only after checking for a name collision. Open Codex from that POS root and read `pos-hardware-integration/CODEX_START_HERE.md`; perform **read-only baseline discovery** and propose TASK-HW-001 plan/mockup. Ask for approval before code.
