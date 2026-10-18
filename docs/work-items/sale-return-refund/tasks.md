# Tasks: Refund Retur Penjualan Lunas

## Sebelum Mulai

- [x] `AGENTS.md` dan `docs/README.md` dibaca.
- [x] Arsitektur, struktur canonical, workflow dan template work item dibaca.
- [x] Dokumen Returns/Payments dan source posting/lookup/writer/history ditinjau.
- [x] User menyetujui scope lunas, nominal otomatis, metode berbeda dan hasil nol.
- [x] Target Returns dan Finance diumumkan sebelum perubahan dokumen.
- [x] Scope Increment 1 dibatasi dokumentasi; tidak coding/runtime/DB change.
- [x] Ownership, dependency, acceptance dan QA automated ditetapkan.
- [x] Dampak schema/route/permission/UI/audit/demo/ledger/Stock/pricing dicatat.
- [x] Sebelum coding, baca ulang worktree, dokumen QA dan source target terkini;
  tutup desain schema/contract/locking pada Increment 2, bukan mengandalkan asumsi.

## Increment 1: Baseline Dan Checklist

- [x] Tulis scope, non-scope dan hasil interview dalam README.
  - Acceptance: membedakan retur fisik, hak refund dan uang keluar; tidak
    mengklaim nominal/HPP/retur lama sudah dibayar.
  - Verification: review terhadap jawaban user dan source yang ditinjau.
- [x] Susun dependency increment dan risiko rounding/replay/concurrency.
  - Acceptance: valuasi/ADR mendahului schema/writer; alokasi nol tetap mencatat
    kuantitas; QA native/browser serta aktivasi aman mempunyai owner.
  - Verification: review `plan.md` dan checklist ini.
- [x] Perbarui rencana/checklist kedua module dan status spesifikasi Returns.
  - Acceptance: refund disetujui tetapi belum tersedia; karantina/tukar/reversal
    tetap terpisah dan tidak ikut dianggap disetujui.
  - Verification: periksa link lokal dan `git diff --check`.
- [x] Review staged scope dokumentasi untuk save point.
  - Acceptance: hanya dokumen aktif yang masuk commit; file user dan `Old-docs`
    tidak tersentuh. Belum push karena work item refund belum selesai.
  - Verification: staged diff/nama file, whitespace dan status Git.

## Increment 2: Valuasi Dan Desain Contract

- [x] Baca ulang QA, source dan baseline yang relevan sebelum coding.
  - Acceptance: tidak ada konflik struktural; konflik dilaporkan sebelum edit.
  - Verification: review architecture/folder/source/`git status --short`.
- [x] Kunci ADR persistence/locking, payload quote dan settlement, serta replay.
  - Acceptance: ownership alokasi/ledger, source refund, version/canonical hash,
    stale quote dan lock invoice lintas retur eksplisit; tidak mengakses model
    privat POS/Stock/Finance dari module lain.
  - Verification: review ADR/kontrak/API dan daftar consumer nyata.
- [x] Tambah test red lalu aturan valuasi decimal murni.
  - Acceptance: partial/full/residual/zero, nominal besar/overflow, original net
    invalid, multi-line/satuan serta urutan refund berbeda diuji; tidak float.
  - Verification: unit tests Returns; bukti red-green dan scoped Pint.
- [x] Tetapkan rekonsiliasi nilai net baris dengan invoice/penerimaan asal.
  - Acceptance: tidak refund tendered cash/kembalian/HPP; snapshot unsupported
    ditolak tanpa mutation dan tidak diam-diam kehilangan diskon/pajak.
  - Verification: unit/contract tests dan review public lookup POS.

## Increment 3: Writer Refund Finance

- [ ] Tambah schema milik Finance dan published writer/DTOs untuk Returns.
  - Acceptance: source refund unik/terpisah dari purchase; shared transaction,
    immutable ledger, snapshot aman dan down guard posted.
  - Verification: migration up/down disposable dan feature tests Finance.
- [ ] Validasi pembayaran tepat dengan metode/akun aktif dan kompatibel.
  - Acceptance: 1..10 metode unik/positif scale2, tanpa kembalian; account caller,
    duplicate, nol, total mismatch dan konfigurasi berubah ditolak.
  - Verification: feature invalid-input/master-current-read/overflow.
- [ ] Implementasikan canonical replay, mandatory audit dan guard histori master.
  - Acceptance: retry identik tanpa audit/payout baru; perubahan isi ditolak;
    audit veto rollback; rename tidak mengubah snapshot lama.
  - Verification: feature Finance dan regresi sale/purchase/master guards.

## Increment 4: Settlement Returns

- [ ] Tambahkan quote, alokasi dan settlement hanya untuk retur posted.
  - Acceptance: lock/current read invoice/alokasi, hitung ulang exact; quote stale
    memerlukan konfirmasi ulang; tidak memakai total/harga bebas dari client.
  - Verification: feature quote/stale/version/permission/tampered payload.
- [ ] Selesaikan total positif melalui writer dan total nol tanpa ledger.
  - Acceptance: alokasi/status/audit atomic, nol tanpa metode; draft/trashed
    ditolak, dokumen selesai readonly dan replay memakai hasil tersimpan.
  - Verification: feature zero/positive/replay termasuk super-system invariants.
- [ ] Buktikan rollback lintas module dan source tetap immutable.
  - Acceptance: audit Returns gagal setelah Finance tidak meninggalkan ledger;
    invoice/penerimaan/Stock/movement/layer/consumption tidak berubah.
  - Verification: audit-veto test dan fingerprint sebelum/sesudah.
- [ ] Uji dua proses MySQL pada invoice sama dan duplicate source.
  - Acceptance: tidak over-refund/deadlock permanen/duplikasi; residual uang dan
    kuantitas exact, termasuk quote yang tadinya sama lalu menjadi stale.
  - Verification: QA native disposable dengan worker nyata dan cleanup.

## Increment 5: UI Dan Riwayat

- [ ] Tambahkan UI quote/konfirmasi pembayaran serta hasil tanpa uang.
  - Acceptance: nominal otomatis format Indonesia, split tepat, permission,
    readonly, Sonner, shortcut dropdown dan icon tooltip semantic.
  - Verification: typecheck/lint/build dan browser alur positif/nol.
- [ ] Lengkapi state loading/error/retry/stale dan timeout recovery.
  - Acceptance: tidak submit ganda atau menimpa pilihan baru dengan quote lama;
    retry tidak dianggap payout baru dan tidak menampilkan sukses palsu.
  - Verification: browser controlled 403/422/500/network dan feature replay.
- [ ] Integrasikan pengeluaran refund positif ke riwayat Finance existing.
  - Acceptance: sumber dapat dikenali; filter/detail snapshot benar tanpa HPP,
    rekening/hash; sale scale2/purchase scale6 tidak rusak; nol tidak masuk ledger.
  - Verification: history feature tests dan browser snapshot/filter/detail.
- [ ] Jalankan QA desktop/tablet Light/Dark dan sidebar expanded/collapsed.
  - Acceptance: 1440x1000, 1024x768 dan 768x1024 tanpa overlap/overflow; console
    dan network tak terduga bersih. Mobile di luar scope.
  - Verification: Chrome DevTools MCP dahulu; fallback dan alasan dicatat bila perlu.

## Increment 6: Aktivasi Dan Delivery

- [ ] Jalankan full regresi, lint/typecheck/build, Pint dan review boundary.
  - Acceptance: seluruh gate relevan PASS; tidak mengklaim QA yang belum jalan.
  - Verification: command pada `plan.md`, native MySQL dan browser artifacts.
- [ ] Siapkan/jalankan aktivasi lokal additive dua kali.
  - Acceptance: migration dan catalog permission saja, tanpa grant/reset/demo/
    payout; data existing dan alokasi historis tidak diubah otomatis.
  - Verification: migration/status/route dan fingerprint kolom existing/data.
- [ ] Dokumentasikan alasan tidak membuat seeder pembayaran refund otomatis.
  - Acceptance: demo master CCTV/kelistrikan dipertahankan; skenario refund
    relasional ada di fixture disposable, bukan pembayaran dari DB user.
  - Verification: review seeder/fixture dan laporan aktivasi.
- [ ] Tutup dokumen aktual dan lakukan commit/push keseluruhan work item.
  - Acceptance: checklist berbukti, README/spec/API/QA/module index akurat,
    file user dikecualikan, SHA remote sama HEAD dan divergence 0/0.
  - Verification: staged diff, `git diff --check`, push dan remote/status check.

## Sesudah Tahap Dokumentasi

- [x] Review isi, status dan seluruh link lokal pada dokumen yang berubah.
- [x] `git diff --check` dan staged scope lulus.

Commit otomatis dilakukan setelah review staged scope dan whitespace lulus;
identitas commit tersedia pada riwayat Git. Tidak push selama work item refund
belum selesai kecuali diminta eksplisit.

## Sesudah Coding Keseluruhan Work Item

- [ ] Semua acceptance refund terpenuhi dan increment implementasi verified.
- [ ] Unit/feature/full test dan native MySQL PASS.
- [ ] Typecheck, lint, build dan scoped Pint PASS.
- [ ] Migration/status dan route PASS.
- [ ] Chrome DevTools QA PASS atau BLOCKED/SKIPPED dengan alasan dan fallback.
- [ ] Fingerprint Stock/FIFO/invoice/payment asal serta aktivasi lokal PASS.
- [ ] Dokumen aktual, risiko terbuka dan remote delivery diperbarui.

## Hasil Verifikasi

| Pemeriksaan | Hasil | Catatan |
| --- | --- | --- |
| Review source/dokumen/interview | PASS | Baseline disusun; refund belum ada di runtime. |
| `git diff --check` Increment 1 | PASS | Dokumen tracked tidak memiliki whitespace error. |
| Pemeriksaan link/whitespace Increment 1 | PASS | Node memeriksa 8 dokumen dan 31 link lokal valid, termasuk file baru. |
| Staged scope/whitespace Increment 1 | PASS | Hanya 8 dokumen target, tanpa source/DB/file user/arsip. |
| TDD RED, dua test Domain sebelum implementasi | EXPECTED FAIL | 59 gagal karena dua class belum ada; bukan kegagalan gate akhir. |
| TDD GREEN awal | PASS | 59 test / 86 assertion setelah implementasi Domain. |
| Unit Returns dan feature basis invoice | PASS | 76 test / 1142 assertion setelah tambahan tipe non-string dan konservasi500 alokasi. |
| Feature Returns/POS/Finance | PASS | 102 test / 898 assertion; sumber posted, pembayaran bersih dan privacy lookup draft. |
| Scoped Pint lima file PHP | PASS | Dua value object dan tiga file test. |
| `php artisan test --compact` Increment 2 | PASS | 592 test / 5188 assertion, 54,90 detik. |
| `vendor/bin/pint --test` lima file dan `php artisan route:list --path=sales/returns` | PASS | Formatter lulus; sembilan route existing, belum ada quote/endpoint refund. |
| Frontend build/typecheck/lint dan Chrome DevTools | SKIPPED | Increment 2 tidak mengubah UI/JS/CSS/HTTP; QA UI tetap wajib pada Increment 5. |
| Migration/native concurrency refund | Belum dijalankan | Tidak ada migration/locking writer di Increment 2; wajib Increment 3..6. |
| Review staged scope/link/whitespace/boundary Increment 2 | PASS | 17 file terfokus, 12 dokumen/66 link lokal valid, dua Domain bebas framework; tidak ada file user/arsip ikut stage. |

## Handoff Increment 2

- Review sebelum coding selesai; tidak ditemukan konflik struktur pada scope.
- Class runtime baru hanya `RefundAllocation` dan `RefundInvoiceBasis`, bebas
  Laravel/HTTP/Eloquent; consumer operasional dipasang pada increment berikutnya.
- Tiga file test memakai unit PHPUnit dan fixture POS CCTV/kelistrikan di SQLite
  testing. Tidak menyentuh database/session user atau membuat payout demo.
- Contract dan schema pada ADR/API berstatus desain, belum endpoint aktif.
- Tidak push karena Increment 3..6 belum selesai. Commit otomatis setelah
  review akhir link/whitespace/boundary/staged scope lulus.

Checklist setelah Increment 2:

- [x] Unit/feature/full test dan scoped Pint lulus.
- [x] Sembilan route existing tetap; tidak ada schema/endpoint/UI baru.
- [x] Dokumen aktual dan batas QA yang belum dikerjakan dijelaskan.
- [x] Review staged scope, link, whitespace, privacy dan arah dependency lulus.

Jangan menambahkan scope ke checklist tanpa persetujuan user. Commit otomatis
setelah task/increment verified; push setelah keseluruhan work item selesai.
