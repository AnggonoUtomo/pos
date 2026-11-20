# Instruksi pertama untuk Codex — salin-tempel

Gunakan perintah berikut **dari root repository POS**, setelah folder `pos-hardware-integration/` ditempatkan sebagai subfolder di sana. **Perintah ini hanya untuk membaca dan merencanakan**, bukan mengimplementasikan fitur.

---

Bro Codex, working directory kamu adalah **root repository POS existing**. Pelajari subfolder **`pos-hardware-integration/` yang berada DI DALAM repository ini**, sebagai panduan modul Hardware & Peripheral Integration POS v1.3 Virtual-First. Ini folder dokumentasi pendamping, bukan project terpisah. Prioritas tertinggi: **jangan mengubah atau memengaruhi sistem dan seluruh modul yang sudah berjalan**.

1. Baca aturan existing di root POS (`AGENTS.md`, bila ada) **tanpa menimpa atau mengeditnya**. Lalu baca `pos-hardware-integration/AGENTS.md`, `pos-hardware-integration/README.md`, `pos-hardware-integration/blueprint/INDEX.md`, **`pos-hardware-integration/blueprint/HARDWARE_PROFILE_CASHCOW.md`**, **`pos-hardware-integration/blueprint/VIRTUAL_FIRST_DEVELOPMENT.md`**, **`pos-hardware-integration/blueprint/VIRTUAL_ACCEPTANCE_MATRIX.md`**. Bila bertentangan, berhenti dan laporkan. Instruksi subfolder bukan pengganti instruksi root repo.
2. **READ-ONLY**: pahami arsitektur repo nyata, status git, controller checkout, REST API, tenant/cabang, policies/RBAC, inventory, pembayaran, receipt, component UI, route, tests dan conventions. Tidak boleh mengubah file, menjalankan migrasi, memasang paket, melakukan seed, format global, commit, push, atau menjalankan servis.
3. Bandingkan semua asumsi bundle dengan implementasi nyata; catat konflik, risiko regresi, nama file yang benar dan solusi additive. Tidak boleh mengubah modul existing agar cocok dengan dokumen jika sebenarnya dokumen yang perlu menyesuaikan.
4. Buat laporan baseline dan mockup UI workspace hardware (desktop Windows dan Android tablet), **tanpa mengedit kode aplikasi**. Perangkat nyata: **CASHCOW CT420D = printer LABEL TSPL/CPCL, USB/Bluetooth/Serial; HC-4208DB = scanner 1D/2D USB/2.4GHz/Bluetooth**. Jangan menganggap CT420D printer struk ESC/POS atau memiliki LAN/cutter/drawer. Rencanakan label print/scan-back sebagai vertical slice tersendiri. **Hardware belum tiba: seluruh uji awal harus menggunakan fake scanner, fake gateway, fake print transport, protocol snapshots, dan label preview.** Jangan install driver atau meminta USB/Bluetooth selama mode virtual.
5. Usulkan hanya `TASK-HW-001` dengan PLAN berisi exact file allowlist, alternatif integrasi paling sedikit dampak, feature flag OFF, pengujian regresi, **virtual acceptance vs deferred physical UAT**, rollback, dan pertanyaan yang perlu diputuskan pengguna. Simpan rencana hanya dalam `pos-hardware-integration/tasks/`; jalankan `python3 pos-hardware-integration/scripts/check_plan.py pos-hardware-integration/tasks/TASK-HW-001/PLAN.md` bila sudah lengkap.
6. **STOP dan minta persetujuan eksplisit dari Ino.** Tanpa `APPROVAL.md` berstatus `APPROVED`, jangan menulis satu baris pun ke source existing.

Tampilkan keluaran: (a) status dan temuan baseline, (b) impact matrix, (c) mockup UI untuk disetujui, (d) draft PLAN, (e) daftar perubahan yang memerlukan izin. Jangan mengklaim implementasi selesai.

---

## Catatan penggunaan

**Ekstrak ZIP langsung dari root project POS** sehingga terbentuk `<root-project-POS>/pos-hardware-integration/`. Jangan ekstrak ke `docs/`, jangan membuat repository kedua, dan jangan menimpa `AGENTS.md` maupun file lain di root POS. Pastikan folder tujuan belum ada/berisi konflik sebelum ekstraksi. Cara aman dan contoh struktur: `pos-hardware-integration/INTEGRASI_KE_REPO_POS.md`. Tidak ada langkah yang memerlukan Docker.
