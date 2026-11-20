# Real Hardware Profile — CASHCOW Pilot v1.1

**Evidence:** screenshot of purchased devices and CT420D technical specifications supplied by Ino (19 November 2026), and CASHCOW official scanner catalogue. **Do not use the screenshot as proof of actual driver/port behavior:** compatibility remains pending physical UAT.

## Primary owned devices

| Field | CASHCOW CT420D | CASHCOW HC-4208DB |
|---|---|---|
| Device class | **Direct thermal LABEL/WAYBILL printer**, NOT generic receipt ESC/POS | Handheld barcode scanner 1D/2D |
| Primary role | Product SKU/barcode labels, price labels, warehouse and shipping waybills | Scan product barcode on Windows PC or Android tablet |
| Protocol/data | **TSPL + CPCL** | Keyboard HID mode must be configured and tested |
| Interfaces | **USB + Bluetooth + Serial** (listed for purchased Bluetooth edition) | **USB + 2.4 GHz receiver + Bluetooth** |
| Printer/media details | 203 DPI; speed up to 152 mm/s; minimum label height 10 mm; max print length 1000 mm; liner media width 20–120 mm | Automatic/manual mode; smart charging dock; IP54 (official catalogue) |
| Unknown/unverified | Driver command compatibility, media sensor/gap, Bluetooth mode, Windows spooler, direct browser print, LAN, auto cutter, ESC/POS | HID/Enter suffix default, actual Bluetooth Android keyboard pairing, mode-switch barcodes, scan latency |
| Status | **PROFILE CONFIRMED FROM USER SCREENSHOT; PHYSICAL UAT PENDING** | **MANUFACTURER CAPABILITIES FOUND; PHYSICAL UAT PENDING** |

**Safety alert:** `Thermal Direct` is a print technology, **not** a receipt printer command protocol. The CT420D's specified TSPL/CPCL label command sets are distinct from ESC/POS. Do not send ESC/POS cut/drawer/receipt commands to this model. Neither Ethernet/LAN nor an auto cutter is demonstrated by the supplied specification. Do not treat `20–120 mm` label media as proof of 80 mm roll-receipt support.

## Two different printer adapters

| Adapter ID | Intended device | Protocol | Supported output | Status |
|---|---|---|---|---|
| `PrinterAdapter.ReceiptEscPos` | Future verified 58/80mm receipt printer | ESC/POS | Customer purchase receipt; conditional auto cutter/cash drawer | DESIGN ONLY; reference device not yet provided |
| `PrinterAdapter.LabelTsplCpcl` | **Owned CASHCOW CT420D** | TSPL/CPCL | Product code/price label, shipping label/waybill | PRIORITY HARDWARE PILOT; untested in app |

The backend must reject a `receipt` job routed to `LabelTsplCpcl`, or `label` job routed to incompatible `ReceiptEscPos`, unless an explicitly validated hybrid printer profile is approved. Never convert jobs by sending arbitrary bytes from the browser.

## Recommended first physical UAT — read-only to POS

1. **Verify the printer**: photograph physical model sticker, actual port panel and media type; check driver/manual and a printer self-test. This does not require changing POS source code.
2. **Scanner on Windows:** use USB HID or supplied 2.4GHz dongle; scan into a blank local text field and record characters, any automatically appended `Enter`, and repeated scans.
3. **Scanner on Android:** pair Bluetooth in OS, set HID keyboard mode using vendor manual if necessary, scan into browser text field and record whether keyboard appears and `Enter` suffix arrives.
4. **Printer external test only:** with manufacturer driver/tool outside POS, print a simple TSPL or CPCL label (e.g. `Produk Demo – Air Mineral 600 mL`, fictional SKU). Do not guess firmware commands; follow CT420D manual.
5. **Scan printed barcode** with HC-4208DB; verify exact text, leading zeros, mixed case where applicable, no extra whitespace and error handling.
6. **Propose** isolated `LabelPrintJob` and `TSPL/CPCL adapter` in PLAN; no source edits before APPROVAL.
7. **Receipt printing** remains a separate future UAT requiring a known-good ESC/POS receipt printer. Browser-only fallback may work on certain setups but is not validated by owning a CT420D.

## Label print contract (design level)

- Input source: existing authorized product ID/SKU with selected tenant and branch; **do not create products or modify inventory**.
- Label fields: display name, SKU, encoded barcode, optional branch-specific price, selected dimensions in millimeters, copies (bounded).
- Server creates immutable label snapshot, scoped print job, idempotency key; gateway resolves profile `LabelTsplCpcl`.
- Adapter validates target protocol, printable dimensions (CT420D listed liner width 20–120mm), minimum 10mm label height, supported symbology, printable character/codepage, gap sensor settings and copies before sending.
- Cash drawer pulse/auto cutter must be unavailable on CT420D until model-specific hardware evidence establishes support (not provided).
- A physical scan-back verification is mandatory for Code128/EAN-13 label quality. EAN-13 test values must use valid checksums; do not present a random 13-digit sample as a valid EAN.
- UI status means data handed off to device/gateway, not physical barcode readability confirmed.

## Minimal new label workspace (behind flag OFF)

- Device selector: `CASHCOW CT420D — Printer Label` (do not call it printer struk).
- Label template chooser: `Kode Produk`, `Harga Produk`, `Pengiriman` (the last only if existing shipment/waybill source exists).
- Form preview with dimensions mm, barcode symbology and quantity; `Test Label` button with obvious `DEMO` marking.
- Device diagnostics: USB/Bluetooth/Serial mode, paper gap/margin, printed width and scanner readability, failure status.
- Floating user guide: how to load a roll of sticker labels, print test, and scan with HC-4208DB on PC/Android, using verified photos only.

## Sources

- User's screenshot of CASHCOW CT420D marketplace technical specification (19 Nov 2026). Full original content is not copied into this bundle because it contains marketplace UI unrelated to technical specs.
- Official manufacturer scanner catalogue: https://www.cashcow.co.id/our-products/handheld-scanner/ (`HC-4208DB` entry).
- Reseller accessory list, for cross-check only: https://www.monotaro.id/s048925969.html.

**Unresolved before printer driver implementation:** exact supported TSPL and CPCL dialect/version and print driver installation method. Request CT420D manual if available.
