# PRD: Permintaan Transfer Antar-Gudang

Status: Scope disetujui 23 Oktober 2026; penutupan dokumentasi 24 Oktober 2026.

## Masalah Dan Pengguna

Staff gudang toko CCTV/kelistrikan perlu mencatat rencana pengiriman dari gudang
utama ke gudang tambahan tanpa mengubah saldo sebelum barang benar-benar diproses.
Admin mengelola draft; viewer memeriksa snapshot dan status, bukan memindahkan stok.

## Hasil Yang Diharapkan

Dokumen draft bernomor server dapat ditambah, direvisi, dilihat, dihapus lunak dan
dipulihkan dengan audit dan pengaman perubahan bersamaan. Barang memakai satuan
yang relevan, misalnya kamera1dus=4unit dan kabel0,125rol=12,5meter. Nama/kode
gudang, barang dan satuan snapshot menjaga keterbacaan setelah master berubah.

## Batas Produk

Satu pasangan gudang aktif berbeda; 1..100 barang unik. Kuantitas positif, exact6,
tanpa input harga/HPP. Draft bukan reservasi; saldo boleh belum mencukupi karena
tidak ada pengurangan stok. Posting nanti wajib validasi stok dan FIFO tersendiri.
Belum ada pengiriman, penerimaan, stok perjalanan, parsial, approval, reversal,
ongkos kirim atau Finance. Role super-system hanya bypass izin, bukan invariant.

## Kriteria Keberhasilan

- Alur CRUD/lifecycle terjaga version dan audit atomic, bukan success palsu.
- Gudang sama, item berulang, konversi tidak exact, payload palsu dan stale ditolak.
- UI desktop/tablet menyediakan form/detail/filter/shortcut, Sonner dan viewer.
- Demo CCTV/kelistrikan aman, tidak menjalankan pemindahan atau mengubah user/grant.
- Snapshot dan Stock/FIFO/Finance/master existing tetap terjaga saat lifecycle.

Acceptance teknis dan hasil command memakai [tasks](tasks.md), bukan checklist
posting hipotetis. Detail teknis pada [spesifikasi module](../../specification.md).
