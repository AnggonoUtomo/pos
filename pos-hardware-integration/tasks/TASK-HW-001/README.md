# TASK-HW-001: Discovery Dan Mockup Hardware

**Status:** DISCOVERY DAN DESAIN APPROVED; IMPLEMENTASI BELUM DIIZINKAN.
**Tanggal:** 20 November 2026.

## Keputusan Pemilik Yang Mengoreksi Bundle

User menegaskan: "kita tetap pertahankan POS sekarang bro, kita tidak
menggunakan scope tenant dan multi cabang."

Karena itu, seluruh penyebutan tenant/branch/SaaS pada bundle adalah asumsi
template yang tidak berlaku untuk POS ini. Aturan tenant/cabang pada
`../../AGENTS.md` butir 9 dibaca sesuai koreksi eksplisit tersebut:

- Tetap single company dan multi-gudang; gudang bukan cabang atau tenant.
- Tidak membuat tenant resolver, branch, tenant_id atau branch_id.
- Otorisasi tetap backend melalui auth, permission dan ownership existing.
- Rancangan perangkat kelak membatasi akses terminal, printer, gateway dan job
  melalui assignment serta credential perangkat, bukan scope tenant/cabang.
- Tidak menambah company_id ke seluruh tabel sebagai pengganti tenant_id.
- Koreksi scope ini bukan persetujuan coding, migrasi atau aktivasi hardware.

Catatan ini menjadi pemetaan khusus repo untuk bundle generik. Dokumen
blueprint asli tidak dianggap spesifikasi siap implementasi. Root `AGENTS.md`
dan keputusan pemilik tetap berlaku. Penyesuaian massal blueprint tidak
dilakukan dalam task yang hanya mengizinkan artefak di folder ini.

## Hasil Yang Ditinjau

1. [Baseline read-only](BASELINE.md).
2. [Matriks dampak dan integrasi](IMPACT_MATRIX.md).
3. [Rancangan UI dan status verifikasi](MOCKUP.md).
4. [Mockup HTML lokal](MOCKUP.html), tanpa akses POS, API atau perangkat.
5. [PLAN sesuai template](PLAN.md).
6. [Checklist incremental](tasks.md).
7. [Persetujuan discovery/desain APPROVED](APPROVAL.md).

TASK-HW-001 hanya discovery dan mockup. Tidak mencakup shell aplikasi,
endpoint, provider, migration, permission seeder, fake adapter executable atau
gateway. Print label dan scan-back kelak menjadi vertical slice tersendiri;
bukan perubahan pada checkout atau cetak struk.

## Status Penerimaan

| Bagian | Status |
| --- | --- |
| Discovery source | Diperiksa statis; detail pada baseline |
| Review baseline, mockup dan PLAN oleh pemilik | APPROVED pada 20 November 2026 |
| Implementasi aplikasi | NOT_AUTHORIZED |
| Software acceptance hardware | NOT_RUN |
| Physical acceptance | PHYSICAL_PENDING |
| Deployment/pilot | NOT_APPROVED |

Validasi struktur PLAN bukan bukti software atau hardware berfungsi.
Commit/push companion kemudian diizinkan eksplisit oleh user pada20 November2026;
lihat APPROVAL.md. Perubahan user di luar folder task dipertahankan.

PLAN version1 dipertahankan sebagai snapshot yang disetujui, termasuk label
draft saat diajukan. Status approval aktual ada di APPROVAL.md; fingerprint
tidak diubah. Langkah berikutnya adalah mengajukan PLAN implementasi terpisah,
bukan menjalankan coding berdasarkan persetujuan discovery ini.
