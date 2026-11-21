# APPROVAL: TASK-HW-002

**Status: APPROVED**
**Owner:** Pemilik project POS (user).
**Approval date:** 2026-11-21 (Asia/Jakarta).
**Approved PLAN fingerprint/version:** Version1, SHA256 tercantum di bawah.
**Approved exact existing-file changes:** NONE untuk source aplikasi; empat
metadata companion sesuai exact allowlist PLAN boleh diperbarui setelah report.
**Approved execution scope:** Implementasi exact allowlist TASK-HW-002 dan QA
disposable/temp server sesuai PLAN; bukan aktivasi produksi atau hardware fisik.

PLAN version1 yang disetujui, SHA256
`665F6076563C5F7355B8FBE433894A89D0728ED7797C532F365732204861DFAA`.
Fingerprint diverifikasi pada21 November2026. Persetujuan eksplisit user:
"ok, setuju bro", sebagai jawaban atas permintaan approval implementasi
TASK-HW-002 sesuai PLAN. PLAN dipertahankan sebagai snapshot, tidak diedit.

## Decision

- [x] APPROVED: hanya PLAN/allowlist/execution QA yang disetujui eksplisit.
- [ ] REVISE: perbaiki PLAN sebelum coding.
- [ ] PENDING: dokumentasi/rencana saja.

## Conditions and exclusions

Usulan: modul scanner virtual baru, lookup identitas barang/satuan jual saja,
inventory.view existing, feature OFF, local/testing-only, QA disposable.
Tidak ada tenant/cabang, modifikasi source existing, migrasi/seed database toko,
dependency, grant, OS service, koneksi hardware atau produksi.

Execution QA mencakup fixture/migration/seeder existing hanya pada SQLite
disposable dengan path/cache terisolasi dan server PHP sementara127.0.0.1 yang
ditutup saat selesai. Tidak ada migrate/seed/live DB, OS runner atau install.
Izin ini tidak mencakup commit/push TASK-HW-002 atau perubahan di luar allowlist.

Agent tidak boleh mengubah status menjadi APPROVED tanpa persetujuan eksplisit
user. Fingerprint kandidat PLAN dicatat setelah validasi struktur; approval
kelak harus menunjuk versi/fingerprint yang benar.

## Izin Git Terpisah: 21 November 2026

User meminta: "commit dan push dulu bro lalu ON kan fiturnya agar bisa
difungsikan untuk produksi. jadi saat ada hardware fisik bisa langsung
digunakan."

Commit dan push hasil TASK-HW-002 diizinkan eksplisit, tanpa file LSP user.
Permintaan aktivasi produksi dicatat sebagai scope berikutnya: membutuhkan
PLAN tersendiri karena guard production ditutup oleh PLAN yang disetujui.
Izin Git ini tidak mengubah fingerprint PLAN atau status PHYSICAL_PENDING.
