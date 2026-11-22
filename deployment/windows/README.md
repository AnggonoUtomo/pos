# Build POS Windows Standalone

## Status

Implementasi awal Increment 1: staging aplikasi dan alat preflight internal.
**Belum installer produksi.** Belum ada provision MySQL, akun pertama, desktop
WebView2, service, TLS LAN, repair atau uninstall. Tidak menjalankan installer
pada PC toko untuk menguji hasil build.

User memilih distribusi aplikasi tertutup dan MySQL Community gratis.
[Kebijakan OEM Oracle](https://www.mysql.com/about/legal/licensing/oem/) belum
memberikan dasar izin bundel untuk keputusan tersebut. `-Distribution` selalu
berhenti sebelum download/build/mutasi sampai gate distribusi dituntaskan.
Tidak membeli lisensi, mengganti MySQL atau mengganti lisensi POS otomatis.

## Isi

- `components.lock.json`: SDK portable terkunci beserta SHA512 dari metadata
  Microsoft; status vendor lainnya eksplisit pending, bukan hash placeholder.
- `app-files.json`: source build dan payload yang boleh disalin.
- `build.ps1`: build privat dari commit aplikasi, test pengaman, EXE preflight,
  lalu opsional vendor produksi/aset frontend di staging baru.
- `src/Pos.Setup`: preflight WinForms read-only dan verifier payload.
- `tests/Pos.Deployment.Tests`: assertion harness .NET tanpa test SDK eksternal.
- `scripts/verify-payload.ps1`: pemeriksaan ulang payload menggunakan verifier
  yang sudah dibangun. Tidak menjalankan Laravel atau mengakses database.

Semua SDK, download, staging dan report berada di
`storage/app/private/windows-installer/`, diabaikan Git. Jangan membagikan
direktori tersebut sebagai installer; staging mengandung dependency builder.
Hanya `application/` hasil akhir yang dinilai sebagai payload aplikasi.
Jika ada recovery build, ikuti `applicationDirectory`/`applicationManifest`
pada `build-report.json`; artefak pada `discardedArtifacts` tidak boleh dipakai.

## Menjalankan Build Privat

Dari root repository, memakai PowerShell Windows bawaan:

```powershell
./deployment/windows/build.ps1 -SourceCommit <commit-40-karakter> -FetchBuildTools -ApplicationPayload
```

`-FetchBuildTools` hanya mengunduh SDK ZIP ke folder privat bila belum ada;
bukan menjalankan installer SDK global. Hash SHA512 diperiksa sebelum ekstraksi,
publisher `dotnet.exe` harus Microsoft/Authenticode Valid, versi SDK harus sama
dengan `global.json`. Jangan mengubah ExecutionPolicy global atau bypass proteksi
Windows bila script diblokir; minta admin meninjau script dan kebijakan setempat.

Tanpa `-ApplicationPayload`, hanya test dan EXE preflight yang dibangun.
Build memerlukan koneksi internet dan tool pembuat PHP 8.4/Composer/Node/npm;
ini bukan dependency yang akan diwajibkan pada komputer klien. Runtime PHP
bundled sendiri baru dinilai setelah pin ABI/lisensi vendor dituntaskan.

Pipeline aplikasi:

1. `git archive` hanya path allowlist pada commit, bukan menyalin workspace.
2. Tolak symlink/submodule/traversal/duplicate dan file privat pada source.
   Marker `.git` kosong membatasi pencarian ignore Tailwind ke staging;
   `.gitignore` dari commit menghindari scanning vendor/node_modules. Tidak ada
   metadata repository atau marker tersebut yang masuk payload.
3. `composer install --no-dev --no-scripts --no-plugins` dari lockfile.
   Tidak memanggil seeder, `artisan`, migrasi atau key generation.
4. `composer check-platform-reqs --no-dev`, `npm ci`, typecheck, lint tanpa
   `--fix`, lalu build Vite. Environment aplikasi builder tidak diwariskan.
5. Salin payload final tanpa node_modules/build cache/storage lokal. Buat
   `application-manifest.json`: commit, panjang file dan SHA256 setiap file.
   Build juga menolak CSS yang kehilangan utilitas dasar Tailwind; exit Vite
   saja tidak cukup untuk meluluskan staging di dalam direktori ignored.
6. Verifier menolak file tambahan/hilang/berubah, direktori privat walau kosong,
   env/SQL/key/media/cache/hot/LSP, serta symlink/junction.
7. `build-report.json` menghubungkan commit aplikasi, hash script/EXE/manifest/
   lockfile dengan hasil. Tooling belum committed ditandai, tidak disamarkan
   sebagai build dari commit bersih.

Build gagal tidak menghapus staging atau data lain. Periksa artefak gagal
sebelum membuat build baru; tidak ada recursive cleanup otomatis.

## Pemeriksaan Terfokus

Dengan SDK portable yang sudah tersedia, dari root repository:

```powershell
./storage/app/private/windows-installer/tools/dotnet-10.0.404/dotnet.exe run --project deployment/windows/tests/Pos.Deployment.Tests/Pos.Deployment.Tests.csproj -c Release --no-restore
```

Gunakan environment proses yang sama dengan builder: `DOTNET_ROOT`,
`DOTNET_CLI_HOME`, `NUGET_PACKAGES` menunjuk direktori privat dan
`DOTNET_GENERATE_ASPNET_CERTIFICATE=false`. SDK dapat membuat sertifikat
development pada first-run tanpa opsi terakhir; builder menonaktifkannya.
Jangan memakai `dotnet test` untuk harness ini: tidak ada test adapter yang
akan menemukannya. Exit nonzero dan nama kasus adalah indikator gagal.

`Pos.Setup.exe --preflight-defaults` membaca OS/admin/port/folder/nama service;
exit `0` berarti pemeriksaan awal lulus, `2` konflik/prasyarat belum terpenuhi,
`1` error pembacaan/argumen. Exit 0 bukan izin lisensi atau bukti install.
Windows 11 x64 adalah kandidat; lifecycle edisi dan QA OS bersih tetap wajib.
EXE tanpa argumen membuka tampilan pemeriksaan, tanpa tombol install semu.
EXE self-contained dapat mengekstrak library native ke cache sementara .NET;
tidak memasang runtime global atau service. Artefak belum code-signed.

## Batas Keamanan Dan Handoff

Manifest hash membuktikan integritas terhadap manifest, bukan autentisitas
publisher. Penandatanganan rilis dan distribusi manifest tepercaya tetap gate
paket akhir. Preflight bukan pengganti pemeriksaan ulang sebelum mutasi pada
installer lengkap. Disk minimum/opsi/path final masih perlu pengukuran paket
vendor; alat awal tidak meluluskan kebutuhan disk instalasi penuh.

Ketentuan [Inno Setup LICENSE](https://jrsoftware.org/files/is/license.txt)
mengizinkan pemakaian komersial dengan syarat yang tercantum. Permintaan
pembelian pada situs tidak dicatat sebagai kewajiban membeli otomatis.
Tetap pertahankan notice/lisensi semua komponen sebelum mendistribusikan paket.

Database/akun/cache produksi, driver, QZ dan konfigurasi Laragon tidak dibaca
untuk payload atau diubah. File LSP user dan Old-docs tidak dipakai. Ikuti
[PLAN](../../docs/work-items/windows-standalone-installer/plan.md) dan
[checklist aktual](../../docs/work-items/windows-standalone-installer/tasks.md)
sebelum melanjutkan increment berikutnya.
