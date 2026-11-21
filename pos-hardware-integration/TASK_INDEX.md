# Indeks Task Hardware

**Diperbarui:** 21 November 2026.

| Task | Slice | Status | Approval |
| --- | --- | --- | --- |
| [TASK-HW-001](tasks/TASK-HW-001/README.md) | Discovery dan mockup saja | COMPLETE; dipush5e3b4a5 | APPROVED untuk discovery/desain |
| [TASK-HW-002](tasks/TASK-HW-002/README.md) | Scanner virtual read-only | COMPLETE; VIRTUAL_PASSED, PHYSICAL_PENDING; dipush96bddfe | APPROVED untuk exact PLAN dan QA disposable; commit/push diizinkan terpisah |
| [TASK-HW-011](tasks/TASK-HW-011/README.md) | Akses produksi diagnostik scanner | COMPLETE; dipush826056d, production ON, VIRTUAL_PASSED, PHYSICAL_PENDING | APPROVED untuk PLAN source/QA/.env/cache |
| [TASK-HW-012](tasks/TASK-HW-012/README.md) | QZ label gateway, konfigurasi dan tes scanner | COMPLETE; implementasi dipush8aa0c86, VIRTUAL_PASSED, PHYSICAL_PENDING, print OFF | APPROVED untuk exact PLAN, SDK, QA, backup/migration/seeder/cache dan commit/push; tanpa instalasi OS |
| [TASK-HW-013](tasks/TASK-HW-013/README.md) | Setup QZ Windows sekali, autodiscovery printer workspace dan tes kesiapan scanner keyboard | Source/helper/docs selesai; QA browser akhir berjalan, native read-only PASS | APPROVED termasuk Git; user memasang QZ manual, agent tidak menjalankan installer kedua |

## Usulan Bundle, Bukan Task Aktif

| ID usulan | Scope konseptual | Status |
| --- | --- | --- |
| TASK-HW-003 | Scanner Bluetooth Android nyata | Belum direncanakan/disetujui |
| TASK-HW-004 | Browser print tambahan bila diperlukan | Belum direncanakan/disetujui; existing tetap ada |
| TASK-HW-005 | Gateway dan adapter receipt terverifikasi | Belum direncanakan/disetujui |
| TASK-HW-006 | Print queue/reprint audited | Belum direncanakan/disetujui |
| TASK-HW-007 | Drawer/capability lanjutan perangkat yang mendukung | Belum direncanakan/disetujui |
| TASK-HW-008 | Cross-device physical UAT/rollout | PHYSICAL_PENDING; belum diizinkan |
| TASK-HW-009 | CT420D label + HC-4208DB scan-back | Software awal tercakup TASK-HW-012; fisik tetap pending |
| TASK-HW-010 | Virtual print/gateway harness sesuai consumer | Harness label/QZ tercakup TASK-HW-012; bukan izin hardware/service nyata |

Nomor usulan tidak memberi izin coding. Setiap slice baru membutuhkan PLAN,
allowlist dan approval khusus. Tidak ada scope tenant/multi-cabang. Jangan
menutup physical gate dari hasil simulasi atau mengaktifkan fitur lain otomatis.
