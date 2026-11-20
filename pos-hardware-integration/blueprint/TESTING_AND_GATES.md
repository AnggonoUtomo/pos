# Testing Strategy and Acceptance Gates

Tests should be **proportional** and scoped; run broader existing suites when approved changed files warrant it. Record exact commands and observed results in REPORT. Do not repeatedly re-run unchanged broad suites without a reason.

**Virtual-first is mandatory:** see `VIRTUAL_ACCEPTANCE_MATRIX.md`. Existing `G0–G9` gates remain, but their **physical portions may legitimately be PHYSICAL_PENDING** while the approved software slice is `VIRTUAL_PASSED`. No production/pilot enablement until required physical gates pass.

## Mandatory gates

| Gate | Required evidence | Result before implementation |
|---|---|---|
| G0 Baseline | Git status, source/contracts/current test map, signed impact inventory | PENDING |
| G1 UX & config | Mockup approval, isolated feature flag OFF behavior, terminal permissions | PENDING |
| G2 Scanner | Windows USB & Android BT HID scanning with manual repeated scan | PENDING |
| G3 Browser print | Desktop and Android print preview 80/58 mm where printer service installed | PENDING |
| G4 Gateway | Outbound claim, branch isolation, receipt print, paper-out/offline feedback | PENDING |
| G5 Delivery | Idempotency, immutable snapshot, safe retries, audit reprint | PENDING |
| G6 Drawer | Explicit permission, one action only, device capability tested | PENDING |
| G7 Non-regression | Existing checkout/stock/payment/permissions/receipt unchanged flag OFF | PENDING |
| G8 Optional extras | Vendor SDK/label/camera independently approved and tested | OUT OF MVP |
| G9 CASHCOW real-device profile | CT420D TSPL/CPCL label printer protocol verified; HC-4208DB scan-back; no false receipt support claims | PENDING REAL HARDWARE |
| VG0–VG4 Virtual-only | Read-only baseline → mockup → fake scanner/gateway/printer + TSPL command snapshot → regression → separate report | NOT RUN |
| PG1–PG4 Physical-only | Real CT420D/HC4208DB connection, media/print/readability, recovery, authorized pilot | PHYSICAL_PENDING |

## Test / UAT register

| UAT | Scenario | Expected result |
|---|---|---|
| UAT-01 | Feature OFF + existing checkout/receipt and API | Same behavior as baseline; no new mandatory service |
| UAT-02 | New terminal bound to branch A | Cannot list/edit branch B terminals |
| UAT-03 | Printer profile 80mm/58mm + invalid capability | Validated; unsupported commands disabled |
| UAT-04 | USB HID PC scan EAN-13 twice intentionally | Product from authorized catalog; quantity increments twice |
| UAT-05 | Android Chrome Bluetooth HID; reconnect | Input returns without changing payment UI |
| UAT-06 | Browser print from PC and Android | Correct CSS/media width; dialog fallback documented |
| UAT-07 | Gateway claims queued job and prints test receipt | Outbound only; printer executes compatible commands |
| UAT-08 | Rapid duplicate `initial` requests | One logical receipt job, stable idempotency response |
| UAT-09 | Reprint posted sale after catalog price changes | Original totals remain; audited copy job |
| UAT-10 | Printer/paper/gateway offline at successful checkout | Sale/payment/stock unchanged by printing failure |
| UAT-11 | Wrong tenant/branch/token, revoked gateway | Every unauthorized claim/config/reprint rejected |
| UAT-12 | Test print and heartbeat / timeout | Status is truthful and actionable |
| UAT-13 | Unauthorized drawer open and duplicate trigger | Rejected; authorized once with audit |
| UAT-14 | Tablet landscape/portrait and PC desktop | Full functionality, usable touch targets |
| UAT-15 | Cut/drawer/QR capability ON/OFF per model | Only supported commands sent |
| UAT-16 | Gateway times out after send with no ack | Mark `uncertain`; never blind auto resend |
| UAT-17 | Camera barcode fallback (optional) | Permissions and recognition tested; no scanner required |
| UAT-18 | Vendor ePOS/Server Direct Print (optional) | Verified exact printer/firmware/browser and HTTPS configuration |
| UAT-19 | Label printer (later) | Separate printer profile & no changes to receipt printing |
| UAT-20 | Local fictional demo seed data | Readable, reversible, no production PII |
| UAT-21 | CT420D label profile, protocol test and mismatch refusal | TSPL/CPCL label demo prints; ESC/POS receipt/cut/drawer commands are rejected; no unsupported LAN assumption |
| UAT-22 | CT420D product label + HC4208DB scan-back | Printed label barcode reads exactly as expected on PC/Android; valid barcode data/check digit; no existing stock/price mutation |

## Physical test matrix

| Hardware | Windows Chrome | Windows Edge | Android Chrome | Notes |
|---|---|---|---|---|
| USB HID scanner 1D/2D | Required | Required | OTG optional | Enter suffix, screen focus |
| Bluetooth HID scanner 2D | Optional | Optional | Required | Bluetooth paired in OS; keyboard mode |
| 80mm ESC/POS USB printer | Gateway test | Gateway test | Via gateway | Exact model and driver |
| 80mm ESC/POS LAN printer | Gateway test | Gateway test | Via gateway | Same shop LAN; no inbound Internet |
| Browser print fallback | Required | Required | Required | Actual Android print service configuration |
| Vendor ePOS printer | Optional | Optional | Optional | Per-model certified adapter |
| **CASHCOW CT420D label printer** | USB/Windows driver UAT required | USB/Windows driver UAT required | Via gateway; direct Bluetooth unproven | **TSPL/CPCL only; not receipt ESC/POS** |
| **CASHCOW HC-4208DB scanner** | USB or 2.4GHz HID UAT required | USB or 2.4GHz HID UAT required | Bluetooth HID UAT required | 1D/2D from manufacturer |

## Non-regression evidence package

Before any code change, record behavior for an unchanged store on disabled feature: login/logout, product search, discount and tax calculation, sales settlement, inventory updates, refund/void if applicable, existing receipt view/print, role permissions, API payloads. After approved slice, rerun only pertinent regression + critical cash/stock paths and record diffs. Any mismatch stops rollout.

## Release and rollback gates

Pilot **one tenant and one branch only** after owner approval. Flip feature flag back OFF for instantaneous UI/API deactivation. For stateful changes, provide additive migration rollback assessment, retention of historical audit, and backup procedure; **never assume migrating down is safe**. No global enable before pilot UAT and explicit approval.
