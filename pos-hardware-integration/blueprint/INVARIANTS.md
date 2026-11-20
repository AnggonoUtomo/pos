# Invariants · Authorization · Consistency Matrix

| ID | Invariant | Authorized actor / scope | Consistency & verification |
|---|---|---|---|
| INV-HW-01 | Feature OFF means no change to existing system behavior | Existing users | Regression snapshot against baseline, UAT-01 |
| INV-HW-02 | Hardware job never mutates sale/payment/stock | Cashier can print posted sales | Read-only sale source, UAT-10 |
| INV-HW-03 | Tenant/branch isolation for terminal, profile, gateway and job | Admin/customer branch token | Cross-tenant/branch negative tests, UAT-11 |
| INV-HW-04 | One logical initial job per posted sale + terminal/policy | Cashier, authorized API | DB uniqueness/idempotency, UAT-08 |
| INV-HW-05 | Snapshot immutable after creation | Server only | Reprint original total despite later catalogue changes, UAT-09 |
| INV-HW-06 | Gateway only claims jobs it is bound to | Scoped gateway token | Lease ownership and isolation, UAT-07/11 |
| INV-HW-07 | Unknown printer state cannot be silently retried | Supervisor for manual decision | `uncertain` branch, UAT-16 |
| INV-HW-08 | Cash drawer action cannot be arbitrary or replayed | Authorized cashier/supervisor only | Nonce/idempotency, RBAC, audit, UAT-13 |
| INV-HW-09 | Scanner lookup respects existing catalogue visibility/pricing | Authorized cashier in branch | Existing product service contract, UAT-04/05 |
| INV-HW-10 | Manual reprint distinct, labeled, logged | Role per real POS policy | Audit event and copy indicator, UAT-09 |
| INV-HW-11 | Unsupported printer command disabled | Device capability profile | Hardware UAT, UAT-15 |
| INV-HW-12 | Local gateway never accepts unvalidated public commands | Branch-bound gateway | Threat/failure test, UAT-11/13 |
| INV-HW-13 | CT420D label job and ESC/POS receipt job must never cross adapters | Authorized branch printer profile | Protocol/media validation and physical UAT, UAT-21 |
| INV-HW-14 | Label print never changes product catalog/price/stock | Authorized catalog operator | Snapshot-only read, scan-back test UAT-22 |
| INV-HW-15 | Fake transport never opens real USB/Bluetooth/socket and cannot enter live production path | Test harness only | VUAT-08/09 |
| INV-HW-16 | Virtual pass cannot advance physical gate or production rollout | Owner approval + real device tester | VG4/PG1–PG4 |

## RBAC proposal (map to existing roles; don't create global roles unapproved)

| Operation | Cashier | Supervisor | Branch admin | Gateway identity |
|---|---|---|---|---|
| Scan products | Yes | Yes | Per existing policy | No |
| Print posted sale | Yes if sale visible | Yes | Yes | No |
| Reprint | According to existing policy | Yes | Yes | No |
| Configure printers | No | Optional | Yes | No |
| Pair/revoke gateway | No | No | Yes | Redeem own pairing code only |
| Test print | No by default | Optional | Yes | Executes claimed job only |
| Manual cash drawer open | If separately granted | If separately granted | Per policy | Executes authorized command only |
| Claim print jobs | No | No | No | Yes, bound tenant/branch only |

## Isolation rules

- Scope every access through trusted authenticated tenant + branch from server, **never** tenant_id from client payload.
- Gateway credentials are distinct from user sessions; revoke on decommission; rotate on suspicion/transfer.
- No direct browser-to-device raw ESC/POS execution. Server validates intent and constructs fixed command set.
- Client-side UI hiding is not authorization. Every CT enforces backend policy.
- Use existing DB transactions and queuing conventions to avoid partial commit; do not introduce cross-database distributed transactions.
