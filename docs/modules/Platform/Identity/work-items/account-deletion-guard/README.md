# Work Item: Pengaman Penghapusan Akun

## Status

Ready

## Scope Disetujui

Pada 11 Oktober 2026 user menyetujui hardening jalur hapus akun profil existing:
tolak penghapusan super-system terakhir, pertahankan current-password, audit
penghapusan yang diizinkan, dan logout hanya setelah transaksi berhasil.
Target Platform/Identity serta inbound adapter ProfileController existing.
Tidak menambah CRUD akun, target user bebas, permission atau route baru.

## Acceptance

- DELETE settings/profile tetap hanya untuk akun request yang terautentikasi.
- Password salah ditolak sebelum mutation; payload target id diabaikan.
- Super-system web terakhir tidak boleh dihapus, termasuk melalui Gate bypass.
- State role dibaca fresh, bukan relasi cached pada auth user.
- Lock role web memakai urutan sama dengan ManageAccess, lalu lock user.
- Audit identity.account_deleted dan delete atomic; failure tidak logout.
- Audit hanya ID dan before roles/after deleted, tanpa name/email/credential.
- Audit mempertahankan causer/subject ID meskipun akun kemudian tidak ada.
- Semantik hard delete starter kit dipertahankan; bukan CRUD master baru.
- UI existing menampilkan error account/password, Sonner, processing dan
  konfirmasi dialog; password input dibersihkan setelah request.
- Theme/CSS, Old-docs dan artefak user tidak disentuh.

## Boundary

ProfileController memvalidasi current-password, memanggil DeleteOwnAccount dan
mengurus logout/invalidation setelah action berhasil. Application memakai
outbound AccessRepository internal; adapter SpatieAccessRepository melakukan
audit/delete. Tidak ada model privat lintas module atau event tanpa consumer.
Route/name/auth guard existing tetap. Tidak menambah dependency, schema atau ADR
baru karena ini penegakan invariant akses yang sudah disepakati.

## Seeder Dan Non-Scope

Seeder baru SKIPPED: tidak ada data master/permission baru. Browser menggunakan
fixture Identity disposable existing, bukan menjalankan seeder/reset akun lokal.
Tidak menghapus akun nyata di MySQL. Session perangkat lain, retained session
rows, reassign ownership dokumen bisnis, deaktivasi akun, dan retention/compliance
akun merupakan scope terpisah sebelum akun dipakai transaksi bisnis.
Uji konkurensi/load MySQL tidak diklaim oleh SQLite feature tests.
