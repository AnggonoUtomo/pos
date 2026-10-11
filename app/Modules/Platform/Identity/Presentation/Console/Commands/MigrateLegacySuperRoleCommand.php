<?php

namespace App\Modules\Platform\Identity\Presentation\Console\Commands;

use App\Modules\Platform\Identity\Application\Actions\MigrateLegacySuperRole;
use DomainException;
use Illuminate\Console\Command;

class MigrateLegacySuperRoleCommand extends Command
{
    protected $signature = 'identity:migrate-super-role {--apply : Terapkan rename audited super-admin web menjadi super-system}';

    protected $description = 'Preview atau migrasi role super lama tanpa mengubah akun, password, atau relasi akses';

    public function handle(MigrateLegacySuperRole $migration): int
    {
        if (! $this->option('apply')) {
            $this->info($migration->preview()
                ? 'Preview: role super-admin web ditemukan. Tidak ada perubahan; gunakan --apply setelah persetujuan migrasi.'
                : 'Preview: role super-admin web tidak ditemukan. Tidak ada perubahan.');

            return self::SUCCESS;
        }

        try {
            $changed = $migration->handle();
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->info($changed
            ? 'Role super-admin web menjadi super-system. ID/relasi tetap; audit tersimpan dan cache permission dihapus.'
            : 'Role super-admin web tidak ditemukan. Tidak ada perubahan.');

        return self::SUCCESS;
    }
}
