# Tasks: Posting Retur Penjualan Layak Jual

## Sebelum Mulai

- [x] AGENTS.md, docs/README.md, ARCHITECTURE.md dan FOLDER-STRUCTURE.md dibaca.
- [x] Spesifikasi Returns/Stock dan ADR-012 dibaca; kontrak/source terkait diperiksa.
- [x] User menyetujui scope barang layak jual/gudang asal/biaya FIFO asal,
  kumulatif terjual, atomicity dan replay; refund/karantina/tukar ditunda.
- [x] Module target diumumkan; worktree user dipisahkan dan dipertahankan.
- [x] Baseline rencana/checklist dibuat sebelum coding.

## Increment 1: Baseline Dan Keputusan Domain

- [x] Catat scope, ownership, dependency dan batas pekerjaan.
  - Acceptance: status rencana dibedakan dari implementasi; Finance tidak berubah.
  - Verification: review source/dokumen aktif, link relatif dan diff --check.
- [x] Tetapkan urutan layer barang retur.
  - Acceptance: jawaban eksplisit user; ADR membedakan biaya asal dari umur FIFO.
  - Verification: review keputusan terhadap Stock dan invariant posted immutable.
- [x] Tetapkan valuasi partial/residual termasuk alokasi biaya nol.
  - Acceptance: tidak mengubah HPP asal, menciptakan nilai, membuang residual,
    atau menerima layer yang membuat pengeluaran berikutnya gagal.
  - Verification: contoh partial/full dan test plan conservation.
- [x] Finalisasi ADR serta contract/schema/lock plan sebelum mutation ledger.
  - Acceptance: ada consumer nyata dan aturan replay, master nonaktif, privacy,
    overflow serta urutan lock yang jelas.
  - Verification: review dependency dan keputusan accepted.

## Increment 2: Stock Writer

- [ ] Tambahkan unit test red, domain alokasi dan test conservation.
  - Acceptance: exact quantity/value, partial berulang, full drain residual,
    beberapa source consumption, invalid/overflow dan batas kumulatif.
  - Verification: focused unit test red-green.
- [ ] Tambahkan schema/port/DTOs/adapter Stock dengan trace consumption asal.
  - Acceptance: receipt/movement/layer immutable dan source unik; histori sale
    utuh; tidak membaca model/DB privat POS atau Returns.
  - Verification: feature test, migration disposable dan route inspection.
- [ ] Buktikan locking, replay dan rollback audit.
  - Acceptance: dua draft bersaing tidak melewati sisa barang terjual; retry sama
    tidak menggandakan stok; audit gagal membatalkan seluruh mutation.
  - Verification: feature tests dan native MySQL disposable dengan cleanup.

## Increment 3: Returns Dan UI

- [ ] Tambahkan action/metadata/status posting dan permission backend terpisah.
  - Acceptance: shared transaction, stale ditolak, replay aman, posted tidak
    edit/hapus/restore; tidak menghitung atau mengeluarkan refund.
  - Verification: focused test termasuk super-system dan rollback lintas module.
- [ ] Tambahkan konfirmasi, status/detail/filter, shortcut dan Sonner.
  - Acceptance: HPP tidak bocor; posted readonly; loading/error/retry berfungsi.
  - Verification: typecheck, ESLint, build, DevTools MCP dan screenshot.
- [ ] Verifikasi desktop/tablet Light/Dark dan expanded/collapsed.
  - Acceptance: tidak overflow/overlap; permission visibility benar.
  - Verification: QA1440x1000,1024x768,768x1024; mobile di luar scope.
- [ ] Pertimbangkan demo posting relasional yang eksplisit dan aman.
  - Acceptance: local/testing, idempotent, tidak menimpa/posting draft user;
    alasan skip dicatat bila tidak ada fixture yang aman.
  - Verification: seeder test atau review alasan skip.

## Increment 4: Penutupan

- [ ] Jalankan full regression, formatter, frontend gate dan native MySQL.
  - Acceptance: semua command tercatat dengan hasil aktual; no-negative/FIFO
    penjualan dan pembelian existing tidak regresi.
  - Verification: test, Pint, tsc, ESLint, build, QA native/browser.
- [ ] Aktifkan schema/katalog lokal tanpa grant/demo/reset/posting.
  - Acceptance: data existing dan invoice/Finance asal identik; activation repeat
    idempotent; migration status sesuai realita.
  - Verification: fingerprint, activation dua kali, migrate:status.
- [ ] Perbarui API/module/ADR dan checklist, scoped commit lalu push.
  - Acceptance: klaim sesuai bukti; remote sama HEAD; file user tidak ikut.
  - Verification: diff --check, staged review, ls-remote, divergence/status.

## Bukti Saat Ini

Baseline dokumentasi saja. Source Returns, POS, Stock dan ADR-012 diperiksa;
pilihan urutan layer sudah ditanyakan melalui interview satu pertanyaan.
User menyetujui layer baru saat retur diterima. Keputusan layer biaya nol
ditanyakan terpisah; belum ada pelonggaran invariant existing atau implementasi.
Belum ada runtime change, migration, test behavior baru atau perubahan ledger.
Gate test/build/browser tidak relevan sampai implementasi dimulai.
Verifikasi baseline: link relatif dokumen diperiksa; git diff --check PASS.

Increment 1 Passed: user mengonfirmasi biaya nol hasil alokasi. ADR-015 accepted,
schema/lock/port plan ditetapkan; harga input tetap positif. Belum ada mutation
ledger pada penutupan baseline ini.
