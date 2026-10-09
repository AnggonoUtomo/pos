# Module Gudang

Fondasi backend `Inventory/Warehouses`: schema dan model gudang dengan ULID,
kode unik, status aktif, soft delete, permission awal, demo seeder, dan audit.

Provider ditemukan otomatis oleh loader aplikasi. Route, migration, dan seeder
berada di dalam module. Endpoint HTTP dan UI CRUD belum tersedia.

Model Eloquent pada Infrastructure adalah internal module. Published contract
baru dibuat ketika ada consumer lintas module yang nyata.

Spesifikasi dan hasil QA tersedia pada
[dokumentasi module](../../../../docs/modules/Inventory/Warehouses/README.md).
