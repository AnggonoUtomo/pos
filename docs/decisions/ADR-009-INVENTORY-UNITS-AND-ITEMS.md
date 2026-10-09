# ADR-009: Pemisahan Master Satuan Dan Barang

## Status

Accepted

## Tanggal

2026-10-09

## Konteks

Indeks module merencanakan Catalog, sedangkan Inventory Foundation memakai
Units dan Items. User memilih pemisahan Units/Items untuk baseline aktif.
POS grosir membutuhkan konversi per barang: satu dus dapat berisi 12 pcs pada
barang A dan 24 pcs pada barang B.

## Keputusan

- `Inventory/Units` memiliki master satuan: kode, nama, simbol, status aktif.
- `Inventory/Items` memiliki barang, SKU, barcode, base unit, relasi satuan
  jual/beli, dan conversion ratio per barang.
- Unit tidak ditetapkan sebagai base unit secara global. Base unit adalah
  pilihan pada barang; ratio base unit pada barang harus 1.
- Ratio satuan lain mengacu langsung ke base unit barang dan harus positif.
  Tidak ada conversion ratio global pada tabel units atau rantai konversi.
- Kategori/brand adalah bagian master barang bila dibutuhkan; tidak dibuat
  module baru tanpa ownership nyata.
- `Inventory/Catalog` tidak dibuat. Consumer Stock, POS, Purchasing, dan Returns
  memakai boundary Items/Units yang relevan.
- Integrasi antar module memakai published contract/DTOs yang dibuat saat ada
  consumer nyata. Model Eloquent satuan tetap privat module Units.

## Konsekuensi

Increment 3 menyiapkan master satuan; aturan konversi dipindahkan ke Increment 4
bersama relasi satuan barang. Keputusan precision/rounding quantity dan ratio
ditetapkan saat Items dibuat. Tidak ada stok, FIFO, pricing, atau UI real baru
pada Increment 3.

## Verifikasi

- Indeks module dan work item menyebut Units/Items secara konsisten.
- Schema units tidak menyimpan ratio atau flag base unit global.
- Saat Items dibuat, test menguji ratio base unit 1, ratio positif, serta dua
  barang yang memakai satuan dus dengan ratio berbeda.
