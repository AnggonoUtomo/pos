# Plan: Keranjang Tertahan

1. Test RED own scope/no ledger, retry/version/cancel, reprice dan linked checkout.
2. Tambah migration/model ULID/port/repository serta use case draft. Integrasikan
   optional source checkout tanpa mengubah canonical payload checkout lama.
3. UI dialog/hook lokal POS, protect cart replacement, keyboard dan recovery.
4. Focused test saat coding; satu regresi lengkap, frontend gate dan browser
   desktop/tablet. MySQL disposable membuktikan row lock dua checkout bersaing.
5. Migration additive produksi, route cache, panduan/checklist, scoped commit
   setiap increment dan push saat capability selesai.

Urutan lock checkout: warehouse existing -> invoice replay lookup -> held cart
source -> levels/customer/unit/item/price -> stock -> Finance -> invoice/audit.
Save/cancel hanya lock held row; baca master save diselesaikan sebelum lock row
agar tidak memperkenalkan urutan held -> warehouse yang berlawanan.
