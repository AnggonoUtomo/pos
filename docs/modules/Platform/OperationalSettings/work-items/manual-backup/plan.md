# Rencana

1. Test RED akses, hasil manual, kegagalan sanitized, audit dan pengaman overlap.
2. Ubah target command ke base_path backups dengan ignore Git; pertahankan
   compatibility precheck aktivasi untuk backup lama. Lock dump lintas pemanggil.
3. Action manual melalui BackupExecutor dan audit repository existing;
   endpoint POST manage/CSRF, tombol konfirmasi/progress/Sonner dan status manual.
4. Focused test, lint/typecheck/build dan QA browser disposable dengan executor
   stub (tanpa dump bisnis dari browser). Uji command dump/restore lokal terpisah.
5. Aktifkan konfigurasi produksi dan jadwal lokal melalui action audited,
   cache setelah QA; dokumentasikan pemicu admin/UAT/perangkat masih pending.
