# APPROVAL: TASK-HW-011

**Status: APPROVED**
**Owner:** Pemilik project POS.
**Tanggal rencana:** 21 November 2026.
**PLAN:** [Version 1](PLAN.md).
**SHA256:** `10DD7FB3D65ACEB12A122C7CB7B4BE472FC0FB56E57104F384DAFF5860654D41`.
**Persetujuan:** 21 November 2026, user menjawab:
"Setuju, aktifkan diagnostik scanner sesuai PLAN".

- [ ] PENDING: perencanaan saja, belum ada izin implementasi/aktivasi.
- [x] APPROVED: exact PLAN, QA disposable dan aktivasi .env/cache disetujui.
- [ ] REVISE: revisi scope sebelum implementasi.

User meminta fitur ON di production setelah commit/push pekerjaan sebelumnya.
PLAN menjelaskan pembukaan scanner diagnostics saja: flag default source tetap
OFF, hanya instalasi ini ON; login/permission/read-only tetap berlaku.
Runtime .env/cache, QA disposable dan backup privat tercantum pada allowlist.
Printer/gateway/hardware fisik tidak otomatis verified atau diizinkan dipasang.

Commit/push TASK-HW-002 telah selesai pada 96bddfe. Persetujuan PLAN baru
diterima terpisah setelah PLAN disusun, bukan disimpulkan dari izin Git.
PLAN dipertahankan sebagai snapshot; status pelaksanaan pada tasks/REPORT.
