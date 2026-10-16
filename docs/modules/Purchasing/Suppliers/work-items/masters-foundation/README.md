# Work Item: Fondasi Master Supplier

## Status Dan Owner

In Progress. Purchasing/Suppliers, disetujui 17 Oktober 2026.
Baseline 15464e9. Requirement [PRD](PRD.md); contract [specification](../../specification.md).
Urutan [plan](plan.md), checklist/bukti [tasks](tasks.md).

## Scope

Master supplier dengan kontak opsional, status aktif, soft delete/restore,
permission view/manage, audit atomic/privacy, demo CCTV/kelistrikan dan UI admin.
Migration/provider/route/database di module, ULID, tanpa Domain untuk CRUD sederhana.
Native MySQL QA serta aktivasi lokal additive tanpa mengubah data/grant lama.

## Tidak Dikerjakan

Purchase order, penerimaan stok/FIFO, utang, pembayaran, retur, export, histori
transaksi supplier, lookup/event tanpa consumer, theme, dependency atau auth baru.

## Acceptance Dan Risiko

- [ ] CRUD/restore, normalisasi/unique/validasi, status dan permission benar.
- [ ] Audit wajib atomic, no-op tanpa audit, kontak tidak bocor.
- [ ] Demo relevan/idempotent, mempertahankan existing/trashed dan tidak grant.
- [ ] UI/shortcut/palette dan QA desktop/tablet Light/Dark lulus.
- [ ] Migration/aktivasi aman, docs/checklist/commit/push terverifikasi.

Risiko: kode tetap terreservasi sesudah hapus; manage-only bukan view;
guard supplier terpakai belum ada karena belum ada dokumen purchasing.
Aktivasi demo hanya menambah kode yang belum ada, bukan mereset master.
Old-docs, SampleUI, empat LSP user, credential dan data transaksi tidak disentuh.
