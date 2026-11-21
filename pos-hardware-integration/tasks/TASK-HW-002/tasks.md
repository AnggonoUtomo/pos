# Tasks: Uji Scanner Virtual Read-Only

## Sebelum Coding

- [x] Baca aturan root/companion dan koreksi scope organisasi dari user.
- [x] Commit/push TASK-HW-001 lebih dahulu; LSP user tidak ikut commit.
- [x] Verifikasi HEAD sama dengan origin/main dan public lookup Items cukup.
- [x] Baca generator/provider/page-guide/QA cache isolation tanpa menjalankannya.
- [x] Susun exact allowlist dan rencana satu alur scanner utuh.
- [x] Validasi struktur PLAN dan fingerprint kandidat.
- [x] User menyetujui scope, file dan execution QA pada APPROVAL.md.
- [x] Validator --require-approved lulus sebelum source/generator/test dijalankan.

## Increment 1: Backend Scanner Terisolasi

- [x] Dry-run generator di environment testing terisolasi; buat module canonical.
  - Acceptance: hanya empat file scaffold terdaftar; tidak overwrite source.
  - Verification: dry-run output, pemeriksaan allowlist, diff.
- [x] Buat test feature/env/permission dan lookup sebelum implementasi.
  - Acceptance: OFF/production ditolak termasuk super-system; auth/verified/
    inventory.view enforced ketika local/testing ON.
  - Verification: focused Hardware tests, red lalu green setelah implementasi.
- [x] Lengkapi provider/routes/query/request/controller dan no-op seeder.
  - Acceptance: public Items contract, hasil identitas saja; tidak ada mutation,
    grant, schema atau model/repository lintas module privat.
  - Verification: lookup leading zero, barcode/SKU precedence, nonaktif/terhapus/
    purchase-only, validation/error/no-store, fingerprint data identik.

## Increment 2: Halaman Dan Virtual Input End-To-End

- [x] Buat UI input/hasil/error/queue/cancel dan riwayat uji sementara.
  - Acceptance: shared input/theme, icon semantic, tidak mengubah cart atau
    harga/stok; scan sama dua kali tercatat dua kali, cap20 dan stale aman.
  - Verification: fake keyboard+Enter melalui harness browser terisolasi.
- [x] Lengkapi shortcut, PageGuide F1 dan Markdown operator.
  - Acceptance: desktop/tablet dapat digunakan, input/modal tidak dicuri fokus;
    virtual vs physical jelas; tidak ada menu atau tab placeholder aktif.
  - Verification: page-guides, typecheck/lint/build dan browser representatif.

## Increment 3: Regresi, Evidence Dan Handoff

- [x] Jalankan satu gate regresi akhir sesuai PLAN, reuse hasil source identik.
  - Acceptance: POS scan/checkout/replay/FIFO/payment/receipt tetap lulus flag OFF;
    tidak mengaktifkan fitur di PC toko atau memakai live DB.
  - Verification: test subset, barcode-unit, browser/report dan allowlist.
- [x] Tulis REPORT dan jalankan check_report.py.
  - Acceptance: verdict hanya scanner VIRTUAL_PASSED bila benar-benar lulus,
    hardware PHYSICAL_PENDING, deployment NOT_APPROVED.
  - Verification: bukti test/screenshot/commands dan review scope source.
- [x] Perbarui metadata companion existing yang tercantum di allowlist.
  - Acceptance: status tidak lagi memakai asumsi repo belum tersedia, tenant
    atau physical USB verified; tidak mengaktifkan task/fitur berikutnya.
  - Verification: review silang STATUS/NEXT_TASK/HANDOFF/TASK_INDEX dan REPORT.
- [x] Handoff batas izin Git/aktivasi sesuai scope; tidak auto-push.
  - Acceptance: tidak menganggap approval source sebagai izin OS/produksi;
    tidak memasukkan file user ke commit.
  - Verification: git status dan tanggapan pemilik.

## Status Aktual

COMPLETE untuk scanner virtual. Implementasi, test dan QA disposable dilakukan
sesudah approval khusus task. Existing source/data/setting POS tidak diubah;
fitur tetap OFF dan production menolak. Hardware fisik PHYSICAL_PENDING.
REPORT memuat command, hasil, batas, kegagalan harness yang sudah diperbaiki
dan bukti akhir. Izin commit/push diterima pada 21 November 2026; perubahan
akses produksi memerlukan PLAN tersendiri, tanpa mengubah approval ini.

## Verifikasi Perencanaan: 21 November 2026

- `python pos-hardware-integration/scripts/check_plan.py
  pos-hardware-integration/tasks/TASK-HW-002/PLAN.md`: PASS.
- SHA256 kandidat PLAN:
  `665F6076563C5F7355B8FBE433894A89D0728ED7797C532F365732204861DFAA`.
  Approval kemudian APPROVED atas user "ok, setuju bro"; validator
  --require-approved lulus sebelum implementasi. Fingerprint PLAN tetap identik.
- Commit TASK-HW-001 `5e3b4a51ca3da1689ee224ffc393f6971b564984`
  terkonfirmasi sama dengan remote origin/main lewat git ls-remote.
- Pemeriksaan perencanaan awal PASS: empat artefak rencana, tujuh link lokal valid,
  33 path allowlist unik, 24 path source/test/dokumen module baru belum ada,
  empat metadata companion existing tersedia, whitespace/newline dan hash valid.
- `git diff --check`: PASS; diff source aplikasi terhadap HEAD kosong.
  Empat entry index LSP user tetap memiliki blob yang sama seperti sebelum task.
- Implementasi dan QA runtime kini PASS sesuai REPORT:69 test/680 assertion,
  focused tests setelah koreksi test/harness, typecheck/lint/build2959 module,
  page-guides33 dan browser12 cek/fingerprint71 tabel identik.
- check_report.py PASS. Tidak ada migration/seed database toko, service OS,
  hardware IO, source existing atau aktivasi produksi.
- User kemudian mengizinkan commit/push TASK-HW-002. File LSP staged/untracked
  user tetap dipertahankan; eksekusi Git dilakukan sebagai save point berikut.
