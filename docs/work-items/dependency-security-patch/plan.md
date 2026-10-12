# Plan: Security Dependency

| Increment | Status | Isi | Acceptance | Verification |
| --- | --- | --- | --- | --- |
| 1 | Passed | Scope dan boundary | Persetujuan, chain/constraint dan versi resmi jelas | Review lock/caller/advisory dan diff |
| 2 | Ready | Update minimal dan regresi | Hanya target transitive berubah; kontrol normal tetap valid | Audit penuh, tree, regresi parser, review independen |
| 3 | Ready | Gate integrasi dan delivery | Stock tetap berfungsi; dokumen sesuai hasil | Full test, lint/typecheck/build, QA browser, commit/push |

## Strategi

Gunakan update package terarah, tanpa mengubah package.json/composer.json jika
constraint existing sudah kompatibel. Periksa diff lockfile agar package lain
tidak ikut diperbarui. Reproduksi parser hanya pada input kecil atau worker
timeout; jangan menjalankan payload DoS tak berbatas atau shell dari input.

Audit npm mencakup dev dependency dan production; Composer memakai lockfile.
Regresi membandingkan input berbahaya yang relevan dengan input normal. Untuk
DoS, bounded rejection/kontrol serta versi patched dan audit menjadi bukti;
bukan klaim load test produksi. Investigator dan reviewer read-only terpisah
memeriksa compatibility dan kandidat patch.

## Rollback

Gunakan revert commit terarah bila diperlukan; jangan reset worktree atau
menghapus perubahan user. Versi rentan bukan tujuan rollback deployment yang
aman: deployment ditahan sampai perbaikan kompatibel terverifikasi.
