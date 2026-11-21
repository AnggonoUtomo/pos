# APPROVAL: TASK-HW-013

**Status: APPROVED**

- [ ] PENDING: kebutuhan dikonfirmasi, exact PLAN/source/OS/Git belum approved.
- [x] APPROVED: user menyetujui PLAN; pemasangan lokal sedang dilakukan user.
- [ ] REVISE: gunakan bila scope perlu diubah sebelum implementasi.

PLAN: [Version1](PLAN.md), tanggal21 November2026.
SHA256: `7CEA2517308C91611DC353E1CC2D938CC7952D1B6AF40297BB9DCF15487E1187`.

User meminta QZ dipasang pada PC ini dan menjawab "Langsung scan + tes kesiapan"
untuk scanner. Jawaban itu tidak dicatat sebagai approval exact-file PLAN yang
baru disusun setelahnya. Approval012 tetap historis dan tidak diubah.

## Izin Yang Diminta

Implementasi allowlist, setup script Windows reusable, download/verifikasi dan
instalasi QZ2.3.0+1 resmi di PC ini, efek standar installer pada file aplikasi/
runtime, trust TLS QZ serta startup login, QA fake/native read-only, build,
dokumentasi/ADR, commit/push setelah selesai. User menangani UAC bila diminta.

Tidak ada izin driver/service/task/firewall/LAN/pairing, silent signing,
perubahan backend/database/role/grant/.env atau aktivasi pengiriman cetak.
Linux hanya didokumentasikan, bukan dipasang/diuji pada PC Windows ini.

## Persetujuan Dan Penyesuaian Eksekusi

User menjawab: "ok, setuju bro. saya sendiri saat ini sedang install manual
qz tray." Jawaban tersebut menyetujui PLAN yang ditawarkan beserta source,
setup reusable/QA/dokumentasi dan commit/push. Pemasangan di PC ini diambil
alih user, sehingga agent tidak menjalankan installer/download/upgrade kedua,
tidak mengubah startup/trust OS yang sedang dipasang, atau melakukan uninstall.
Agent hanya memeriksa instalasi yang selesai dan koneksi native read-only.
PLAN version1/fingerprint tetap menjadi snapshot; pengurangan eksekusi OS ini
dicatat di sini, bukan mengubah PLAN yang disetujui.
