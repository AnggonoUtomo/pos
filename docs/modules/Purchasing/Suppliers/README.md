# Purchasing/Suppliers

Status: In Progress. Scope master supplier disetujui 17 Oktober 2026.
Memiliki kode/nama, kontak opsional, status aktif, soft delete/restore dan audit.
Tidak memiliki pembelian, penerimaan stok, FIFO, utang atau pembayaran supplier.

Kode dinormalisasi trim/uppercase dan unik termasuk data terhapus. Kontak tidak
masuk audit. Permission purchasing.suppliers.view/manage, tanpa auto-grant.
Demo CCTV/kelistrikan fiktif hanya local/testing dan tidak menimpa existing.

Source app/Modules/Purchasing/Suppliers; UI resources/js/pages/purchasing/suppliers.
Route/database/seeder berada di module. Domain/lookup/event belum diperlukan:
CRUD sederhana dan belum ada consumer lintas module. Detail [specification](specification.md),
[plan](plan.md), [tasks](tasks.md) dan [work item](work-items/masters-foundation/README.md).
