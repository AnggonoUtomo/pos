# WI-0014: Baseline shadcn/ui dan Layout Aplikasi

Status: Draf

## Tujuan

Menetapkan baseline UI aplikasi dengan shadcn/ui untuk admin ERP sidebar dan POS fullscreen.

## Rujukan

- `docs/SPEC.md`
- `docs/ARCHITECTURE.md`
- `docs/adr/ADR-0006-shadcn-ui-default-ui-system.md`
- `components.json`
- `docs/QA-AUTOMATION.md`

## Modul Target

- Category: Platform
- Module: UiShell
- Namespace/path: `resources/js/layouts`, `resources/js/pages`, `resources/js/components`
- Jenis pekerjaan: baseline shadcn/ui, admin ERP sidebar, dan POS fullscreen

## Scope

Masuk scope:

- Verifikasi konfigurasi shadcn/ui.
- Struktur layout `AppLayout` dan `PosLayout`.
- Struktur frontend feature-folder di `resources/js/pages`.
- Komponen dasar navigasi, tombol, form, dialog, dropdown, select, checkbox, tab, tooltip jika dibutuhkan.
- Standar ikon `lucide-react`.
- Dokumentasi aturan pemakaian komponen UI.

Di luar scope:

- Implementasi fitur transaksi POS lengkap.
- Desain struk final.
- Tema custom besar di luar konfigurasi shadcn/ui.

## Checklist Sebelum Coding

- [ ] Source Laravel dan frontend sudah diaudit.
- [ ] `components.json` dibaca.
- [ ] Struktur `resources/js` dipahami.
- [ ] Struktur feature-folder frontend dari `docs/ARCHITECTURE.md` dibaca.
- [ ] Komponen shadcn/ui yang sudah tersedia diidentifikasi.

## Rencana Increment

| Increment | Fokus | Checklist Sebelum | Checklist Sesudah | QA automated | Status |
| --- | --- | --- | --- | --- | --- |
| INC-01 | Audit shadcn/ui baseline | - [ ] `components.json` dibaca | - [ ] Komponen tersedia teridentifikasi | `npm run build` | Draf |
| INC-02 | Admin ERP sidebar layout | - [ ] Pola layout app dibaca | - [ ] Layout admin siap sebagai baseline | `npm run build`, pemeriksaan manual admin | Draf |
| INC-03 | POS fullscreen layout | - [ ] Kebutuhan desktop/tablet dipahami | - [ ] Layout POS fullscreen siap sebagai baseline | `npm run build`, pemeriksaan manual POS | Draf |
| INC-04 | Dokumentasi UI modular | - [ ] Struktur feature-folder disepakati | - [ ] Aturan folder UI tercatat | `git diff --check` | Draf |

## Kriteria Penerimaan

- [ ] shadcn/ui menjadi default untuk komponen UI baru.
- [ ] Admin layout dan POS layout punya arah struktur yang jelas.
- [ ] Page dan komponen fitur mengikuti pola `pages/{category}/{feature}/components`.
- [ ] Komponen shared hanya dipakai untuk komponen lintas fitur yang stabil.
- [ ] Tidak ada design system paralel.
- [ ] Build frontend berhasil.

## Verifikasi

- [ ] `npm run build`
- [ ] `npm run lint`
- [ ] Pemeriksaan manual tampilan admin dan POS jika halaman sudah tersedia.
