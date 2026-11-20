# Vertical Slices — One Complete Module at a Time

**Workflow rule:** do not start code until PLAN+APPROVAL. Each slice includes design/mockup, necessary API/domain tests, UI, human-readable demo data (if applicable), manual UAT and REPORT. Approve the next slice separately. The list below is **roadmap only**.

| Slice | Scope and deliverables | Depends on | Existing seam policy |
|---|---|---|---|
| VS-HW-01 | Read-only baseline; wireframe and mockup; isolated hardware workspace shell; feature flag design, terminal/profile config proposal | Discovery G0 | No repo changes for mockup; UI code only after approval |
| VS-HW-02 | USB HID scanner in Windows Chrome/Edge; focused scan input, suffix Enter, existing catalog lookup and quantity behavior | G1 | Read existing lookup only; any change to shared cart requires explicit approval |
| VS-HW-03 | Bluetooth HID on Android Chrome; touch-friendly scanning and reconnect guidance | VS-HW-02 | Do not alter existing mobile checkout |
| VS-HW-04 | Browser print template 80 mm and 58 mm fallback; preview and print dialog; posted sale source | Verified existing sale read-only contract | No checkout hook initially |
| VS-HW-05 | Optional native outbound gateway; pairing, printer profiles, test print, capability-aware ESC/POS, heartbeat | VS-HW-04 | New gateway and isolated API only; dependency approval needed |
| VS-HW-06 | Immutable snapshot, print queue, attempts, conservative retry, manual reprint with audit | VS-HW-05 | Minimal post-commit hook only by separate exact-file approval; manual print first |
| VS-HW-07 | Cash drawer authorization, auto cutter and optional vendor adapter spike | VS-HW-05/06 | No payment behavior change |
| VS-HW-08 | Consolidation, cross-device real hardware UAT, docs, operational guide, performance checks | All MVP slices | No unrelated refactor |
| VS-HW-09 | **CASHCOW CT420D label TSPL/CPCL adapter**, product label preview/print, USB gateway test, barcode scan-back with HC-4208DB | VS-HW-01/02 + gateway foundation; real manual/driver | New label jobs only; **no receipt/checkout changes** |
| VS-HW-10 | **Virtual hardware harness**: FakeBarcodeInput, FakePrintTransport, FakeGateway, fault injection, golden TSPL/CPCL payload tests, label preview snapshots, tablet browser emulation | G0/G1 + specific approved PLAN | Test-only dependencies; no real USB/Bluetooth/printer IO and no existing module mutation |

## Release fences

**Phase 0 (documentation only):** Existing system unchanged; G0 discovery and UI mockup approval.

**Phase 0A (virtual-first after task approval):** complete VS-HW-10 fake adapters in a new isolated namespace. Virtual scanner and label/gateway software slices depend on it. Report software `VIRTUAL_PASSED` with hardware `PHYSICAL_PENDING`. Real hardware physical UAT is deferred; no production enablement.

**Phase 1 (read/UX-only integration):** Scanner and browser print without automatic checkout hooks or required installed services.

**Phase 2 (gateway opt-in):** Pair one pilot tenant/branch; other customers remain unaffected. Prioritize owned **CASHCOW CT420D USB label printing** after read-only driver UAT; no claim of receipt printing.

**Phase 3 (approved checkout event integration):** After verifying existing post-commit pattern, propose one minimal integration seam to auto-enqueue initial receipt. If no safe seam exists, retain explicit Print button; never force refactor.

**Practical pilot order while devices are shipping:** baseline/mockup → virtual harness → virtual HC-4208DB keyboard scan behavior (not actual Bluetooth) → virtual gateway foundation → CT420D TSPL/CPCL label command/preview tests. Real USB/Bluetooth connection and physical label scan-back are reserved for later PG gates. ESC/POS receipt printer testing waits for a separately verified model.

## Definition of Done per slice

- PLAN approved with exact paths and blast radius.
- UI/UX mockup approved before UI code, including Android landscape portrait behavior.
- No behavior change with feature flag off; targeted regression tests pass.
- Domain/API/policy and accessible UI tests pass; hardware tests when slice requires them.
- Failure cases tested and manually demonstrable; report distinguishes emulated vs real devices.
- New demo fixture contains readable Indonesian fictional data, no real PII.
- Sonner toast success/fail only on actual operation outcome.
- User guide floating button opens sheet with device-specific illustrated steps and troubleshooting.
- REPORT and check-report complete; rollback reviewed; STATUS/NEXT_TASK/HANDOFF/TASK_INDEX updated.
- Virtual software acceptance explicitly distinguished from outstanding physical device acceptance. Never mark `HARDWARE_VERIFIED` after mock-only tests.
- Commit/push only with user authorization and the repository's own workflow.

## Suggested task naming

`TASK-HW-001` discovery + mockup, `TASK-HW-002` scanner PC, ... `TASK-HW-008` integration UAT. Task numbering may be adapted to avoid collision after inspection. No task number authorizes itself.
