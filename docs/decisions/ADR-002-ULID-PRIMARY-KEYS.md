# ADR-002: ULID Untuk Primary Key Tabel Utama

## Status

Accepted

## Tanggal

2026-09-12

## Konteks

Project membutuhkan identifier yang aman untuk diekspos pada URL, tidak mudah
ditebak, dan tetap relatif terurut untuk kebutuhan operasional. Nomor dokumen
bisnis tetap harus terpisah dari primary key.

## Keputusan

Primary key tabel utama memakai ULID. Nomor transaksi, nomor penerimaan, nomor
retur, nomor payment, dan nomor dokumen lain dibuat sebagai business document
number terpisah.

## Konsekuensi

- URL dan reference public lebih aman dibanding integer berurutan.
- Query relasional harus konsisten memakai kolom ULID.
- Seeder, factory, migration, model, dan test harus memakai strategi ULID yang
  sama.
- Business document number tetap bisa mengikuti format operasional tanpa
  mengubah identifier teknis.

## Verifikasi

- Migration tabel utama memakai ULID primary key.
- Foreign key ke tabel utama memakai tipe yang kompatibel.
- Model memakai konfigurasi ULID sesuai pola Laravel project.
- Nomor dokumen tidak dipakai sebagai primary key.
