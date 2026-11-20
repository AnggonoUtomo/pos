# Virtual-First Hardware Development — Binding Strategy v1.2

**Purpose:** develop and test POS hardware features **without physically connecting** the devices that are still being shipped, while preserving the live POS. This strategy is binding for all hardware slices.

## Principle 1 — simulation is not hardware certification

Separate three claims:

1. `VIRTUAL_PASSED`: application contract, UI, simulated data flow and generated protocol payload passed automated/mock tests. **Does not mean physical hardware works.**
2. `PHYSICAL_PENDING`: real printer/scanner/driver/port/Bluetooth integration has not been tested. This is an expected, honest pending state—not a failed software task.
3. `HARDWARE_VERIFIED`: real device connected, vendor-specific expected behavior observed, all required physical UATs passed and evidence recorded.

`VIRTUAL_PASSED` plus `PHYSICAL_PENDING` is a valid **development handoff**, but not permission to label a device as production-compatible or globally enable automatic hardware actions. `FAILED` denotes an actual failed test. A feature flag defaults OFF and any production pilot requires a separately approved change and physical validation.

## Principle 2 — explicit transport interfaces

Use the repository's actual language conventions after read-only discovery. Conceptual interfaces—not implementation instructions:

```ts
type HardwareJobKind = 'product_label' | 'receipt' | 'test_print';
type Protocol = 'TSPL' | 'CPCL' | 'ESC_POS';

interface PrintTransport {
  submit(job: ValidatedPrintJob, bytes: Uint8Array): Promise<TransportAck>;
}

interface ScannerInput {
  onScan(callback: (payload: { value: string; source: string }) => void): void;
}
```

**Fake/virtual adapters only for development/test:**

- `FakePrintTransport` captures protocol, bytes, job ID, print attempts and injected device status **without sending data to ports or printers**.
- `FakeBarcodeInput` emits configurable barcode text + simulated `Enter`/invalid data; mobile and desktop use same authorized product lookup contract.
- `FakeGateway` simulates an outbound gateway polling the isolated print queue: claim/lease/ack/timeout/retry/uncertain/revoked-token; **does not install a background service**.
- `LabelPreviewRenderer` creates deterministic screen/SVG/PDF preview using the same **structured label data** as the TSPL/CPCL builder; it is a visual preview, **not a physical printer emulator**.

**Real adapters are defined but not activated:** `CashcowCt420dUsbTransport`, `CashcowCt420dBluetoothTransport` and `ReceiptEscPosTransport` remain unverified until the appropriate device UAT. The real CT420D is a **label printer** with specified TSPL+CPCL, USB/Bluetooth/Serial; its test bytes must never be mislabeled as ESC/POS receipt bytes.

## Principle 3 — test contract fidelity, not just screens

Virtual tests must cover at least:

1. **Scanner:** scan Code128/SKU test value, scan valid EAN-13, whitespace/leading zero preservation, deliberate same-code rapid repeat, unknown code, wrong tenant, suffix Enter, focus while a payment dialog is open, Bluetooth-disconnect **simulation**.
2. **Label generator CT420D:** valid media dimensions (20–120mm with liner, minimum 10mm label height per user spec), text overflow, copies bounded, barcode payload, coordinates, TSPL/CPCL commands syntactically based on authoritative manual, character encoding, `PRINT` semantics and byte snapshot (golden file).
3. **Virtual label preview:** render black-and-white label for product `Air Mineral 600 mL`, SKU `SKU-DEMO-001`, code type/code value, dimensions. Compare preview snapshots visually and via assertions; no false claim of optical scan readability.
4. **Print job queue:** commit logical job once, duplicate idempotency returns same initial job, lease expires, competing gateway does not double-claim, offline before send, timeout after possible send → `uncertain`, no blind retry, copy/reprint audit.
5. **Authorization:** tenant/branch isolation; revoked gateway token; unauthorized cash drawer action denied; feature flag OFF causes **zero** hardware side effects or network calls and identical existing checkout behavior.
6. **Failure injection:** USB device unavailable, gateway disconnected, empty media, unsupported protocol, malformed barcode, invalid dimensions, transient API fault and lost ack.
7. **Browser:** Windows Chrome/Edge sizes plus **Android tablet browser emulation** (e.g. landscape/portrait, touch targets, keyboard input, dialog behavior); note that emulating a tablet viewport does **not** emulate Android Bluetooth hardware or real print service.

## Principle 4 — tools without forcing new dependencies

- Follow tests/libs **already present** in the source repository first (PHPUnit/Pest/Vitest/Jest/Playwright only if existing or approved).
- If available/approved, Playwright can simulate scan keystrokes and Enter, mock API network, emulate tablet screen/touch, and take screenshots. It cannot certify actual dongle/Bluetooth pairing.
- Do not install Playwright, Node packages, drivers, Docker, emulator software or native gateway services without a task PLAN and explicit APPROVAL.
- For TSPL/CPCL, begin with command-builder unit tests, validated grammar and snapshots. A third-party visualizer is optional and needs license/runtime review; do not trust it as proof of printer firmware compatibility.
- Mock implementations must be separated from production namespaces/builds and cannot accidentally send raw printer bytes in a live tenant.

## Mandatory virtual test evidence

Every REPORT for a virtual slice records:

| Item | Required entry |
|---|---|
| Test environment | OS/browser, test runner versions, mock adapters used |
| Software evidence | command/test names, assertions, screenshot or snapshot references |
| Job evidence | sanitized job ID, state transitions, idempotency result, no sale/stock mutation |
| Compatibility claim | `VIRTUAL_PASSED` and `PHYSICAL_PENDING` stated separately |
| Physical exclusions | USB driver, Bluetooth pairing, media feed/gap, print darkness, paper alignment, barcode physical scan readability, performance and auto cutter remain unverified |
| Next physical gate | exact device/model, what will be plugged in, steps and objective pass/fail criteria |

## Hard stop: production release gate

Even if **all virtual tests pass**, do not mark CT420D/HC-4208DB as **hardware-verified**, auto-activate feature for customers, or claim cash drawer/cutter/device compatibility. Physical UAT must be completed, and the owner must approve rollout. An alternative fallback (e.g. browser print) does not change this rule.

## Example simulations (illustrative, not real hardware tests)

| Scenario | Fake input | Expected virtual result |
|---|---|---|
| Barcode scan | `SKU-DEMO-001` + `Enter` | Existing authorized catalog resolver called once, item added per POS cart rules |
| Same barcode twice | Two distinct scan events | Quantity +2, without treating deliberate second scan as accidental network duplicate |
| Wrong protocol | `receipt/ESC_POS` routed to CT420D | Error `PRINTER_PROTOCOL_MISMATCH`; no mock transport bytes |
| Product label | 50 × 30 mm, `product_label/TSPL`, 1 copy | Generated validated TSPL bytes captured; preview generated; no real printer IO |
| Gateway down before send | Fake connection refused | Job `failed_before_send` or pending with bounded safe retry; sale remains committed |
| Lost ack after send | Fake transport sent bytes, ack dropped | Job `uncertain`; stop automatic resend |
| Cross-tenant claim | Gateway A requests Tenant B job | Denied, audit event; no leaked receipt data |

## References

- https://playwright.dev/docs/input — simulated input and keyboard actions.
- https://playwright.dev/docs/emulation — browser/device emulation.
- https://playwright.dev/docs/network — network route/mock behavior.
- https://in.tscprinters.com/en/downloads — TSPL/TSPL2 programming manual reference; firmware-specific CT420D verification remains pending.
