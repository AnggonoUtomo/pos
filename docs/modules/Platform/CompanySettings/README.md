# Platform/CompanySettings

Scope MVP: satu identitas toko, alamat/kontak, dan catatan struk. Singleton
immutable secara identitas, bukan CRUD banyak perusahaan; tidak ada delete/
soft delete. Update dengan version, permission platform.company-settings.manage,
view terpisah, audit wajib atomik. Nama toko wajib; informasi lain opsional.

Public contract CompanyProfile menyediakan snapshot identitas untuk POS, bukan
model Eloquent. Invoice baru menyimpan snapshot; cetak ulang tidak berubah saat
pengaturan diubah. Invoice lama tanpa snapshot tidak direkayasa ulang.

Route dan migration/seeder milik module. Demo fiktif hanya local/testing,
idempotent, tidak menimpa konfigurasi existing. Rencana/checklist/QA memakai
[MVP siap toko](../../../work-items/mvp-ready/tasks.md).
