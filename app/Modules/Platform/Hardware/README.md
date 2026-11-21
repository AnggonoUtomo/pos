# Platform/Hardware

Diagnostik scanner read-only dan workspace printer label QZ: konfigurasi,
preview, job, claim/outcome, riwayat serta reprint beralasan. Tidak ada writer
stok/payment atau perubahan checkout. Provider mengikuti loader existing.

- Flag `pos_hardware.enabled` default false, bersumber dari
  `POS_HARDWARE_ENABLED` melalui Config/hardware.php.
- Flag ON mendukung production; OFF selalu404 termasuk untuk super-system.
- POS_HARDWARE_PRINTING_ENABLED terpisah, default false; konfigurasi/preview
  tidak mengaktifkan pengiriman fisik.
- Scanner existing tetap inventory.view dan public SaleItemCatalog.
- Workspace /platform/hardware memakai platform.hardware.view/manage/print/
  reprint per action. Seeder hanya mendaftarkan key, tidak mengubah grants.
- Katalog label memakai public ItemLabelCatalog Inventory/Items, tanpa private
  dependency. Semua satuan aktif berbarcode dapat dilabeli.
- Tabel hardware_printers memakai version/soft delete; hardware_label_jobs dan
  hardware_print_attempts menyimpan snapshot/claim/outcome tanpa soft delete.
- Idempotency/single claim menjaga dispatch; uncertain tidak auto-retry.
- QZ SDK lazy, WSS allowlist dan image-only melalui driver OS. Instalasi QZ,
  driver dan sertifikat dilakukan admin secara manual.
- Demo seeder no-op; profil/job sintetis hanya fixture QA disposable.

Dokumen canonical: `docs/modules/Platform/Hardware/`.
Persetujuan dan bukti: `pos-hardware-integration/tasks/TASK-HW-002/`.
Aktivasi produksi diagnostik: `pos-hardware-integration/tasks/TASK-HW-011/`.
Gateway label: `pos-hardware-integration/tasks/TASK-HW-012/`.
Tidak ada klaim USB/Bluetooth atau hardware fisik sudah teruji.
