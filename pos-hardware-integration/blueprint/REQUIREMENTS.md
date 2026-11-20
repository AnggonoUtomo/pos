# Requirements Register — BR-HW

Legend: `M` = MVP mandatory; `L` = later; `G` = hard guardrail. Acceptance IDs map to `TESTING_AND_GATES.md`.

| ID | Priority | Requirement | Acceptance |
|---|---|---|---|
| BR-HW-001 | G | Existing POS behavior unchanged when hardware feature disabled | UAT-01 |
| BR-HW-002 | M | Register isolated terminal per tenant/branch | UAT-02 |
| BR-HW-003 | M | Per-terminal printer profile (58/80mm, transport, capability) | UAT-03 |
| BR-HW-004 | M | Barcode USB HID from Windows browser | UAT-04 |
| BR-HW-005 | M | Barcode Bluetooth HID from Android Chrome | UAT-05 |
| BR-HW-006 | M | Barcode look-up via existing authorized catalog | UAT-04/05 |
| BR-HW-007 | M | Responsive browser print fallback | UAT-06 |
| BR-HW-008 | M | Optional LAN/USB print gateway can pull job from SaaS | UAT-07 |
| BR-HW-009 | M | One completed sale produces no more than one initial logical receipt job | UAT-08 |
| BR-HW-010 | M | Safe manual reprint with marking and audit | UAT-09 |
| BR-HW-011 | M | Printer disconnection never undoes sale or payment | UAT-10 |
| BR-HW-012 | M | Enforced tenant/branch/device binding | UAT-11 |
| BR-HW-013 | M | Print test, heartbeat/status, clear error/toast | UAT-12 |
| BR-HW-014 | M | Cash drawer trigger strictly authorized and audited | UAT-13 |
| BR-HW-015 | M | Hardware module responsive PC + Android tablet | UAT-14 |
| BR-HW-016 | M | Support auto cutter conditionally per model | UAT-15 |
| BR-HW-017 | M | Distinguish sent/acknowledged/uncertain physical print status | UAT-16 |
| BR-HW-018 | G | No dependency upgrades or existing schema edits without approval | UAT-01 |
| BR-HW-019 | G | No cross-tenant data, replay-based cash drawer or arbitrary raw commands | UAT-11/13 |
| BR-HW-020 | L | Scan barcode using camera via HTTPS permissions | UAT-17 |
| BR-HW-021 | L | Vendor-specific printer web SDK or Server Direct Print | UAT-18 |
| BR-HW-022 | L | Printer label/barcode and stock label workflow | UAT-19 |
| BR-HW-023 | G | No implied offline sales; print failure handled independently | UAT-10 |
| BR-HW-024 | M | Human-readable local demo fixtures, no real customer data | UAT-20 |
| BR-HW-025 | M | Separate CT420D TSPL/CPCL label printer profile; never treat as ESC/POS receipt printer | UAT-21 |
| BR-HW-026 | M | Print product/SKU barcode label on owned CT420D and scan it back using owned HC-4208DB | UAT-22 |
| BR-HW-027 | G | Hardware compatibility may be claimed only after real CT420D+HC-4208DB UAT, not from marketplace screenshot | UAT-21/22 |
| BR-HW-028 | M | Virtual scanner/printer/gateway adapters allow development without connecting devices | VUAT-01–07 |
| BR-HW-029 | G | Report `VIRTUAL_PASSED` separately from `PHYSICAL_PENDING`; never auto-promote to hardware verified | VUAT-04/05/10 |
| BR-HW-030 | G | Fake adapters never send real IO and cannot activate in production tenant | VUAT-08/09 |

## Business rules

- Barcode duplicates may intentionally increase product quantity: do not drop legitimate consecutive scans under a generic debounce.
- Barcode format is opaque product identity; do not infer tenant from barcode. Identical codes may exist across tenants.
- QR used as item/receipt barcode is unrelated to **QRIS payment settlement**; no payment adapter in this scope.
- A receipt is immutable evidence of a posted sale. Reprints use the original snapshot, not recalculated product prices, promotions or taxes.
- Hardware operations must not execute until original sale commit succeeds. Failed jobs produce explicit retry/reprint options.
- Android silent printing requires approved gateway or verified vendor-specific integration, not generic browser promises.
- **Owned device classification:** CT420D prints *labels/waybills* via TSPL/CPCL; an 80mm ESC/POS *receipt* printer is a separate, not-yet-confirmed device. Do not conflate them.
- **Virtual-first acceptance:** simulation results can close software work but cannot close physical hardware gates or authorize production enablement; no installed driver/printer/Bluetooth pair required for development tests.

## Non-functional targets (validate with pilot)

| ID | Target |
|---|---|
| NFR-HW-01 | Scanner input-to-lookup feedback responsive under typical store network; collect measured latency, do not hard-code SLA before pilot |
| NFR-HW-02 | No unbounded print queue; bounded retries, TTL, heartbeat and operator-visible failure state |
| NFR-HW-03 | All gateway SaaS connections outbound over TLS with scoped, revocable device credentials |
| NFR-HW-04 | Gateway restarts/reconnects without duplicating acknowledged logical jobs |
| NFR-HW-05 | Test on real Windows Chrome/Edge, Android Chrome, USB HID, Bluetooth HID, USB/LAN printer |
| NFR-HW-06 | Screens support touch interaction; device setup provides concise illustrated user guide |
