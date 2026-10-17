# Rencana Suppliers

1. Selesaikan [fondasi master](work-items/masters-foundation/plan.md) sesuai
   increment dan QA yang disetujui.
2. SupplierLookup redacted tersedia untuk consumer draft/posting PurchaseOrders;
   bukti berada pada work item draft-foundation module tersebut.
3. Posted memakai FK restrict dan snapshot: supplier boleh nonaktif/soft delete,
   posting baru wajib aktif. Tidak ada hard delete atau usage port spekulatif.

Perubahan consumer dicatat di draft-foundation PurchaseOrders dan
[penerimaan penuh](../../../work-items/purchase-full-receipt/README.md).
Supplier tidak memiliki posting stok, retur atau ledger Finance.
