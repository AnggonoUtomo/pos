<?php

namespace App\Modules\Platform\OperationalSettings\Presentation\Console\Commands;

use App\Modules\Platform\OperationalSettings\Application\Actions\RunScheduledBackup;
use Illuminate\Console\Command;
use Throwable;

class RunDueBackup extends Command
{
    protected $signature = 'pos:backup-due';

    protected $description = 'Jalankan backup bila jadwal operasional WIB aktif dan jatuh tempo';

    public function handle(RunScheduledBackup $action): int
    {
        try {
            $status = $action->handle();
            $this->line('Backup terjadwal: '.$status);

            return $status === 'failed' ? self::FAILURE : self::SUCCESS;
        } catch (Throwable) {
            $this->error('Backup terjadwal gagal. Periksa schema, layanan dan status operasional.');

            return self::FAILURE;
        }
    }
}
