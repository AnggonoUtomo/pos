# Rencana Sales/Parties

1. Increment 1: generator dan CRUD backend level/pelanggan, schema ULID,
   invariant referensi/locking, permission, audit, seed demo dan API tests.
   Acceptance: create/list/edit/delete/restore berjalan, user tanpa akses 403,
   level invalid/terpakai ditolak termasuk super-system, rollback atomic.
2. Increment 2: UI admin lokal module dan navigasi/palette. Reuse workspace
   stabil melalui route prefix eksplisit tanpa mengubah default Inventory.
   Acceptance: CRUD dan feedback ergonomis, permission UX, shortcut/filter.
3. Increment 3: QA automated backend, MySQL disposable, browser desktop/tablet,
   dokumentasi API/status dan penutupan. Tidak reset atau demo DB operasional.

Setiap increment diuji dan commit otomatis. Push setelah semua increment lulus.
