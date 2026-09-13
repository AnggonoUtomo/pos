# ADR-008: English Technical Naming Untuk Kode Dan Database

## Status

Accepted

## Tanggal

2026-09-12

## Konteks

Developer membutuhkan naming teknis yang konsisten dengan ekosistem Laravel,
package, dan dokumentasi teknis. Pada saat yang sama, dokumentasi dan UI harus
mudah dipahami oleh tim dan user Indonesia.

## Keputusan

Nama tabel, kolom, class, method, route, permission, enum, DTOs, event, contract,
namespace, dan file source memakai English technical naming. Dokumentasi proyek
dan label UI memakai Bahasa Indonesia.

## Konsekuensi

- Database tidak memakai Bahasa Indonesia untuk nama tabel/kolom.
- Permission key memakai English technical naming yang stabil, misalnya
  `identity.view`.
- UI tetap dapat menampilkan label Bahasa Indonesia.
- Dokumen developer ditulis dalam Bahasa Indonesia.

## Verifikasi

- Migration baru memakai nama tabel dan kolom English.
- Permission, route, class, dan namespace memakai English technical naming.
- UI label yang dilihat user memakai Bahasa Indonesia.
