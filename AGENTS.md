# Aturan Kerja Project

Panduan ini wajib dibaca sebelum agent atau developer mengubah kode di
workspace `C:\laragon\www\pos`.

## Konfigurasi Project

- Nama project: POS Modular ERP-Lite.
- Stack utama: Laravel 12, PHP 8.4, Inertia React, React 19, Vite, Tailwind CSS
  4, shadcn/ui, MySQL.
- Package utama: Spatie Laravel Permission dan Spatie Laravel Activitylog.
- Lokasi module: `app/Modules/{Domain}/{Module}`.
- Lokasi framework reusable: tidak ada untuk fase ini.
- Strategi identifier: ULID untuk primary key tabel utama.
- Mekanisme route frontend: Inertia React dengan Ziggy route helper.
- Command generator module: `php artisan module:make {Domain} {Module}`.
- Validasi module: dry-run generator, test terkait, `route:list`, dan
  pemeriksaan migration/status sesuai scope.

## Mulai Dari Konteks Yang Cukup

Sebelum mengubah kode, baca `docs/README.md`, lalu hanya dokumen yang relevan
dengan pekerjaan. Jangan membaca seluruh dokumentasi untuk tugas kecil dan
mandiri.

Sebelum membuat atau mengubah module, contract, port, adapter, generator, atau
struktur folder, wajib membaca `docs/ARCHITECTURE.md` dan
`docs/FOLDER-STRUCTURE.md`. Jika kode, generator, dan dokumen tidak selaras,
hentikan perubahan struktural dan laporkan konfliknya.

Jika pekerjaan menyentuh module, baca juga dokumen module di
`docs/modules/{Domain}/{Module}/` bila tersedia. Jika belum tersedia dan scope
module cukup signifikan, buat dokumen module sebelum coding.

## Cara Bekerja

- Utamakan perubahan kecil dan terfokus.
- Jangan memperluas scope atau menutup risiko lain tanpa persetujuan user.
- Pecah perubahan multi-file menjadi increment yang dapat diverifikasi.
- Jalankan test atau pemeriksaan yang proporsional dengan risiko perubahan.
- Pertahankan perubahan user yang tidak terkait.
- Jangan membuat branch, commit, push, atau memasang dependency tanpa permintaan
  eksplisit user.
- Untuk perubahan module, informasikan module target sebelum coding.
- Setiap work item module wajib memiliki rencana increment dan checklist sebelum
  coding dimulai.

## Arsitektur Dan Keamanan

- Gunakan DDD-lite Modular Monolith dengan Hexagonal Architecture.
- Arah dependency internal adalah `Presentation -> Application -> Domain` dan
  `Infrastructure -> Application -> Domain`.
- Domain tidak bergantung pada layer luar. Application tidak mengimpor adapter
  konkret Infrastructure.
- `Presentation` adalah inbound adapter, `Application` berisi use case dan port,
  `Infrastructure` berisi outbound adapter, dan `ServiceProvider.php` menjadi
  composition root module.
- Dependensi konkret lintas module dilarang. Gunakan public contract, DTOs, atau
  event publik yang memang memiliki consumer nyata.
- Backend adalah security authority. Frontend permission hanya untuk UX.
- Jangan menyimpan atau menampilkan secret, token, password, credential, atau
  payload sensitif dalam source, log, test output, maupun dokumentasi.

## Keputusan Domain Yang Tidak Boleh Dilanggar

- Single company untuk fase 1.
- Multi gudang, bukan multi cabang.
- User boleh memilih gudang saat transaksi.
- Satu transaksi memakai satu gudang untuk MVP.
- Stok tidak boleh minus.
- FIFO berjalan per `item + warehouse`.
- Semua stok disimpan dalam base unit terkecil.
- Harga jual berdasarkan customer level dan satuan.
- Public registration dimatikan.
- Posted transaction tidak boleh diedit bebas.
- Koreksi posted transaction lewat retur, void/reversal, atau adjustment yang
  audited.
- Activity log bukan ledger stok atau ledger finance.
- Finance Lite dipakai sebelum full accounting.

## Dokumentasi Pekerjaan

- Modul baru memakai `docs/modules/{Domain}/{Module}/`.
- Bagian module yang signifikan memakai
  `docs/modules/{Domain}/{Module}/work-items/{nama-pekerjaan}/`.
- Pekerjaan lintas module memakai `docs/work-items/{nama-pekerjaan}/`.
- Gunakan nama folder `kebab-case`; Domain dan Module mengikuti source code.
- Work item cukup memiliki `README.md`, `plan.md`, dan `tasks.md`.
- Tambahkan PRD untuk kebutuhan produk baru atau requirement yang belum jelas.
- Tambahkan ADR hanya untuk keputusan yang mahal atau sulit dibalik.
- Bug kecil, typo, dokumentasi sederhana, dan perubahan satu file tidak wajib
  memiliki folder kerja baru.

## Gaya Perubahan

- Ikuti pola Laravel dan Inertia React yang ada.
- Untuk UI, gunakan shadcn/ui dan `lucide-react` sebagai default sebelum membuat
  komponen custom.
- Untuk feedback operasi CRUD UI, gunakan Sonner toast dari shadcn/ui.
- Jangan membuat design system paralel tanpa persetujuan eksplisit.
- Letakkan komponen fitur dekat dengan page-nya:
  `resources/js/pages/{domain}/{module}/components`.
- Gunakan `resources/js/components/shared` hanya untuk komponen lintas fitur yang
  benar-benar umum dan stabil.
- CRUD master/operasional yang mutable memakai soft delete secara default kecuali
  ada alasan eksplisit.
- Transaksi posted, ledger, stock movement, FIFO layer, payment record, dan
  activity log tidak boleh memakai soft delete sebagai pengganti reversal, void,
  atau adjustment.
- Controller module yang butuh permission memakai `HasMiddleware` dan middleware
  `can:{permission}` per action, atau policy eksplisit.
- Frontend permission guard memakai `resources/js/hooks/use-permission.ts` dengan
  `isSuperSystem`; guard ini hanya untuk UX.
- Module baru harus menyimpan route dan database miliknya di dalam module:
  `Routes/`, `Database/Migrations/`, dan `Database/Seeders/`.
- Demo seeder module wajib dipertimbangkan untuk setiap module; isi bila ada
  data master/operasional relevan, atau catat alasan skip.

## Sesudah Coding

- Jalankan test yang relevan.
- Jalankan build/typecheck/lint jika tersedia dan relevan.
- Untuk UI/browser, coba QA via Chrome DevTools MCP sesuai `docs/QUALITY.md`
  atau catat alasan `SKIPPED/BLOCKED` beserta fallback.
- Jalankan migration/status check jika schema berubah.
- Verifikasi behavior utama secara manual jika UI atau transaksi berubah.
- Catat hasil command di work item atau implementation report.
- Isi checklist sesudah coding dan status increment pada work item.
- Jangan klaim selesai jika command gagal atau belum dijalankan.

## Bahasa Dan Handoff

- Dokumentasi proyek ditulis dalam Bahasa Indonesia.
- Nama tabel, kolom, class, method, route, permission, enum, DTOs, event,
  contract, dan namespace memakai English technical naming.
- Commit message mengikuti gaya ringkas seperti `feat:`, `fix:`, `docs:`, atau
  `chore:` jika user meminta commit.
- Pada handoff, laporkan perubahan, verifikasi, yang tidak disentuh, dan risiko
  terbuka secara ringkas.
