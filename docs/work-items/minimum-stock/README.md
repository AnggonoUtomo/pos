# Stok Minimum Per Barang dan Gudang

## Scope dan Keputusan

Inventory/Stock memiliki pengaturan minimum satuan dasar per barang/gudang.
User menyetujui peringatan saat saldo <= minimum. Minimum 0 boleh untuk barang
habis; nonaktif merupakan opsi terpisah. Pasangan belum diatur tidak mendapat
peringatan. Saldo tidak tersedia dianggap 0, bukan membuat saldo baru.

CRUD pengaturan memakai soft delete/restore, unique pair termasuk terhapus,
optimistic version, permission view/manage dan audit. Angka exact enam desimal,
format tampilan/input Indonesia. Pasangan tidak dipindah saat edit: ubah batas
atau status, atau hapus/pulihkan pengaturan. Master nonaktif/terhapus membuat
pengaturan tidak berlaku; pengaktifan/pemulihan memerlukan master aktif.

Daftar memiliki filter gudang/pencarian/status, pagination global dan selisih
menuju batas minimum (0 saat sama). Bukan saran jumlah order penuh/target stok.
Dashboard mengonsumsi public MinimumStockAlerts; hanya user berizin view
mendapat ringkasan. Tidak ada PO otomatis, notifikasi OS, reservasi atau blokir
transaksi. Freeze opname dan aturan no-negative/FIFO existing tidak diubah.

## Status

Selesai: implementasi, QA, aktivasi lokal dan delivery ke origin/main.
[Rencana](plan.md) dan [checklist beserta bukti](tasks.md).
Demo relevan CCTV/kelistrikan dibuat hanya pada database latihan/disposable;
aktivasi toko tidak mengatur batas atau role akun tanpa input user.

Halaman: https://pos.test/inventory/stock/minimums. Migration batch35 dan
catalog dua permission sudah aktif, tanpa mengisi minimum toko atau mengubah
akun, role, credential, transaksi, saldo, FIFO, Finance, scheduler dan .env.
File LSP user dan Old-docs tidak disentuh. PDF panduan lama belum diregenerasi;
tambahan operator tersedia pada Markdown dan F1.
