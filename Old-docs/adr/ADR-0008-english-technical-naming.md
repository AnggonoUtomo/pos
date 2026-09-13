# ADR-0008: Gunakan English Technical Naming Untuk Database Dan Kode

## Status

Diterima

## Tanggal

2026-09-12

## Konteks

Dokumentasi proyek ditulis dalam Bahasa Indonesia agar mudah dipahami developer lokal. Namun aplikasi menggunakan Laravel, Inertia React, Spatie Permission, Spatie Activitylog, dan package ekosistem yang memakai konvensi teknis berbahasa Inggris.

Jika tabel/kolom database memakai Bahasa Indonesia sementara class, package, permission, dan route memakai English technical naming, mapping mental developer menjadi berat dan berisiko menciptakan campuran naming.

## Keputusan

Gunakan English technical naming untuk:

- Tabel database.
- Kolom database.
- Class, interface, enum, DTO, event, contract.
- Route name.
- Permission key.
- Module namespace.

Gunakan Bahasa Indonesia untuk:

- Dokumentasi.
- UI label.
- Help text.
- Pesan validasi yang tampil ke user.

Contoh:

```text
Database: customers
UI label: Pelanggan

Database: sales_invoices
UI label: Faktur Penjualan

Database: stock_movements
UI label: Kartu Stok / Mutasi Stok
```

## Alternatif Yang Dipertimbangkan

### Bahasa Indonesia Untuk Tabel Dan Kolom

- Kelebihan: dekat dengan istilah bisnis lokal.
- Kekurangan: bentrok dengan konvensi Laravel/package dan mudah bercampur dengan English naming.
- Ditolak.

### Campuran Berdasarkan Modul

- Kelebihan: fleksibel.
- Kekurangan: tidak konsisten dan sulit dirawat.
- Ditolak.

## Konsekuensi

- Developer harus menjaga database naming tetap English plural snake_case.
- UI tetap dapat menampilkan istilah Bahasa Indonesia.
- Package table bawaan seperti Spatie tetap mengikuti nama standarnya.
- Perubahan naming database membutuhkan ADR atau persetujuan eksplisit jika sudah ada migration.
