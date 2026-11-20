# POS SaaS — Hardware & Peripheral Integration

**Codex Development Companion · PIMS-style · v1.3 In-Repo Virtual-First · 20 November 2026**

> **STATUS: DOKUMENTASI SAJA — BELUM ADA IZIN IMPLEMENTASI.** Folder ini ditujukan berada **di dalam root repository POS**, tetapi hanya berisi dokumen pendamping. Menempatkan folder baru ini tidak mengizinkan perubahan source aplikasi, migrasi database, atau aktivasi fitur. Semua rancangan bersifat *proposed* sampai dipetakan ke proyek POS yang benar-benar berjalan.

> **LOKASI WAJIB:** `<root-project-POS>/pos-hardware-integration/`. Folder pendamping ini **sejajar** dengan direktori aplikasi existing, **bukan** di luar project dan **bukan** di dalam `docs/`. Panduan pemasangan: `INTEGRASI_KE_REPO_POS.md`. **Virtual-first:** pengembangan perangkat keras boleh menggunakan mock/simulator tanpa menghubungkan perangkat nyata setelah tugas spesifik disetujui; hasilnya belum berarti lulus uji fisik.

## Tujuan

Menambahkan kemampuan integrasi printer struk thermal, **printer label TSPL/CPCL**, dan barcode scanner ke POS berbasis **browser PC/laptop dan tablet Android**, dengan **Laravel REST API + React/TypeScript** sebagai asumsi stack yang **harus diverifikasi terhadap source existing**. POS berjalan sebagai SaaS; tidak perlu native app kasir atau Docker. Gateway cetak lokal opsional diperlukan bagi cetak senyap lintas platform, terutama dari tablet.

**Aturan terpenting:** sistem dan modul yang telah berjalan wajib tetap berfungsi tanpa perubahan perilaku. Semua fitur hardware berada di balik feature flag yang **nonaktif secara default**. Tidak ada refactor umum, perubahan skema existing, pembaruan dependency, penggantian autentikasi, atau perubahan UI existing tanpa persetujuan tertulis per tugas.

## Mulai dari sini

1. Tempatkan folder `pos-hardware-integration/` langsung di root POS, mengikuti `INTEGRASI_KE_REPO_POS.md`; jangan timpa folder/file yang sudah ada. Buka Codex dengan working directory **root POS**.
2. Operator menyalin instruksi siap pakai dari `pos-hardware-integration/CODEX_START_HERE.md`; Codex membaca instruksi repo POS terlebih dahulu, kemudian `pos-hardware-integration/AGENTS.md` dan `pos-hardware-integration/blueprint/INDEX.md`.
3. Jalankan **discovery read-only** dari `blueprint/BASELINE_AND_BOUNDARY.md`; cocokkan klaim stack, route, otorisasi, payment, dan lifecycle sales dengan kode aktual. Jangan ubah apa pun di luar folder dokumentasi.
4. Siapkan mockup module-level dari `blueprint/UI_UX_SPEC.md` lalu minta persetujuan desain.
5. Baca `blueprint/REQUIREMENTS.md` dan `blueprint/TRACEABILITY.md`.
6. Pilih **satu vertical slice** dari `blueprint/VERTICAL_SLICES.md`.
7. Salin `implementation/templates/PLAN.md` ke tugas baru dalam `pos-hardware-integration/tasks/`; rinci file yang terdampak, risiko, rollback, dan pengujian.
8. Dari root POS, jalankan `python3 pos-hardware-integration/scripts/check_plan.py pos-hardware-integration/tasks/TASK-HW-XXX/PLAN.md`.
9. **BERHENTI:** pengguna harus mengisi/mengizinkan `APPROVAL.md` dengan status `APPROVED`. Tanpa itu tidak ada coding.
10. Implementasikan satu slice setelah approval, lakukan pengujian proporsional, tulis `REPORT.md`, jalankan validator laporan di folder pendamping.
11. Barulah perbarui `STATUS.md`, `NEXT_TASK.md`, `SESSION_HANDOFF.md`, dan `TASK_INDEX.md` di dalam `pos-hardware-integration/`; jangan sentuh file source POS di luar batas persetujuan.

## Batasan platform

| Terminal | Barcode | Cetak |
|---|---|---|
| Windows + Chrome/Edge | USB HID, Bluetooth HID | Browser print atau native print gateway |
| Android + Chrome | Bluetooth HID, USB OTG HID pada perangkat kompatibel, kamera fallback | Browser print; gateway lokal lewat SaaS; vendor ePOS teruji bersifat opsional |

- **Printer struk target:** thermal 80 mm Ethernet/LAN, ESC/POS, auto cutter, port cash drawer yang kompatibel. 58 mm merupakan fallback; **belum ada unit printer struk yang dikonfirmasi**.
- **Printer label yang SUDAH DIMILIKI:** CASHCOW CT420D, TSPL + CPCL, USB/Bluetooth/Serial. **Bukan** device uji ESC/POS dan tidak boleh diberi perintah cash drawer/cutter generik. Detail `HARDWARE_PROFILE_CASHCOW.md`.
- **Scanner yang SUDAH DIMILIKI:** CASHCOW HC-4208DB, 1D/2D, USB, Bluetooth dan 2.4 GHz dongle. Koneksi HID perlu UAT fisik.
- **Virtual-first saat perangkat masih dikirim:** fake scanner, fake gateway, fake print transport, TSPL/CPCL command snapshots, label preview, browser tablet emulation dan failure injection. `VIRTUAL_PASSED` terpisah dari `PHYSICAL_PENDING`; tidak boleh mengklaim `HARDWARE_VERIFIED` tanpa perangkat nyata. Baca `blueprint/VIRTUAL_FIRST_DEVELOPMENT.md` dan `blueprint/VIRTUAL_ACCEPTANCE_MATRIX.md`.
- **Jalur print:** `Browser → SaaS API → job queue → gateway melakukan pull HTTPS → printer lokal`. Tidak membuka akses inbound internet ke printer/toko.
- **Tanpa gateway:** fitur browser print tetap tersedia dengan dialog cetak. Silent printing universal pada browser Android **bukan** janji produk.
- **Scanner:** default HID keyboard wedge, suffix `Enter`, pemindaian 1D/2D. QRIS payment adalah domain pembayaran terpisah.

## Peta dokumen

| Dokumen | Fungsi |
|---|---|
| `AGENTS.md` | Instruksi utama Codex dan larangan perubahan existing |
| `INTEGRASI_KE_REPO_POS.md` | Tata letak folder **di dalam root POS** tanpa menimpa file existing |
| `CODEX_START_HERE.md` | Prompt pertama siap salin-tempel ke Codex (read-only) |
| `blueprint/INDEX.md` | Peta, keputusan, dan urutan baca |
| `blueprint/BASELINE_AND_BOUNDARY.md` | Discovery read-only, impact boundary, go/no-go |
| `blueprint/REQUIREMENTS.md` | Register BR-HW dan non-functional rules |
| `blueprint/TRACEABILITY.md` | Mapping BR ↔ kontrak ↔ VS ↔ UAT |
| `blueprint/DOMAIN_SPEC.md` | Konteks, entitas, status, lifecycle |
| `blueprint/HARDWARE_PROFILE_CASHCOW.md` | **Perangkat nyata milik user, beda label printer vs printer struk, protokol dan UAT** |
| `blueprint/VIRTUAL_FIRST_DEVELOPMENT.md` | **Strategi fake adapter, virtual scanner/printer/gateway, test contracts dan status** |
| `blueprint/VIRTUAL_ACCEPTANCE_MATRIX.md` | **Gerbang virtual dan fisik yang terpisah** |
| `blueprint/ARCHITECTURE.md` | Adapter, gateway, jaringan, deployment dan failure modes |
| `blueprint/COMMAND_CONTRACTS.md` | CT-HW-001 s.d. CT-HW-011 |
| `blueprint/ADRS.md` | Architecture Decision Records |
| `blueprint/VERTICAL_SLICES.md` | Pengerjaan satu modul sampai UI/UX tuntas |
| `blueprint/INVARIANTS.md` | Matriks invariant, authorization dan konsistensi |
| `blueprint/UI_UX_SPEC.md` | Mockup dan standar UI sesuai PIMS |
| `blueprint/TESTING_AND_GATES.md` | Acceptance gate, matrix OS/hardware dan uji regresi |
| `blueprint/REFERENCES.md` | Tautan teknis primer untuk verifikasi |
| `blueprint/diagrams/workflow_16x9.svg` | Diagram workflow PIMS 16:9 |
| `implementation/templates/` | PLAN, APPROVAL, REPORT |
| `scripts/` | Validator dokumentasi read-only |

## Lingkup bundle

Dokumen ini **bukan patch** dan **bukan bukti kompatibilitas runtime** perangkat. Model/port/driver harus diuji pada hardware nyata. Tidak ada password, token, IP toko, atau data pelanggan dalam contoh. Versi 1.1 memperbaiki identifikasi perangkat yang sudah dibeli; versi 1.0 generik tetap sebagai riwayat terpisah.

**Update v1.3:** seluruh struktur berada di `<root-project-POS>/pos-hardware-integration/`, bukan di luar repository atau dalam `docs/`. Petunjuk Codex kini menggunakan path dari root POS; tidak perlu mengubah `AGENTS.md` milik POS. Tidak ada virtual test yang **sudah** dijalankan; dokumen hanya mengatur cara pengujian ketika implementasi diizinkan.
