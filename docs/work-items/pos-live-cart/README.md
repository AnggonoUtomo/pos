# Data POS Dan Keranjang Nyata

Status: Done. Implementasi, QA dan delivery remote terverifikasi;
checklist ditutup 15 Oktober 2026.
Persetujuan user 14 Oktober 2026.
Owner Sales/POS, consumer read boundary Inventory dan Sales/Parties.

Mengganti katalog/stok/pengali harga mock dengan data nyata. Gudang dan pelanggan
dipilih eksplisit; harga persis matrix satuan x level pelanggan, tanpa fallback.
Keranjang diperiksa backend termasuk stok agregat lintas satuan. Quantity dan
uang exact, UI Indonesia. Route /pos dan permission existing dipertahankan.

Non-scope: persistence penjualan/draft, posting/reservasi/FIFO keluar, diskon,
pajak, payment, retur/void/struk. Kontrol mock terkait tidak dianggap capability
nyata. Tidak mengubah theme, akun/grant/ledger, Old-docs atau artefak SampleUI.

Tidak ada schema baru. Tidak seed transaksi ke database user. Demo master,
stok awal dan 54 harga existing adalah prasyarat dataset, bukan direset.

Gap existing: /pos auth-only dan quick link/palette belum mengikuti permission
sidebar. Ditutup dalam scope endpoint data nyata ini. Dashboard mock di luar
scope, kecuali guard link POS.
