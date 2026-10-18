# Rencana Posting Retur Penjualan

## Increment 1: Baseline Dan Keputusan Domain

Status: Passed.

Catat persetujuan scope, ownership dan checklist. Kunci urutan FIFO serta
perlakuan nilai nol/residual sebelum ADR dan implementasi. Desain harus
menjelaskan concurrency antar draft invoice sama, pembagian cost dari beberapa
consumption, trace ke source line, privacy HPP, master nonaktif dan overflow.
Tidak membuat port/adapter/schema hanya untuk melengkapi diagram.

## Increment 2: Writer Stock Dan Valuasi

Status: Passed.

Setelah keputusan domain accepted, buat unit test red untuk alokasi retur
partial/full, residual, multiple cost layers dan batas kuantitas. Publikasikan
writer Stock dengan DTOs minimal yang benar-benar dikonsumsi Returns.
Schema/adapter milik Stock menyimpan trace consumption asal dan membatasi
kumulatif retur melalui lock/current read serta constraint yang sesuai.
Histori sale/consumption asal tidak ditimpa.

Verifikasi: unit/feature tests, rollback Stock audit, replay, ledger conservation,
migration disposable, native MySQL termasuk peer race dan stale read.

## Increment 3: Posting Returns Dan UI

Status: Passed.

Action Returns mengunci revisi draft, memvalidasi sumber dan memanggil writer
Stock pada shared transaction. Tambahkan permission posting terpisah melalui
catalog additive; tidak memberikan grant otomatis. UI konfirmasi posting,
status posted readonly, filter/detail, shortcut dan Sonner mengikuti pola
existing. HPP tetap privat dan tidak ditampilkan sebagai nominal refund.

Demo posting hanya dipertimbangkan secara eksplisit pada local/testing dan
tidak dijalankan saat aktivasi. Jangan memposting draft user melalui seeder.

Verifikasi: permission/version/replay, posted mutation guards, rollback audit
Returns setelah Stock berhasil, browser DevTools dan desktop/tablet Light/Dark.

## Increment 4: Aktivasi Dan Delivery

Status: Passed.

Jalankan regresi, typecheck/lint/build, route/migration, native QA, dan review
boundary. Aktivasi lokal hanya schema/katalog permission; fingerprint data lama
diperiksa, tidak demo/grant/reset/posting. Perbarui dokumentasi dan checklist
berbasis bukti. Commit otomatis per increment verified; push setelah keseluruhan
work item selesai, lalu verifikasi remote dan file user yang tidak ikut commit.
