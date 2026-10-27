# Rencana

1. Tambahkan seeder master module dan orchestrator lifecycle: enam barang,
   multi-satuan, tiga level/harga, dua gudang, pelanggan, supplier dan metode bayar.
2. Test RED/GREEN skenario utuh, nilai saldo/uang, idempotensi, rollback,
   penolakan database berisi data dan pengaman production.
3. Helper penggantian transactional dengan allowlist tabel bisnis, urutan FK,
   fingerprint lama dan preservasi tabel nonbisnis. Test helper tanpa reset kedua.
4. Satu gate backend, backup/restore, preview lalu penggantian satu kali MySQL.
5. Dokumentasikan lifecycle, nomor dokumen baru, saldo dan arus uang expected;
   verifikasi report readonly, commit scoped dan push ketika selesai.

Tidak ada module runtime, schema, route, dependency atau desain UI baru.
Dokumen Transfers existing masih memiliki bagian status draft historis meski
posting sudah tersedia; tidak merapikan gap dokumen di luar scope ini.
