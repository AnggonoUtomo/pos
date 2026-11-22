# Tasks: Installer POS Windows Standalone

## Status

PLAN Draft, implementasi belum diizinkan. Tanggal: 22 November 2026.
Checklist dicentang sesudah hasil diperiksa, bukan saat pekerjaan dimulai.

## Sebelum Mulai

- [x] AGENTS, README, ARCHITECTURE, FOLDER-STRUCTURE, WORKFLOW dan QUALITY dibaca.
- [x] Windows x64/offline/WebView2/server LAN dikonfirmasi user.
- [x] Opsi server otomatis dan database baru/setup pemilik dikonfirmasi user.
- [x] Ownership deployment/Identity/OperationalSettings disebut.
- [x] Bootstrap, seeder akun/permission/role, backup dan readiness diperiksa.
- [x] Referensi primer runtime/installer ditinjau; ketidakpastian dicatat.
- [x] Source baseline dan file LSP user dicatat; Old-docs tidak disentuh.
- [x] Acceptance, increment, QA dan rollback ditulis.
- [ ] PLAN/komponen/allowlist/ADR/pengecualian OS diterima user.
- [ ] Gate lisensi distribusi, dukungan OS, signing dan lingkungan QA dituntaskan.

## Increment 0: Dokumentasi Perencanaan

- [x] Susun README, PRD, plan dan tasks sesuai template project.
  - Acceptance: kebutuhan disepakati terpisah dari usulan teknis/izin coding.
  - Verification: review jawaban user dan source terkait.
- [x] Tambahkan ADR-022 Proposed dan tautan indeks dokumentasi.
  - Acceptance: tidak mengubah aturan aktif atau memasang OS service.
  - Verification: review status ADR dan diff tujuh dokumen target.
- [x] Periksa whitespace, link lokal dan scope staged dokumentasi.
  - Acceptance: hanya tujuh dokumen target dalam scope commit; tidak ada
    source/runtime berubah atau file LSP user yang diikutsertakan.
  - Verification: diff check, 53 tautan relatif valid, review diff scoped;
    commit wajib memakai path explicit, bukan seluruh index milik user.

## Increment 1: Payload Offline Terverifikasi

- [ ] Kunci manifest komponen, NuGet/build tool, sumber, hash dan lisensi.
  - Acceptance: ABI/arsitektur/OS cocok; signature/hash/lisensi gagal berarti
    berhenti, bukan bypass atau mirror acak. Exact file list dilengkapi dahulu.
  - Verification: verifier manifest, review vendor license/support matrix.
- [ ] Buat build staging dari source commit dan lockfile.
  - Acceptance: vendor produksi dan asset siap; tidak ada env/key/SQL/media/
    backup/cache/hot/LSP/driver privat atau dependency Laragon terselip.
  - Verification: allowlist payload, path traversal/symlink checks,
    `composer check-platform-reqs --no-dev`, typecheck/lint/build staging.
- [ ] Buat EXE bootstrap/preflight yang tidak mutasi saat gagal.
  - Acceptance: offline, konflik port/path/service jelas, tanpa takeover;
    UAC/lisensi/opsi OS terlihat, tidak download saat install.
  - Verification: PayloadTests, InstallationSafetyTests, instalasi disposable.
- [ ] Catat hasil dan commit scoped tanpa binary/runtime data.
  - Acceptance: source, manifest dan artifact/checksum terhubung.
  - Verification: review diff dan report; tidak push parsial otomatis.

## Increment 2: Setup Baru, Akun Pertama Dan Backup

- [ ] Implementasikan env/storage/cache/backup khusus standalone.
  - Acceptance: mode OFF identik baseline; backup fallback/output kompatibel;
    media publik tersedia tetapi private/backup/key tidak dapat diakses web.
  - Verification: StandaloneRuntimeTest, BackupPosTest, path dengan spasi.
- [ ] Provision DB baru privat, katalog permission aman dan role produksi.
  - Acceptance: loopback/credential unik/hak minimum; tanpa migrate:fresh,
    seed demo/lifecycle, reset key/password atau akses DB existing.
  - Verification: daftar data awal dan fingerprint DB disposable.
- [ ] Buat wizard lokal dan bootstrap owner CLI milik Identity.
  - Acceptance: password bukan argumen/log, hash/validation/audit atomic,
    satu super-system pertama, tanpa route setup/registrasi publik.
  - Verification: InitialOwnerTest (race, rerun, DB berisi akun/data,
    password invalid, audit/transaction gagal, interupsi setelah commit).
- [ ] Uji login dan backup/restore pada instalasi baru.
  - Acceptance: tidak ada akun baku; mysql/mysqldump tersedia; user web tidak
    diberi global CREATE/DROP. Profil toko belum diisi tetap pending operator.
  - Verification: login terisolasi dan restore fingerprint disposable;
    cek recovery meliputi config/key/media, bukan SQL saja.
- [ ] Catat hasil dan commit scoped.
  - Acceptance: tanpa perubahan transaksi/identifier/grant akun toko lama.
  - Verification: focused tests di atas dengan ENV/cache QA terpisah.

## Increment 3: Desktop, Background Dan LAN

- [ ] Bangun shell WebView2 dengan login dan status runtime.
  - Acceptance: non-admin, origin/profil terjaga, tanpa privileged JS bridge;
    shortcut/scanner/fokus/upload/download/popup struk/print dialog bekerja.
  - Verification: DesktopPolicyTests dan WebView2 aktual, bukan Chrome saja.
- [ ] Terapkan service otomatis/manual serta kontrol native admin.
  - Acceptance: close-window tidak stop server; tidak ada terminal berkala,
    service/ACL minimum dan tidak mengambil alih proses produk lain.
  - Verification: boot/reboot/close/crash/start-stop di Windows disposable.
- [ ] Terapkan TLS canonical LAN dan firewall privat.
  - Acceptance: origin/media bukan localhost klien; DB/private tertutup;
    CA unik dan trust sah, tanpa bypass TLS atau ubah router/hosts otomatis.
  - Verification: HTTPS/cookie/negative URL tests dan klien LAN; tablet fisik
    yang tidak tersedia tetap pending, emulasi diberi label.
- [ ] Sediakan Scheduler dan QZ opsional.
  - Acceptance: satu pemicu schedule:run tanpa window; jadwal tetap di Laravel.
    QZ existing tidak ditimpa, physical print tidak diaktifkan.
  - Verification: SchedulerTests, schedule:list, tick, dump terjadwal nyata,
    restore disposable dan WebView2-WSS QZ bila komponen dipilih.
- [ ] Catat hasil dan commit scoped.
  - Acceptance: opsi OFF dan kegagalan vendor bukan sukses palsu.
  - Verification: QA alur berubah saja; jangan ulang semua tema/viewport.

## Increment 4: Repair, Update, Uninstall Dan Recovery

- [ ] Implementasikan repair/update offline dengan ownership/backup gate.
  - Acceptance: akun/key/DB/media tidak direset; aktivasi setelah health;
    downgrade tidak kompatibel ditolak.
  - Verification: failure injection disk/port/migration/service, retry dan
    fingerprint sebelum-sesudah pada instalasi disposable.
- [ ] Implementasikan uninstall tanpa purge data/shared runtime.
  - Acceptance: hanya executable/service/rule milik instalasi dihapus;
    DB/config/key/media/backup dan QZ/WebView2/redist user tetap ada.
  - Verification: clean install -> repair/update -> uninstall -> inspeksi data.
- [ ] Buktikan recovery dan rollback tanpa menghilangkan transaksi baru.
  - Acceptance: file rollback hanya schema kompatibel; jika tidak aman,
    maintenance/admin, bukan silent restore/down-migration.
  - Verification: restore disposable termasuk media/config/key, skenario
    schema tidak reversible, review panduan.
- [ ] Catat hasil dan commit scoped.
  - Acceptance: laporan menyebut batas backup pada PC yang sama.
  - Verification: review bukti/diff.

## Increment 5: Penerimaan Paket Utuh

- [ ] Jalankan satu gate regresi lengkap pada kandidat final.
  - Acceptance: PHP/native tests dan typecheck/lint/build lulus; fixture
    shift -> POS lunas -> stok/FIFO/payment/invoice -> backup konsisten.
  - Verification: command PLAN, DB/ENV/cache disposable; reuse hasil unchanged.
- [ ] Uji offline pada Windows bersih tanpa Laragon/tool developer.
  - Acceptance: install/owner/login/LAN/background/reboot/repair/uninstall
    bekerja pada setiap OS yang diklaim, tanpa dependency dari PC pembuat.
  - Verification: snapshot VM/mesin bersih, network-off, checksum artifact.
- [ ] Tulis panduan installer dan laporan penerimaan.
  - Acceptance: BUILD/INSTALL/LAN/STORE/PHYSICAL dipisahkan; pending printer,
    operator, tablet atau backup terpisah tidak ditutup tanpa bukti.
  - Verification: review panduan Bahasa Indonesia dan hasil QA aktual.
- [ ] Commit akhir dan push ketika seluruh paket selesai/diinstruksikan.
  - Acceptance: hanya source/docs; executable privat dengan checksum,
    tidak ada secret, dump atau binary vendor dalam Git.
  - Verification: diff scoped, status git dan remote sesudah push berizin.

## Sesudah Implementasi

- [ ] Scope dan acceptance selesai, bukan checklist berdasarkan dugaan.
- [ ] Hasil command/test/build/OS dicatat, QA yang tidak relevan diberi alasan.
- [ ] DevTools PASS atau BLOCKED/SKIPPED dengan fallback yang jelas.
- [ ] WebView2 aktual, clean install/reboot/TLS/LAN/recovery diverifikasi.
- [ ] ADR, dokumen module, panduan operator dan batas izin OS selaras.
- [ ] Data toko, cache produksi, Old-docs dan LSP user tetap tidak berubah.

## Hasil Verifikasi Perencanaan

| Pemeriksaan | Hasil | Catatan |
| --- | --- | --- |
| Discovery source/runtime requirements | PASS | Read-only, bukan production readiness |
| `git diff --cached --check -- <tujuh path dokumen>` | PASS | Tidak ada error whitespace pada scope dokumen |
| Pemeriksaan tautan Markdown relatif | PASS | 7 dokumen, 53 tautan, 0 target hilang |
| Review scope/status | PASS | ADR Proposed, PLAN Draft; lima increment implementasi belum dicentang |
| Test/build/installer/OS/browser | NOT RUN | Dokumentasi saja, implementasi belum ada |

Jangan menambah scope tanpa revisi PLAN/persetujuan atau menutup checklist
implementasi sebelum coding dan pemeriksaan terkait benar-benar selesai.
