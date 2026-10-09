# Module Satuan

Fondasi backend Inventory/Units memiliki master satuan dengan ULID, kode unik,
nama, simbol, status aktif, soft delete, permission, demo seeder, dan audit.

Provider ditemukan otomatis. Routes, migration, dan seeder ada di dalam module.
Endpoint HTTP dan UI CRUD belum tersedia. Model Eloquent pada Infrastructure
bersifat privat module; contract dibuat ketika ada consumer nyata.

Base unit dan conversion ratio per barang dimiliki Inventory/Items sesuai ADR-009.
Tidak ada konversi global pada master satuan.

Spesifikasi dan QA tersedia pada
[dokumentasi module](../../../../docs/modules/Inventory/Units/README.md).
