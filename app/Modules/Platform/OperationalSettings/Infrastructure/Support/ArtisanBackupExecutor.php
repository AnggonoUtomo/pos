<?php

namespace App\Modules\Platform\OperationalSettings\Infrastructure\Support;

use App\Modules\Platform\OperationalSettings\Application\Contracts\BackupExecutor;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class ArtisanBackupExecutor implements BackupExecutor
{
    public function run(): string
    {
        $result = Artisan::call('pos:backup');
        if ($result !== 0 || ! preg_match('~Backup tersimpan: storage/app/private/backups/(pos-[0-9]{8}-[0-9]{6}-[a-f0-9]{8}\.sql)~', Artisan::output(), $match)) {
            throw new RuntimeException('Backup gagal.');
        }

        return $match[1];
    }
}
