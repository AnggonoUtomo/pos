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

- [x] Tambah schema milik Finance dan published writer/DTOs untuk Returns.
  - Acceptance: source refund unik/terpisah dari purchase; shared transaction,
    immutable ledger, snapshot aman dan down guard posted.
  - Verification: migration up/down disposable dan feature tests Finance.
- [x] Validasi pembayaran tepat dengan metode/akun aktif dan kompatibel.
  - Acceptance: 1..10 metode unik/positif scale2, tanpa kembalian; account caller,
    duplicate, nol, total mismatch dan konfigurasi berubah ditolak.
  - Verification: feature invalid-input/master-current-read/overflow.
- [x] Implementasikan canonical replay, mandatory audit dan guard histori master.
  - Acceptance: retry identik tanpa audit/payout baru; perubahan isi ditolak;
    audit veto rollback; rename tidak mengubah snapshot lama.
  - Verification: feature Finance dan regresi sale/purchase/master guards.

## Increment 4: Settlement Returns

- [x] Tambahkan quote, alokasi dan settlement hanya untuk retur posted.
  - Acceptance: lock/current read invoice/alokasi, hitung ulang exact; quote stale
    memerlukan konfirmasi ulang; tidak memakai total/harga bebas dari client.
  - Verification: feature quote/stale/version/permission/tampered payload.
- [x] Selesaikan total positif melalui writer dan total nol tanpa ledger.
  - Acceptance: alokasi/status/audit atomic, nol tanpa metode; draft/trashed
    ditolak, dokumen selesai readonly dan replay memakai hasil tersimpan.
  - Verification: feature zero/positive/replay termasuk super-system invariants.
- [x] Buktikan rollback lintas module dan source tetap immutable.
  - Acceptance: audit Returns gagal setelah Finance tidak meninggalkan ledger;
    invoice/penerimaan/Stock/movement/layer/consumption tidak berubah.
  - Verification: audit-veto test dan fingerprint sebelum/sesudah.
- [x] Uji dua proses MySQL pada invoice sama dan duplicate source.
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
| Migration/native concurrency pada Increment 2 | Belum dijalankan saat itu | Tidak ada migration/locking writer di Increment 2; hasil writer terbaru di bawah. |
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

## Handoff Increment 3

Implementasi pada 19 Oktober2026 milik Finance/Payments. Writer tidak menerima
harga/HPP/akun bebas dan tidak memverifikasi hak retur sendiri; consumer Returns
harus memeriksa posted, nominal dan quote di Increment 4 sebelum memanggil writer.
Interface/DTOs writer/choices dipublikasikan untuk consumer yang telah dirancang
tersebut, tanpa skeleton consumer/endpoint pura-pura pada increment ini.

| Pemeriksaan | Hasil | Bukti ringkas |
| --- | --- | --- |
| TDD RED awal writer | EXPECTED FAIL | 11 test gagal karena DTO/writer/schema belum ada. |
| TDD GREEN writer awal | PASS | 11 test / 94 assertion. |
| Focused writer dan RefundSettlement | PASS | 26 test / 116 assertion. |
| `php artisan test --compact` | PASS | 618 test / 5304 assertion, 56,91 detik. |
| `php scripts/qa/sale-refund-payment-mysql.php` | PASS | DB random disposable, native migrate/status/empty down-up, decimal30,2, 3 worker LOCK WAIT, satu payout saat retry, current read status/mapping, nominal28 digit, audit veto, histori master dan FK/down guard. Cleanup selesai. |
| `php artisan migrate:status` lokal | PASS untuk inspeksi | Migration refund baru Pending; semua migration lama Ran. Tidak menerapkan schema/payout di DB user; aktivasi Increment 6. |
| Browser/JS build/lint/typecheck | SKIPPED | Tidak ada perubahan UI/HTTP/JS/CSS; Chrome DevTools dan matriks desktop/tablet dijalankan saat UI disentuh. |
| Demo payout | SKIPPED dengan alasan | Refund bukan data master; QA menggunakan master kas/bank fiktif hanya di DB disposable. Tidak membuat seeder pembayaran otomatis. |
| Scoped Pint dan `php artisan route:list --path=finance` | PASS | Enam belas file PHP terfokus; 15 route Finance existing, tidak menambah endpoint payout. |
| QA native ulang setelah guard koneksi | PASS | URL override dinonaktifkan dan SELECT DATABASE() diverifikasi sebelum migration/writer; seluruh skenario native dan cleanup kembali lulus. |
| Review staged diff/link/whitespace/boundary | PASS | 28 file terfokus, 12 dokumen/53 link lokal valid, 16 file PHP; Application tidak mengimpor adapter dan Domain bebas framework. Pemeriksaan pola credential lulus. |

Checklist setelah Increment 3:

- [x] Schema, validasi, snapshot/replay, audit dan histori master terverifikasi.
- [x] Regresi sale/purchase/Returns tetap lulus; ledger lama tidak dipakai ulang.
- [x] Migration lokal Pending dibedakan dari disposable yang sudah Ran/up-down.
- [x] QA native selesai dan worker/database dibersihkan.
- [x] Scoped Pint, route, review boundary, link dan staged diff akhir dicatat.

Commit otomatis dilakukan setelah gate akhir lulus; identitas tersedia pada
riwayat Git. Belum push karena work item refund belum selesai.

## Handoff Increment 4

Implementasi 19 Oktober2026 melengkapi consumer Returns tanpa mengubah lookup
draft, posted header/version, source invoice, penerimaan atau Stock/FIFO.
Quote readonly tidak membuat scope. Schema yang belum tersedia menghasilkan422
refund. Request client tidak boleh menyertakan total/harga/akun/aktor bebas;
quote dihitung ulang di bawah scope lock dan revision yang berubah ditolak.

| Pemeriksaan | Hasil | Bukti ringkas |
| --- | --- | --- |
| TDD RED backend/source | EXPECTED FAIL | 10 test awal belum memiliki permission refund; satu lookup test gagal karena method belum ada. |
| Lookup GREEN | PASS | Projection safe terpisah, 1 test/5 assertion. |
| Focused settlement/source terbaru | PASS | 15 test/124 assertion; positive/zero/residual, source immutable, replay, multi-unit, stale/version/tamper, guest/permission/super-system, disabled/veto audit, missing/trashed/overflow, source invalid dan harga/master berubah. |
| `php artisan test --compact` | PASS | 633 test/5428 assertion, 59,67 detik. |
| `php scripts/qa/sale-return-refund-mysql.php` | PASS | Empty up/down/status, quote readonly, empat worker LOCK WAIT, initial scope race/stale quote dan dua retry, replay JSON, nol/sen terakhir, audit Returns sesudah Finance rollback, FK/down guard. Semua child/DB dibersihkan. |
| `php artisan route:list --path=sales/returns` | PASS | 11 route; quote/refund baru diproteksi sales.returns.refund. |
| `php artisan migrate:status` lokal | PASS untuk inspeksi | Finance refund dan Returns settlement Pending; seluruh migration lama Ran. Tidak menjalankan migrasi/demo/payout/grant di DB user. |
| UI/browser/JS gates | SKIPPED | Tidak ada perubahan UI/JS/CSS; contract backend memakai feature/native QA. Chrome DevTools dan desktop/tablet pada Increment 5. |
| Scoped Pint | PASS | Delapan belas file PHP target, termasuk test dan skrip QA. |
| Review staged scope/link/whitespace/boundary | PASS | 34 file terfokus, 16 dokumen/68 link lokal valid. Application tidak mengimpor adapter atau Domain privat lintas module; pemeriksaan pola credential lulus. |

Checklist setelah Increment 4:

- [x] Rencana dan dependency dibaca sebelum coding; schema/contract mengikuti ADR.
- [x] Focused/full test serta native concurrency/rollback/immutability lulus.
- [x] Readiness guard dan status lokal Pending dijelaskan tanpa klaim aktivasi.
- [x] Scoped Pint dan review akhir boundary/link/whitespace/staged scope dicatat.

Commit otomatis setelah gate akhir lulus, tanpa push sampai keseluruhan work item
selesai. UI/history dan aktivasi tetap pekerjaan berikutnya, bukan scope tambahan
yang dikerjakan diam-diam pada increment ini.

Jangan menambahkan scope ke checklist tanpa persetujuan user. Commit otomatis
setelah task/increment verified; push setelah keseluruhan work item selesai.
