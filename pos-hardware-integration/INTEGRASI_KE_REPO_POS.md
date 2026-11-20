# Penempatan Folder Pegangan Codex di Dalam Project POS

**Keputusan Ino:** folder dokumentasi harus menjadi subfolder langsung dari **root repository POS yang sudah berjalan**. Nama folder: **`pos-hardware-integration`**, bukan `docs` dan bukan repository terpisah.

## Struktur yang benar

```text
<root-project-POS>/
├── AGENTS.md                     # jika sudah ada, BIARKAN
├── app/                          # contoh Laravel; struktur aktual bisa berbeda
├── routes/                       # contoh; BIARKAN
├── resources/                    # contoh; BIARKAN
├── src/                          # contoh frontend; BIARKAN
├── composer.json                  # contoh; BIARKAN
├── package.json                   # contoh; BIARKAN
└── pos-hardware-integration/      # folder pegangan BARU
    ├── AGENTS.md                  # berlaku sebagai panduan dalam subfolder
    ├── CODEX_START_HERE.md
    ├── README.md
    ├── INTEGRASI_KE_REPO_POS.md
    ├── blueprint/
    ├── implementation/
    ├── scripts/
    └── tasks/
```

Nama `app/`, `src/` dan sebagainya **hanya ilustrasi**, bukan instruksi untuk membuat folder tersebut. Root repo POS bisa berupa monorepo atau struktur lainnya; jangan memindahkan atau mengubahnya agar cocok dengan contoh.

## Cara menempatkan

1. Buka folder **root project POS asli** (yang memuat `.git` atau entrypoint project existing), bukan folder induknya.
2. Pastikan di root POS **belum ada folder `pos-hardware-integration/`**. Jika sudah ada, **jangan overwrite/merge** secara otomatis; periksa isinya dan minta arahan pemilik.
3. Salin atau ekstrak **hanya folder `pos-hardware-integration/`** dari paket ZIP v1.3 ke root project POS.
4. Pastikan hasilnya **persis satu tingkat**: `<root-project-POS>/pos-hardware-integration/README.md`, **bukan** `.../pos-hardware-integration/pos-hardware-integration/README.md`.
5. Pastikan folder/file aplikasi existing **tidak berubah**. `git status --short` boleh digunakan untuk memeriksa; jangan menambahkan, menghapus, stage, commit, atau push tanpa arahan Ino.
6. Buka Codex dengan working directory **root POS**. Instruksikan membaca `pos-hardware-integration/CODEX_START_HERE.md`.

## Aturan scope untuk Codex

- `AGENTS.md` di **root POS** (jika ada) tetap berlaku. `pos-hardware-integration/AGENTS.md` adalah tambahan yang lebih spesifik untuk dokumen pendamping, **bukan pengganti** aturan root.
- Codex yang membaca source POS dari root harus **secara eksplisit** membuka `pos-hardware-integration/CODEX_START_HERE.md`; jangan mengandalkan penemuan otomatis `AGENTS.md` di subfolder untuk mengendalikan perubahan source di luar subfolder.
- Sampai ada PLAN dan APPROVAL, aktivitas terhadap source existing **hanya read-only**. Rencana, laporan dan perubahan dokumen tinggal di `pos-hardware-integration/`.
- Posisi folder di dalam repo **tidak berarti** izin implementasi hardware. Coding, migrasi, dependency install, driver, akses printer, testing fisik dan deployment memiliki gerbang persetujuan tersendiri.

## Mulai dengan Codex

Saat Codex berada di root POS, gunakan pesan:

> Baca `pos-hardware-integration/CODEX_START_HERE.md`, ikuti aturan root `AGENTS.md` jika tersedia, dan lakukan hanya discovery read-only. Jangan mengubah file source POS sampai saya menyetujui PLAN.

Untuk validasi PLAN dari root POS:

```bash
python3 pos-hardware-integration/scripts/check_plan.py pos-hardware-integration/tasks/TASK-HW-001/PLAN.md
```

Jika PLAN belum dibuat, perintah validasi tersebut **belum perlu dijalankan**.
