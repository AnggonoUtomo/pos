# Keputusan Aktif

Dokumen ini menjadi indeks singkat ADR yang berlaku.

| ADR | Keputusan | Status |
| --- | --- | --- |
| [ADR-001](decisions/ADR-001-DDD-LITE-MODULAR-MONOLITH-HEXAGONAL.md) | DDD-lite Modular Monolith dengan Hexagonal Architecture | Accepted |
| [ADR-002](decisions/ADR-002-ULID-PRIMARY-KEYS.md) | ULID untuk primary key tabel utama | Accepted |
| [ADR-003](decisions/ADR-003-FIFO-PER-WAREHOUSE-NO-NEGATIVE-STOCK.md) | FIFO per item dan gudang dengan stok tidak boleh minus | Accepted |
| [ADR-004](decisions/ADR-004-FINANCE-LITE-BEFORE-FULL-ACCOUNTING.md) | Finance Lite sebelum full accounting | Accepted |
| [ADR-005](decisions/ADR-005-AUTH-PERMISSION-ACTIVITY-LOG.md) | Laravel auth, Spatie Permission, dan Spatie Activitylog | Accepted |
| [ADR-006](decisions/ADR-006-SHADCN-UI-SONNER-TOAST.md) | shadcn/ui dan Sonner toast sebagai baseline UI feedback | Accepted |
| [ADR-007](decisions/ADR-007-MODULE-COMMUNICATION-BOUNDARY.md) | Komunikasi lintas module melalui public boundary | Accepted |
| [ADR-008](decisions/ADR-008-ENGLISH-TECHNICAL-NAMING.md) | Technical naming memakai English, dokumentasi dan UI label Bahasa Indonesia | Accepted |
| [ADR-009](decisions/ADR-009-INVENTORY-UNITS-AND-ITEMS.md) | Master satuan pada Units, barang dan konversi per barang pada Items | Accepted |
| [ADR-010](decisions/ADR-010-SUPER-SYSTEM-GATE-BYPASS.md) | Super-system bypass permission melalui Gate; invariant bisnis tetap wajib | Accepted |
| [ADR-011](decisions/ADR-011-INVENTORY-BASE-UNIT-AND-REFERENCE-GUARDS.md) | Satuan dasar barang tetap; satuan yang pernah dirujuk tidak boleh dihapus/nonaktif | Accepted |
| [ADR-012](decisions/ADR-012-PAID-CHECKOUT-AND-FIFO-RESIDUAL.md) | Checkout lunas, kembalian hanya tunai, presisi uang dan residual FIFO | Accepted |
| [ADR-013](decisions/ADR-013-PURCHASE-RECEIPT-LEDGER.md) | Receipt pembelian terpisah, anchor saldo opsional dan ledger immutable atomic | Accepted |
| [ADR-014](decisions/ADR-014-PURCHASE-PAYMENT-DISBURSEMENTS.md) | Ledger pengeluaran pembelian exact6 terpisah, lunas sesudah receipt dan replay aman | Accepted |
| [ADR-015](decisions/ADR-015-SALE-RETURN-COST-AND-FIFO-LAYERS.md) | Layer retur baru dengan biaya FIFO asal; dukungan biaya nol belum diputuskan | Proposed |

Tambahkan ADR baru hanya untuk keputusan yang mahal, sulit dibalik, atau
mengubah baseline arsitektur/domain.
