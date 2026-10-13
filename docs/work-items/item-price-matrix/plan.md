# Rencana Harga Barang

1. Increment 1: schema item_prices milik Parties, public contract/DTOs dengan
   consumer nyata, invariant matrix dan port lookup satuan jual milik Items.
   Test validation, price update/no-op/soft delete, level guard dan rollback.
2. Increment 2: integrasi save/list Items, matriks UI, rupiah exact dan seed
   harga demo. Adaptasi test existing ke contract save publik yang baru.
3. Increment 3: gate penuh, QA browser desktop/tablet, MySQL disposable/locking,
   migration additive lokal + demo harga eksplisit, fingerprint data existing,
   dokumentasi/bukti dan push. Tidak reset DB atau mengubah theme/Old-docs.

Urutan lock: level aktif terurut, global satuan terurut, barang, relasi/harga.
Mutation memakai current locking read. Commit setelah increment lulus.
