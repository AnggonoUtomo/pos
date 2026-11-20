# Tasks: Discovery Dan Mockup Hardware POS

## Sebelum Mulai

- [x] Baca root AGENTS dan instruksi companion, termasuk virtual/physical gates.
- [x] Baca dokumen arsitektur, struktur, kualitas dan module relevan.
- [x] Catat worktree user; jangan stage/revert file LSP atau companion.
- [x] Konflik tenant/cabang dikonfirmasi user: tidak berlaku untuk POS ini.
- [x] Scope hanya dokumentasi dalam folder TASK-HW-001.

## Increment 1: Baseline Dan Boundary

- [x] Inventarisasi source checkout, RBAC, katalog, barcode, label dan receipt.
  - Acceptance: nama route/file aktual, tanpa menyalin data toko atau secret.
  - Verification: pembacaan source statis, BASELINE.md.
- [x] Susun matriks dampak dan alternatif additive.
  - Acceptance: source allowlist kosong; tidak ada tenant/cabang atau rewrite POS.
  - Verification: review silang IMPACT_MATRIX.md dengan file source disebut.

## Increment 2: Mockup Seluruh Workspace

- [x] Buat mockup terpisah dari runtime dengan data CCTV/kelistrikan fiktif.
  - Acceptance: ringkasan/perangkat/scanner/label/antrean/panduan dan failure states.
  - Verification: MOCKUP.html, MOCKUP.md dan review layout.
- [x] Render desktop dan tablet landscape/portrait.
  - Acceptance: tanpa horizontal overflow halaman, teks/aksi terlihat, request
    ke aplikasi/perangkat nol; emulasi bukan uji Android fisik.
  - Verification: screenshot offline dan ringkasan hasil pada MOCKUP.md.

## Increment 3: PLAN Dan Penyerahan

- [x] Lengkapi PLAN dan ajukan APPROVAL (awalnya PENDING).
  - Acceptance: exact file allowlist dokumen, flag OFF proposal, rollback,
    regresi future, virtual/physical terpisah; tidak self-approve.
  - Verification: check_plan.py tanpa --require-approved.
- [x] Periksa whitespace, link dan batas perubahan.
  - Acceptance: perubahan hanya task; status staged user tetap dipertahankan.
  - Verification: git diff --check, pemeriksaan artefak dan git status.
- [x] Review hasil oleh user dan approval mockup/PLAN.
  - Acceptance: persetujuan eksplisit tercatat, bukan asumsi dari konfirmasi scope.
  - Verification: user "ok, approved bro." pada20 November2026; APPROVAL.md
    APPROVED untuk discovery/desain saja. Fingerprint PLAN tetap identik.

## Sesudah Pekerjaan

- [x] Laporkan hasil validasi dokumen dan batas pemeriksaan di MOCKUP.md.
- [x] Tegaskan software NOT_RUN, physical PHYSICAL_PENDING, rollout NOT_APPROVED.
- [x] Berhenti sebelum coding; tidak commit/push sebelum izin Git terpisah.

Tidak ada checklist implementasi source yang boleh dimulai dari task ini.

## Hasil Verifikasi Aktual

- Validator struktur PLAN: PASS menggunakan Python existing (`python`);
  alias `python3` Windows Store tidak tersedia. Tidak instal interpreter.
- Setelah persetujuan user, validator `--require-approved`: PASS. SHA256
  PLAN identik dengan fingerprint yang disetujui, tanpa mengedit PLAN.
- Mockup: PASS pada4 viewport, kelima tab/panduan, root tanpa overflow,
  console error0, external request0. MCP tidak tersedia; fallback browser
  existing offline. Bukan uji POS, Android fisik atau hardware software.
- Allowlist: tepat12 artefak; pemeriksaan setelah approval menemukan18 link
  Markdown lokal valid. Whitespace/final newline PASS; render sebelumnya
  dipakai kembali karena HTML/screenshot tidak diubah.
- `git diff --check`: PASS. File task untracked diperiksa juga lewat Node,
  bukan mengandalkan git diff yang tidak menyertakan untracked.
- `git diff HEAD --name-only -- app resources config bootstrap routes database
  composer.json composer.lock package.json package-lock.json AGENTS.md docs`:
  kosong. Tidak ada perubahan aplikasi/root docs dibanding HEAD.
- Status staged/untracked LSP user tetap sama. User kemudian memberi izin Git
  terpisah untuk commit/push companion saja, sebelum lanjut menyiapkan PLAN.
- Review pemilik dan APPROVAL: APPROVED untuk discovery/desain. Test
  aplikasi/DB/build: NOT_RUN, sesuai batas dokumentasi. Hardware software
  NOT_RUN; PHYSICAL_PENDING. Implementasi/deployment tetap tidak diizinkan.
