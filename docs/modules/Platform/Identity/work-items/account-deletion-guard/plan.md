# Plan: Pengaman Penghapusan Akun

| Increment | Status | Scope | Acceptance | Verifikasi |
| --- | --- | --- | --- | --- |
| 1 | Passed | Scope dan checklist | Acceptance, boundary, seeder skip dan QA tertulis sebelum coding | Review source/docs dan diff check PASS |
| 2 | Passed | Backend guard dan audit | Last-super, fresh state, rollback dan logout sesudah commit | 33 focused test/208 assertion, 181 full test/1055 assertion, Pint/route/diff PASS |
| 3 | In Progress | Feedback UI, QA dan delivery | Field error/Sonner, desktop/tablet, dokumentasi aktual | UI/frontend gate, DevTools smoke, workflow Playwright dan docs PASS; delivery berikutnya |

## Urutan

1. Dokumentasikan scope dan checklist; commit save point.
2. Reproduksi jalur last-super/audit failure dengan failing HTTP tests.
3. Application DeleteOwnAccount, operasi delete port/adapter, lalu controller.
4. Verifikasi ordinary/super deletion, validation, pivot cleanup, rollback,
   preservation sesi, dan relasi role auth yang stale. Commit backend.
5. UI dialog existing menampilkan account error dan Sonner; konfirmasi/password
   tetap wajib; processing mencegah duplicate submit/close.
6. QA memakai fixture SQLite disposable untuk blocked/allowed deletion, password
   salah, keyboard, framing desktop/tablet dan console. Jangan delete akun lokal.
7. Full gate, update docs/risiko, commit/push setelah scope selesai.

## Contract

DELETE /settings/profile, route profile.destroy, auth session + CSRF existing.
Input hanya password wajib/current_password; identity berasal request user.
Last-super menghasilkan validation field account (422 JSON atau session errors).
Password salah tetap field password. Success redirect / dan sesi diinvalidasi.
Audit/infrastructure failure tidak menghasilkan success atau logout.
