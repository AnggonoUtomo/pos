# Domain Specification — Hardware Integration

## Bounded contexts

1. **Existing Sales (owned by POS, READ-ONLY contract initially):** owns final posted sale, payment success, money, tax/discount, inventory and returns. Hardware must never become owner of any sales rule.
2. **Hardware Registry (new):** tenant/branch terminal, gateway pairing and printer profiles, capability discovery, enabled feature flag.
3. **Barcode Capture (new UI adapter):** capture scanner HID keystrokes and resolve through existing authorized product catalog. Does not create/modify product master or payment.
4. **Receipt Dispatch (new):** immutable receipt snapshot, print job, delivery attempt, delivery acknowledgments, manual reprint.
5. **Print Gateway (new optional device-side service):** authorized pull, transport selection, ESC/POS rendering, device status and local diagnostic. No access to existing payment database.

## Proposed entities (not migrations)

| Entity | Key fields (conceptual) | Rules |
|---|---|---|
| `PosTerminal` | id, tenant_id, branch_id, label, client_platform, enabled, printer_profile_id | Scoped to one branch; browser session binds terminal |
| `PrinterProfile` | id, tenant_id, branch_id, type, paper_mm, connection, capability_manifest, gateway_id | Allows `58`/`80`; must not expose LAN IP to SaaS app users other than authorized branch administrators |
| `PrintGateway` | id, tenant_id, branch_id, device_label, pairing_state, token_fingerprint, last_seen_at | Outbound authenticated, revocable, branch-specific |
| `ReceiptSnapshot` | id, tenant_id, sale_id, issued_at, structured_lines, totals, content_hash | Source is committed sale; immutable; tenant protected |
| `PrintJob` | id, tenant_id, branch_id, terminal_id, snapshot_id, job_kind, logical_key, state, attempts, lease_until | Unique logical initial print; idempotent request |
| `PrintAttempt` | id, print_job_id, gateway_id, started_at, transport_status, finished_at, error_code | Append-only diagnostic, sanitized |
| `HardwareAudit` | id, tenant_id, branch_id, actor_id/device_id, action, target, outcome, time | Track manual reprint, drawer release, pairing, credential revoke |

**Important:** do not create tables until the existing schema and naming conventions are inspected and the migration is individually approved. Existing receipt table or job mechanism may be reusable instead of duplicating it.

## Lifecycle — printer job

`queued → leased/claimed → sent_to_transport → accepted_or_acknowledged` with controlled branches to `failed`, `uncertain` or `cancelled`.

- `sent_to_transport` means bytes were handed to a driver/socket, **not** paper printed.
- `accepted_or_acknowledged` means adapter confirmation available; if physical printer cannot confirm, UI should say **"Dikirim ke printer"** rather than **"Pasti tercetak"**.
- `uncertain` means transport may have printed before timeout; do not auto-resend.
- `failed_before_send` may be retried within bounded, exponential backoff policy.
- `reprint` creates separately authorized `copy` job, auditable and labeled **SALINAN** when appropriate.

## Events / integrations

| Trigger | Behavior | Boundary |
|---|---|---|
| User scans code | Resolve catalog under user's tenant/branch, update only POS UI cart | Existing lookup API or approved new read-only resolver |
| User manually prints posted sale | Read committed sale, obtain/create immutable snapshot, queue receipt | No modification to sale state |
| Sale committed | **Optional future approved integration seam** to queue initial receipt | Never hook until existing lifecycle is documented + owner approval |
| Gateway accepts job | Update print attempt/status | Never alter payment or stock |
| User reprints | Reuse immutable original snapshot + new copy job | Needs RBAC + audit |
| User triggers drawer | Validate permission and currently bound device | No direct arbitrary ESC/POS bytes from browser |

## Business examples (fictional)

- Tenant: `Toko Melati`; branch: `Cabang Sukamaju`; terminal: `Kasir-01`; printer: `Thermal-LAN-80`.
- `8991234567891` is a syntactically valid EAN-13 check-digit example for an `Air Mineral 600 mL` **fictional fixture**; no claim that it belongs to a real product. `SKU-DEMO-001` is preferable for virtual Code128 tests.
- Print of sale `TRX-DEMO-00021` fails while Wi-Fi drops. Sale remains posted, UI shows `Menunggu printer`, no duplicate settlement. Supervisor later initiates reprint as a separate event.
