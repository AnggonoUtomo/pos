# Rencana Increment POS Data Nyata

1. Dokumen, dry-run scaffold dan public read boundary nyata. Verifikasi contract
   filtering, precision dan no mutation; commit setelah test terarah lulus.
2. Application catalog/quote, validasi Domain, HTTP permission dan regression.
   Test spoof price/level, stok agregat, missing price, rounding/overflow,
   inactive/deleted, no persistence/audit; commit sesudah focused PASS.
3. Ganti UI/mock route ke module, server-driven data/harga dan subtotal, race
   guard, feedback/shortcut/permission link. Typecheck/lint/build/browser; commit.
4. Full QA, MySQL read-only evidence, desktop/tablet, local activation tanpa
   grant/reset dan docs aktual. Push hanya setelah work item selesai.

Quote konsisten dalam read transaction tetapi tidak mengambil lock/reservasi;
posting masa depan wajib memvalidasi ulang, bukan mempercayai quote ini.
