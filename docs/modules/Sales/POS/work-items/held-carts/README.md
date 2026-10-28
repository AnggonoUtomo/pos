# Keranjang Tertahan

Status: implementasi, verifikasi dan aktivasi lokal selesai 28 Oktober 2026;
delivery Git dicatat pada tasks.md. User menyetujui ownership kasir sendiri, persisten di
database, tanpa pengalihan/reservasi stok dan tanpa pembayaran ganda dari dua tab.

## Scope Dan Contract

- Sales/POS memiliki draft, label opsional, gudang/pelanggan, baris/satuan/
  kuantitas dan diskon/pajak. Tidak menyimpan nominal pembayaran atau menjadikan
  harga tersimpan sebagai authority. Tahan tidak membuat invoice/ledger/FIFO.
- Permission existing sales.pos.invoices.create untuk tahan/daftar/panggil/
  batalkan. Checkout tetap memerlukan create dan post. Ownership adalah
  invariant backend, termasuk untuk super-system, bukan hanya Gate/policy.
- GET /pos/held-carts: pagination 25 milik actor, tanpa actor parameter.
- POST /pos/held-carts/{id}: client membuat ULID satu kali untuk retry save;
  version 0 untuk baru, versi terkini untuk update. Request yang sama dapat
  diulang tanpa draft ganda. Versi lama dengan isi berbeda ditolak 422.
- GET /pos/held-carts/{id}: panggil hanya draft own yang belum selesai/batal.
  Reprice/recheck current master/stok. Jika tidak valid, seluruh baris tetap
  dikembalikan dengan error dan quote null agar bisa diperbaiki/dihapus kasir.
- POST /pos/held-carts/{id}/cancel: versi wajib, soft delete draft dan audit.
  Tidak membuat UI restore/pengalihan/expiry otomatis pada scope awal ini.
- Payload checkout opsional held_cart {id, version}; invoice replay existing
  tetap lebih dulu. Sumber held dikunci, harus own/versi cocok/belum digunakan.
  Penandaan invoice sumber, stok, uang dan audit commit/rollback bersama.
- Membuka draft tidak menghapus/mereservasinya. Dua tab boleh membaca tetapi
  hanya satu linked checkout berhasil; key pembayaran sama tetap replay aman.
  Reset workspace tidak membatalkan draft server. Batal draft adalah aksi
  eksplisit terpisah dengan konfirmasi.

## UI Dan Batas

Tahan dan daftar Tertahan pada toolbar POS, shortcut F6/F7, dialog shadcn,
Sonner dan label Indonesia. Memanggil saat keranjang terisi meminta konfirmasi.
Pending pembayaran mengunci operasi held agar recovery idempotency tidak rusak.
Source ID/version ikut pending attempt sessionStorage; attempt lama tanpa held
tetap kompatibel. Tidak ada auto-save tersembunyi, offline queue atau claim TTL.

Seed demo tidak mengubah data toko. Fixture disposable akan menyiapkan keranjang
kasir CCTV; production tidak diisi draft latihan tanpa instruksi user.
Migration/additive dan route cache diaktifkan setelah verifikasi. Tidak menyentuh
Old-docs, file LSP user, akun/role, printer/UAT/penjadwal atau lifecycle reset.
