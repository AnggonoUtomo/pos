# Rencana Empat Paket

1. Inventory/Transfers + Inventory/Stock: posting transfer mempertahankan biaya
   FIFO asal, layer tujuan baru pada waktu posting, sumber/destinasi/status/audit
   atomik dan idempotent. Koreksi stok memakai jalur audited, bukan edit saldo.
2. Sales/POS + Sales/Returns: diskon persen/nominal dan pajak dihitung server,
   checkout lunas, struk/detail dan refund memakai nilai bersih snapshot.
3. Platform/CompanySettings + Reporting/OperationalReports: identitas toko dan
   ringkasan penjualan/pembelian/retur/stok/kas dengan filter dan permission.
4. Persiapan penggunaan: aktivasi migration/permission tanpa reset data,
   backup/restore teruji, konfigurasi produksi, skenario operator dan blocker.

Commit otomatis setelah bagian terverifikasi; push setelah paket utuh selesai.
Perubahan user pada SampleUI/storage dan folder Old-docs tidak disentuh.
