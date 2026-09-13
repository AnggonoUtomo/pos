# ADR-0002: Gunakan ULID Sebagai Primary Key

## Status

Diterima

## Tanggal

2026-09-12

## Konteks

Aplikasi membutuhkan identifier yang tidak mengekspos jumlah data, cocok untuk URL, dan siap untuk kebutuhan import/export atau integrasi masa depan.

Nomor transaksi tetap dibutuhkan sebagai identitas bisnis yang human-readable.

## Keputusan

Gunakan ULID sebagai primary key tabel utama.

Nomor bisnis seperti `invoice_no`, `purchase_no`, dan `transfer_no` disimpan sebagai kolom terpisah dan tidak menjadi primary key.

## Alternatif Yang Dipertimbangkan

### Auto-Increment Integer

- Kelebihan: sederhana dan efisien.
- Kekurangan: mengekspos urutan data dan kurang fleksibel untuk integrasi/import.
- Ditolak.

### UUID

- Kelebihan: umum dan unik.
- Kekurangan: lebih panjang dan kurang sortable dibanding ULID.
- Ditolak.

### Hybrid Integer + Public ULID

- Kelebihan: performa internal integer tetap ada.
- Kekurangan: menambah kompleksitas dan dua identitas teknis.
- Ditolak untuk fase awal.

## Konsekuensi

- Model Laravel memakai ULID.
- Foreign key memakai ULID.
- Index MySQL harus diperhatikan agar nama index tidak terlalu panjang.
- Nomor dokumen bisnis tetap punya unique constraint sendiri.
