# Command Contracts CT-HW-001…011

> **Proposed API contracts, not executable route definitions.** First discover live POS API conventions. Prefix `/api/v1/pos/hardware` is illustrative and MUST be collision-checked. User token and gateway token have different permissions. Numeric IDs/UUIDs use the existing project's convention.

## CT-HW-001 — RegisterTerminal

- **Actor:** authorized branch admin or permitted cashier enrolling own station.
- **Proposed:** `POST /api/v1/pos/hardware/terminals`.
- **Request:** `{branch_id, label, platform:"windows_browser"|"android_chrome"}`; tenant is derived from trusted session, **never** browser field.
- **Response:** `{terminal_id, branch_id, enabled:false, printer_profile_id:null}`.
- **Preconditions:** role allowed, branch belongs to tenant, unique terminal identity. **Effects:** new terminal only, never changes existing POS config.
- **Errors:** 401/403/404/409/422; audit terminal registration.

## CT-HW-002 — ConfigurePrinterProfile

- **Actor:** branch admin with `hardware.manage` equivalent (must map existing RBAC).
- **Proposed:** `PUT /api/v1/pos/hardware/terminals/{terminal}/printer`.
- **Request:** `{printer_profile_id, paper_mm:58|80, auto_print:false, auto_cut:false, drawer_enabled:false}`; all optional hardware actions default false until tested.
- **Response:** terminal settings and normalized capability summary.
- **Guard:** printer/terminal must be same tenant + branch; enable flags only for tested capability; sensitive gateway LAN details inaccessible to ordinary cashiers.

## CT-HW-003 — PairGateway

- **Actor:** admin in POS to generate one-time pairing code; device initiates redeem over TLS.
- **Proposed:** `POST /api/v1/pos/hardware/gateway-pairings` and `POST /api/v1/pos/hardware/gateway-pairings/redeem`.
- **Request:** `{branch_id, label}` then `{one_time_code, device_fingerprint}`.
- **Result:** bound gateway identity with revocable scoped credential. Code short-lived, single-use; credentials never returned to public browser after redemption.

## CT-HW-004 — TestPrint

- **Actor:** branch admin with device test privilege.
- **Proposed:** `POST /api/v1/pos/hardware/terminals/{terminal}/test-print`.
- **Request:** `{printer_profile_id}`; server generates clearly marked `TEST / BUKAN TRANSAKSI` sample with no customer data.
- **Response:** `{job_id, state:"queued"}`; never modifies sales.

## CT-HW-005 — CreateReceiptPrintJob

- **Actor:** cashier authorized to view/print a **committed** sale.
- **Proposed:** `POST /api/v1/pos/hardware/sales/{sale}/receipt-jobs`.
- **Request:** `{terminal_id, mode:"initial"|"copy"}`, header `Idempotency-Key` for initial logical job.
- **Response:** `{job_id, snapshot_id, state}` (201 for new, existing job for idempotent duplicate).
- **Guard:** sale settled/posted under existing domain convention, no draft/cancelled sale, branch match; snapshot immutable.
- **Effects:** job/snapshot/audit only. **NEVER** alters sale, stock, tax, invoice number or payment settlement.

## CT-HW-006 — GatewayClaimJobs

- **Actor:** authenticated local gateway with branch-scoped device token.
- **Proposed:** `POST /api/v1/pos/hardware/gateways/{gateway}/claim` (outbound HTTPS).
- **Request:** `{capacity:1, printer_profile_ids:[...]}`; server trusts scope from token, validates each assigned profile.
- **Response:** `{jobs:[{job_id, lease_token, expires_at, receipt_snapshot, capabilities}]}`. Claim atomic and lease-bounded. Never gives jobs of other branch.

## CT-HW-007 — AcknowledgeAttempt

- **Actor:** gateway identity that currently owns lease.
- **Proposed:** `POST /api/v1/pos/hardware/print-jobs/{job}/attempts`.
- **Request:** `{lease_token, state:"sent"|"accepted"|"failed_before_send"|"uncertain", error_code?}`.
- **Rules:** monotonic valid state transition, no fake physical-print confirmation, append attempt history, rate limits. Duplicate ack idempotent. Failed before send eligible for bounded retries, uncertain requires operator decision.

## CT-HW-008 — ReprintReceipt

- **Actor:** cashier or supervisor per existing permission decision, reason required if local policy requires it.
- **Proposed:** `POST /api/v1/pos/hardware/sales/{sale}/reprints`.
- **Request:** `{terminal_id, reason}`; create separate `copy` job using original snapshot; report `SALINAN` where supported.
- **Guard:** role/sale branch/tenant checks; audit actor/time/reason; never mutate original sale.

## CT-HW-009 — PulseCashDrawer

- **Actor:** explicitly permitted cashier/supervisor, bounded by tenant + terminal and approved event.
- **Proposed:** `POST /api/v1/pos/hardware/terminals/{terminal}/drawer-open`.
- **Request:** `{reason:"cash_sale"|"authorized_manual_open", sale_id?}`.
- **Guard:** capability `drawer_kick=true`, RBAC, reason, rate limit, audit. Gateway receives only fixed validated command primitive, never browser-provided raw bytes. Do not trigger twice on HTTP retry.

## CT-HW-010 — ResolveScannedBarcode

- **Actor:** authenticated cashier in existing authorized catalog context.
- **Preferred:** reuse existing product lookup API with identical semantics if possible; otherwise propose `GET /api/v1/pos/hardware/barcodes/{encoded}`.
- **Request:** raw decoded barcode text and optional symbology metadata (untrusted); scan suffix `Enter` handled in UI.
- **Response:** `{found, product_id?, display_name?, sku?, available?, variants?}` using existing catalog, inventory and tenant rules.
- **Guard:** do not infer tenant from barcode, do not change stock or price, reject malformed/extremely long input, avoid double-adding on network retry but preserve intentional rapid repeated scans.

## CT-HW-011 — CreateProductLabelJob (CASHCOW CT420D pilot)

- **Actor:** existing authorized inventory/catalog operator; never a gateway using user CRUD permissions.
- **Proposed:** `POST /api/v1/pos/hardware/product-label-jobs` (must collision-check existing routes).
- **Request:** `{terminal_id, printer_profile_id, product_id, template_id, copies, width_mm, height_mm, barcode_format}`. Tenant and branch derived from trusted context, **not user-supplied**.
- **Response:** `{job_id, snapshot_id, kind:"product_label", state:"queued"}`.
- **Guards:** product visible in current tenant/branch; immutable label snapshot; TSPL/CPCL-compatible profile; width/height within validated CT420D media limits; copies bounded and idempotent; barcode format/data validated; rate limit; audit. Real product price is rendered from the authorized existing product/branch pricing contract without mutating it.
- **Critical:** DO NOT use this contract to print customer receipt, open cash drawer, trigger auto cutter, create inventory or alter product stock. `PRINTER_PROTOCOL_MISMATCH` if wrong printer/media type.
- **Test:** print fictional product SKU as barcode on CT420D and scan back with HC-4208DB. Physical test pending.

## Cross-cutting API rules

- Response errors follow existing POS error envelope. No new global conventions without review.
- All write contracts require auth, idempotency where repeatable, audit, branch context and validation.
- API prefix and database identifiers are **placeholders**, not instructions to overwrite live endpoints.
- Cursor/long polling/lease semantics are selected only after existing queue/event model discovery.
