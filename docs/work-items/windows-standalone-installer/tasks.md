# Tasks: Installer POS Windows Standalone

## Status

PLAN diterima untuk memulai Increment 1. Tanggal: 22 November 2026.
MySQL tetap dipilih untuk aplikasi tertutup. Revisi 23 November 2026: dipasang
manual oleh admin, bukan dibundel; pengajuan Oracle dihentikan. Distribusi
penuh belum diimplementasikan; build privat/preflight dikerjakan terpisah.
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

## Revisi MySQL Manual Dan Pemulihan QA Windows

Instruksi awal 22 November: lengkapi izin Oracle dan uji Windows bersih.
Revisi 23 November: abaikan pengajuan Oracle; MySQL dipasang manual nanti.
Tidak mengubah scope bisnis atau memberi izin pembelian, install hypervisor
atau perubahan VM existing secara diam-diam.

- [x] Hentikan pengajuan Oracle dan tetapkan MySQL sebagai prasyarat manual.
  - Acceptance: tidak mengirim draft, membeli lisensi, mengganti engine atau
    mengklaim izin diperoleh. Kebutuhan identitas/SSO tidak dilanjutkan.
  - Verification: instruksi user, PRD/PLAN/ADR-022 diperbarui; draft privat
    ditandai dibatalkan dan tidak masuk Git/payload.
- [x] Selaraskan manifest dan preflight dengan prasyarat MySQL manual.
  - Acceptance: tidak ada MySQL pada komponen bundled/nama service milik POS;
    listener DB existing bukan konflik atau bukti koneksi valid. Distribusi
    tetap ditolak karena installer lengkap belum diimplementasikan.
  - Verification: test terfokus, publish native, manifest dan pesan preflight.
    RED listener DB terbukti, GREEN 45 kasus; publish PASS. CLI read-only
    exit 2 pada Windows 10/non-admin, databaseConnectionVerified=false.
    Manifest tidak membundel MySQL dan Distribution ditolak sebelum build baru.
- [ ] Implementasikan validasi koneksi/DB kosong/hak akses/alat backup manual.
  - Acceptance: Increment 2, tanpa mutasi service/datadir/akun MySQL existing,
    tanpa password pada argumen/log atau database toko sebagai fixture.
  - Verification: test dan MySQL disposable; belum tersedia pada preflight.
- [x] Periksa kesiapan lingkungan Windows secara read-only.
  - Acceptance: tidak install/start/stop hypervisor atau menyentuh VM pemilik.
  - Verification: CIM/registry/config metadata; VirtualBox 7.2.14 terpasang,
    Authenticode Valid/Oracle, dua VM terdaftar. Tidak ada VM dijalankan.
- [x] Konfirmasi VM khusus/ISO Windows 11/PC uji serta izin penggunaannya.
  - Hasil: user memilih WS-RTR2-01 dan menyetujui clone POS-QA-WS-RTR2-01;
    clone harus tanpa jaringan/shared folder sebelum boot, VM asal utuh.
- [x] Diagnosis interupsi dan verifikasi fingerprint VM asal setelah percobaan.
  - Verification: daftar VM/running VM, log VBoxSVC dan SHA256 seluruh VDI
    serta konfigurasi. Kedua hash identik dengan catatan sebelum percobaan;
    laporan privat qa-vm-source-after.json dan qa-vm-recovery-report.json.
- [ ] Buat clone penuh dan verifikasi isolasi serta fingerprint VM asal.
  - Acceptance: no overwrite, no snapshot restore/reset pada VM asal;
    seluruh NIC dan shared folder clone OFF sebelum boot.
  - Verification: showvminfo, hash konfigurasi/disk asal dan artefak QA privat.
  - Discovery: share RTR2 ternyata global. User menyetujui OFF sementara saat
    QA dan restore persis setelah clone mati; belum dijalankan saat staging.
  - Attempt 1: FAIL RPC_S_SERVER_UNAVAILABLE/0x800706ba; user mungkin end
    proses Oracle/VirtualBox lewat Task Manager. VM QA belum terdaftar;
    artefak parsial dipertahankan dan bukan image siap pakai. SHA256 config
    VM asal tetap identik, VM asal poweroff. Retry satu kali pada path baru.
  - Attempt 2: INTERRUPTED; pemeriksaan 23 November menemukan hanya VDI
    parsial 3.170.893.824 byte, tanpa konfigurasi/registrasi VM QA dan tanpa
    proses clone aktif. Log VBoxSVC merekam shutdown sesi Windows pada
    22 November pukul 17.16 WIB. Exit code akhir tidak tersedia; jangan
    menyalin diagnosis RPC attempt pertama ke attempt kedua.
  - Tidak ada retry ketiga otomatis atau penghapusan artefak parsial.
    Tidak ada VM diboot atau global share diubah; BLOCKED_ENVIRONMENT.
  - VS Code tidak diwajibkan ditutup. Tidak memaksa boot guest saat RAM host
    terbatas; clone powered-off bukan bukti Windows bersih lulus QA.
- [ ] Buktikan baseline bersih dan jalankan preflight native di Windows uji.
  - Acceptance: tidak ada data toko/dependency developer; OS/lisensi/snapshot
    dan checksum tercatat. VM existing tidak dianggap bersih dari namanya.
  - Verification: bukti runtime pada guest; metadata konfigurasi tidak cukup.
- [ ] Setelah installer lengkap tersedia, uji install/offline/reboot/LAN/recovery.
  - Acceptance: prerequisite implementasi Increment 2-4 benar-benar siap;
    preflight saja tidak menutup penerimaan installer.
  - Verification: skenario Increment 5 pada OS disposable.

Status sekarang: pengajuan Oracle **DIBATALKAN USER, TIDAK DIKIRIM**; izin
bundel MySQL **NOT_APPLICABLE** karena tidak dibundel. Windows bersih
**BELUM DIUJI**. Clone disetujui tetapi dua percobaan belum selesai;
tidak boot/reset atau mengubah VM asal. Tidak ada install atau QA OS yang lulus.

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
- [ ] Pin seluruh vendor bundled, validasi prasyarat manual dan build EXE lengkap.
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
    menggunakan PHP lokal. Runtime PHP/Apache bundled belum dipin/diuji,
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
- [ ] Validasi MySQL manual dan DB khusus kosong, katalog permission/role produksi.
  - Acceptance: loopback/akun khusus/hak minimum dan mysql/mysqldump tersedia;
    tidak memasang/mengubah service/datadir MySQL; tanpa migrate:fresh,
    seed demo/lifecycle, reset key/password atau memakai DB toko existing.
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
    DB/config/key/media/backup dan MySQL/QZ/WebView2/redist user tetap ada.
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
- [ ] Uji offline pada Windows bersih tanpa Laragon/tool developer, MySQL manual siap.
  - Acceptance: install/owner/login/LAN/background/reboot/repair/uninstall
    bekerja pada setiap OS yang diklaim, tanpa dependency dari PC pembuat;
    MySQL manual tercatat sebagai prasyarat, bukan dianggap ikut dibundel.
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
| Harness deployment | PASS | 45 kasus setelah revisi MySQL manual; termasuk RED listener DB lalu GREEN. Dua kasus baru melengkapi 43 pengaman awal |
| Restore locked + publish native | PASS | .NET 10.0.15 self-contained win-x64; NuGet lockfile dan source tooling dicatat |
| `composer install --no-dev --no-scripts --no-plugins` | PASS | 87 package dari composer.lock commit source, staging tanpa env; tidak menjalankan Artisan |
| `composer check-platform-reqs --no-dev --no-plugins` | PASS | PHP builder 8.4.16; bukan bukti PHP runtime bundled |
| `npm ci`, `tsc --noEmit`, ESLint nonmutating | PASS | Staging commit; warning deprecation ESLint existing dicatat tanpa upgrade di luar scope |
| Vite + CSS Tailwind | PASS sesudah koreksi | Ignore walker semula melewatkan source ignored. Marker boundary dan .gitignore commit memperbaiki; CSS final 104.423 byte, empat utilitas dasar diperiksa |
| Manifest + native verifier | PASS | 7.884 file dan checksum seluruh payload; fixture hash yang dirusak ditolak oleh EXE dan wrapper PowerShell |
| Preflight PC ini | PASS penolakan | Exit 2: Windows 10/non-admin ditolak; installationPerformed=false. Bukan INSTALL_VERIFIED |
| `-Distribution` | PASS penolakan | Setelah revisi: BLOCKED_IMPLEMENTATION sebelum build directory baru atau download, bukan izin rilis |
| Syntax PowerShell, diff, scope/link docs | PASS | Pemeriksaan terfokus, file LSP staged user tetap terpisah |
| Lisensi MySQL bundel aplikasi tertutup | NOT_APPLICABLE setelah revisi | MySQL/server/client tools tidak dibundel; dipasang manual admin. Pengajuan Oracle dibatalkan sebelum dikirim, bukan izin telah terbit |
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

### Hasil Revisi MySQL Manual, 23 November 2026

- PRD/PLAN/ADR-022, indeks dan README tooling memakai prasyarat MySQL manual;
  server/mysql/mysqldump tidak ada di daftar runtime bundled. Tidak ada
  pengajuan Oracle dikirim, MySQL dipasang, atau database toko diubah.
- Preflight hanya memeriksa konflik HTTPS/service POS, tidak mengklaim koneksi
  DB valid. Test listener DB existing FAIL sebelum perbaikan lalu 45 kasus
  PASS. Publish native berhasil tanpa restore/dependency baru.
- Artifact preflight terbaru:
  `storage/app/private/windows-installer/builds/manual-mysql-preflight-dcefeed1f1fd4a90a55f1ec0ca80f210/Pos.Setup.exe`.
  SHA256 `dc305ae82e84a9b654ddf2f1c4074fdfd084d0b43f634b548da51723de01418d`,
  116.131.982 byte. verification-report.json mencatat source hash/tooling dirty;
  bukan build aplikasi atau installer lengkap baru.
- CLI native aktual: exit 2, Windows 10/non-admin ditolak, installationPerformed
  dan databaseConnectionVerified keduanya false. Tidak ada service/registry/DB
  yang dimutasi. UI native visual dan Windows bersih tetap BELUM DIUJI.
- Manifest JSON dan penolakan Distribution PASS. Diff/link/scope diperiksa
  sebelum commit; regresi PHP/frontend tidak diulang karena source aplikasi
  POS, schema, asset web dan dependency tidak berubah. DevTools SKIPPED:
  perubahan native/preflight, bukan halaman web; bukan pengganti QA native.
- Konfigurasi dan seluruh disk VM asal cocok SHA256 sebelum/sesudah kedua
  interupsi. Tidak ada VM QA terdaftar atau boot; share global tidak diubah.
  Kedua disk parsial tetap privat dan bukan VM QA yang siap dipakai.

Save point mencakup hanya tooling, ignore build dan dokumentasi terkait;
work item keseluruhan belum selesai dan belum dipush.

Jangan menambah scope tanpa revisi PLAN/persetujuan atau menutup checklist
implementasi sebelum coding dan pemeriksaan terkait benar-benar selesai.
