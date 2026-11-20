# Architecture Decision Records

These records are **design proposals**; repo-specific decisions must be confirmed after read-only discovery.

## ADR-HW-001 — Outbound gateway, not inbound printer access

**Context:** SaaS browser must print from Windows and Android; Android browsers do not universally support silent USB/Bluetooth raw thermal printing. HTTPS origins also face restrictions for direct calls to HTTP LAN peripherals.

**Decision:** new optional local gateway initiates outbound authenticated TLS calls to SaaS print queue. Gateway talks to locally configured USB/LAN printers. Browser print is universal fallback where OS printing works.

**Consequence:** Android tablet can use shared printer if gateway PC/mini PC is running; no NAT port forwarding. Gateway deployment/monitoring becomes an optional operational item. Standalone tablet silent-print requires separate validated vendor adapter.

**Alternatives:** PC-only QZ Tray (valid for Windows but not Android directly); vendor ePOS JS/Server Direct Print (proprietary, allowed only as adapter); direct browser LAN fetch (permission/CORS/HTTPS issue); native Android app (out of baseline).

## ADR-HW-002 — Capability matrix before generic brand support

**Decision:** target ESC/POS family with per-model checked capabilities (`cut`, `drawer_kick`, `qr`, `raster`, `paper_status`, codepage). Unknown capability is off by default. Support 80 mm first, 58 mm second. Barcode scanners default HID wedge.

**Consequence:** no false guarantee all ESC/POS clones support same function. Adapter and UAT required for each validated device class.

## ADR-HW-003 — Conservative print delivery and immutable snapshot

**Decision:** generate immutable receipt from a posted sale; unique logical job; delivery attempts tracked separately. State after possible physical send but lost ack is `uncertain`, not auto-resend. Explicit authorized reprints create copies with auditable reason.

**Consequence:** accurate transaction integrity and no silent duplicate dispatch. Cannot claim paper printed without device evidence.

## ADR-HW-004 — Additive opt-in, preserve existing behavior

**Decision:** separate module/namespace and tenant/branch feature flag disabled by default; no existing checkout/controller/router/schema change without precisely approved integration seam. Mockup and plan gate before code; no Docker mandatory.

**Consequence:** MVP can be piloted on selected tenant/branch; business logic regression risks remain isolated. Full auto print after sale may need a future minimal approved hook.

## ADR-HW-005 — Browser PC/Android, no offline checkout promise

**Decision:** Windows Chrome/Edge and Android Chrome are mandatory browser UAT; touchscreen responsive. Scanner Bluetooth HID on Android, USB HID on PC. Hardware integration does not implement offline POS business transactions.

**Consequence:** offline inventory/payment reconciliation is explicitly outside this module; existing online behavior unchanged.

## ADR-HW-006 — Distinct label printer and receipt printer protocols

**Context:** user's purchased **CASHCOW CT420D** is explicitly specified as direct thermal label/waybill, command languages **TSPL + CPCL**, USB/Bluetooth/Serial. Earlier generic hardware baseline assumed a future **80 mm LAN ESC/POS receipt printer**. They are different device classes even though both use thermal printing.

**Decision:** introduce `LabelTsplCpcl` adapter, product-label job type, media/dimension validation, and scanner scan-back UAT. Preserve existing `ReceiptEscPos` adapter only for later verified receipt printer. No attempt to reinterpret CT420D as ESC/POS; unsupported drawer/auto cutter disabled.

**Consequence:** owned CT420D can be used productively for barcode/price/waybill label pilot without purchasing unnecessary new hardware. Receipt auto-print remains a separate outstanding device/UAT requirement.

## ADR-HW-007 — Virtual-first development with honest separate acceptance

**Context:** hardware devices are in shipment and not connected. The POS architecture, browser inputs, print queue and TSPL/CPCL command generator can be tested against deterministic fakes before physical delivery.

**Decision:** test via isolated fake scanner, fake outbound gateway, fake print transport, label preview and browser device emulation. Use separate `VIRTUAL_PASSED` and `PHYSICAL_PENDING` verdicts. Do not install device drivers, create real gateway services, open real printer ports or claim firmware compatibility in this phase.

**Consequence:** software development proceeds without waiting; final hardware reliability remains unverified. Physical UAT and explicit owner approval are prerequisites for customer pilot and any `HARDWARE_VERIFIED` status.
