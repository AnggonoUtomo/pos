# Tasks: Installer POS Windows Standalone

## Status

PLAN diterima untuk memulai Increment 1. Tanggal: 22 November 2026.
MySQL tetap dipilih user untuk aplikasi tertutup; izin bundel belum terbukti.
Distribusi penuh BLOCKED_LICENSE, build privat/preflight dikerjakan terpisah.
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
- [x] PLAN/komponen/allowlist/ADR/pengecualian OS diterima user.
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

Pecahan kerja pada increment ini (tidak menandai paket lengkap lulus):

- [x] Staging aplikasi dari commit, manifest file/hash dan penolakan data privat.
- [x] Test negatif payload/path/konflik dan preflight native read-only.
- [ ] Gate distribusi MySQL, pin seluruh vendor dan build EXE offline lengkap.
- [ ] QA Windows disposable; tidak menggunakan PC toko sebagai target install.

- [ ] Kunci manifest komponen, NuGet/build tool, sumber, hash dan lisensi.
  - Acceptance: ABI/arsitektur/OS cocok; signature/hash/lisensi gagal berarti
    berhenti, bukan bypass atau mirror acak. Exact file list dilengkapi dahulu.
  - Verification: verifier manifest, review vendor license/support matrix.
- [ ] Buat build staging dari source commit dan lockfile.
  - Acceptance: vendor produksi dan asset siap; tidak ada env/key/SQL/media/
    backup/cache/hot/LSP/driver privat atau dependency Laragon terselip.
  - Verification: allowlist payload, path traversal/symlink checks,
    `composer check-platform-reqs --no-dev`, typecheck/lint/build staging.
  - Hasil parsial: payload aplikasi PASS, 7.884 file; tool builder masih
    menggunakan PHP lokal. Runtime PHP/Apache/MySQL bundled belum dipin/diuji,
    sehingga item payload offline lengkap ini belum ditutup.
- [ ] Buat EXE bootstrap/preflight yang tidak mutasi saat gagal.
  - Acceptance: offline, konflik port/path/service jelas, tanpa takeover;
    UAC/lisensi/opsi OS terlihat, tidak download saat install.
  - Verification: PayloadTests, InstallationSafetyTests, instalasi disposable.
  - Hasil parsial: EXE preflight self-contained dibangun dan CLI read-only
    diuji; bukan Inno installer/wizard instalasi lengkap. Tidak ada opsi OS
    aktif, service dibuat, atau klaim native UI/clean install sudah lulus.
- [x] Catat hasil dan commit scoped tanpa binary/runtime data.
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

## Hasil Aktual Increment 1 (Parsial)

| Pemeriksaan | Hasil | Catatan |
| --- | --- | --- |
| SDK portable | PASS | 10.0.404, SHA512 cocok metadata Microsoft, dotnet.exe Authenticode Valid/Microsoft; tidak memasang SDK global |
| Harness deployment | PASS | 43 kasus. RED nyata pada parent path berupa file dan direktori privat kosong; GREEN setelah koreksi |
| Restore locked + publish native | PASS | .NET 10.0.15 self-contained win-x64; NuGet lockfile dan source tooling dicatat |
| `composer install --no-dev --no-scripts --no-plugins` | PASS | 87 package dari composer.lock commit source, staging tanpa env; tidak menjalankan Artisan |
| `composer check-platform-reqs --no-dev --no-plugins` | PASS | PHP builder 8.4.16; bukan bukti PHP runtime bundled |
| `npm ci`, `tsc --noEmit`, ESLint nonmutating | PASS | Staging commit; warning deprecation ESLint existing dicatat tanpa upgrade di luar scope |
| Vite + CSS Tailwind | PASS sesudah koreksi | Ignore walker semula melewatkan source ignored. Marker boundary dan .gitignore commit memperbaiki; CSS final 104.423 byte, empat utilitas dasar diperiksa |
| Manifest + native verifier | PASS | 7.884 file dan checksum seluruh payload; fixture hash yang dirusak ditolak oleh EXE dan wrapper PowerShell |
| Preflight PC ini | PASS penolakan | Exit 2: Windows 10/non-admin ditolak; installationPerformed=false. Bukan INSTALL_VERIFIED |
| `-Distribution` | PASS penolakan | BLOCKED_LICENSE sebelum build directory baru atau download |
| Syntax PowerShell, diff, scope/link docs | PASS | Pemeriksaan terfokus, file LSP staged user tetap terpisah |
| Lisensi MySQL bundel aplikasi tertutup | BLOCKED_LICENSE | Pilihan versi gratis user dicatat; kebijakan Oracle bukan izin otomatis. Tidak membeli/mengganti engine |
| Vendor runtime lengkap, signing, OS disposable, WebView2/instalasi aktual | PENDING | Tidak menutup Increment 1 penuh atau mulai provisioning produksi |
| PHP regresi/migration/browser DevTools | SKIPPED | Tidak ada source/schema/UI web POS berubah; QA native UI dan clean install tetap pending, bukan diganti mock/Chrome |

### Artefak Privat Dan Provenance

Root build: `storage/app/private/windows-installer/builds/095b625c1b73-19cfb292c5e6402ba024533819ccb270/`.

- Commit aplikasi: `095b625c1b73c2ea1df7e289dbaa4603ba5367c0`.
- Payload diterima: `application-verified/`, bukan `source/` atau `application/`
  lama yang CSS-nya tidak lengkap. Artefak lama tetap ada tetapi ditolak report.
- Manifest: `application-verified-manifest.json`, SHA256
  `593def5c7522e326364c0d45b16a337241f8de722deef9f2be742bc0f41bfbe0`.
- EXE internal `preflight/Pos.Setup.exe` 116.131.982 byte, SHA256
  `d195cb3d561e5aabdd51bcfd1f07ff3f53b871394b2e8db65c930401912039f7`.
- `build-report.json` menghubungkan manifest aplikasi, lockfile dan tooling;
  `tooling-manifest.json` menyimpan hash source build. Tooling belum committed
  ketika build dijalankan ditandai eksplisit. Binary/report tidak masuk Git.
- Gate sebelumnya yang tidak terdampak dipakai kembali. Setelah temuan Tailwind,
  hanya frontend diperbaiki/dibangun ulang; pemeriksaan arsitektur native
  diperbaiki dan publish/probe terkait saja diulang. Tidak mengulang regresi POS.

### Efek Samping SDK Yang Sudah Ditangani

First-run SDK sempat membuat satu ASP.NET development certificate pada
CurrentUser/My, 22 November 2026 16:30:35. Sertifikat baru tersebut dihapus
secara exact thumbprint setelah subject/OID/waktu diperiksa; tidak pernah
ditrust dan sudah tidak ditemukan. Builder kini menetapkan
`DOTNET_GENERATE_ASPNET_CERTIFICATE=false` serta cache/CLI home privat.
Tidak mengubah sertifikat POS/Laragon, database, service, firewall, QZ atau
driver. Jangan memakai cleanup semua sertifikat untuk mengatasi kejadian ini.

Save point mencakup hanya tooling, ignore build dan dokumentasi terkait;
work item keseluruhan belum selesai dan belum dipush.

Jangan menambah scope tanpa revisi PLAN/persetujuan atau menutup checklist
implementasi sebelum coding dan pemeriksaan terkait benar-benar selesai.
