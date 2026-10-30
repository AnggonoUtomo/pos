# Platform/AuditLog

Status: Active. Halaman audit baca-saja, permission dan route lokal aktif.

Modul memiliki projection aman dari tabel Spatie activity_log, filter dan UI
penelusuran aktivitas. Bukan pemilik ledger stok/finance, bukan penulis ulang
audit module lain, dan tidak menyediakan edit/hapus/ekspor log.

Dependency publik: OperationalSettings/TablePagination untuk ukuran daftar.
Adapter membaca tabel audit package dan nama actor dari users melalui query;
tidak memuat subject morph/model privat module lain atau credential pengguna.

Lihat [spesifikasi](specification.md) dan
[work item](work-items/read-only-viewer/README.md).

Buka `/platform/audit-log` lewat menu Platform > Log Aktivitas. Super-system
memakai bypass existing; role lain membutuhkan platform.audit-log.view yang
ditetapkan administrator, tanpa auto-grant. Panduan operator:
[Log Aktivitas](../../../user-guide/LOG-AKTIVITAS.md).
