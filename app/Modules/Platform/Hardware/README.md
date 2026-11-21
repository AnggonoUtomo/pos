# Platform/Hardware

Uji identitas barcode satuan jual, read-only dan virtual-first. Provider
ditemukan otomatis oleh loader module existing. Tidak ada model/tabel, stock
writer, payment writer, job, driver atau gateway pada slice ini.

- Flag `pos_hardware.enabled` default false, bersumber dari
  `POS_HARDWARE_ENABLED` melalui Config/hardware.php.
- Hanya local/testing; production selalu404 bahkan jika flag true.
- Route: GET `/platform/hardware/scanner` dan `/resolve?code=...`.
- Auth web/verified existing dan permission inventory.view. Tidak ada grant baru.
- Query mengonsumsi public SaleItemCatalog milik Inventory/Items, tanpa model
  atau adapter privat lintas module.
- Demo seeder no-op; fixture barcode dibuat hanya oleh QA disposable.
- Migration .gitkeep mengikuti scaffold; tidak ada perubahan skema.

Dokumen canonical: `docs/modules/Platform/Hardware/`.
Persetujuan dan bukti: `pos-hardware-integration/tasks/TASK-HW-002/`.
Tidak ada klaim USB/Bluetooth atau hardware fisik sudah teruji.
