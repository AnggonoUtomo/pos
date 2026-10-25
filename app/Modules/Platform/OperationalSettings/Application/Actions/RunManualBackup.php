<?php

namespace App\Modules\Platform\OperationalSettings\Application\Actions;

use App\Modules\Platform\OperationalSettings\Application\Contracts\BackupExecutor;
use App\Modules\Platform\OperationalSettings\Application\Contracts\OperationalSettingsRepository;
use Illuminate\Validation\ValidationException;
use Throwable;

class RunManualBackup
{
    public function __construct(private BackupExecutor $executor, private OperationalSettingsRepository $settings) {}

    public function handle(int $actorId): string
    {
        $this->settings->recordManualBackup('requested', null, $actorId);
        try {
            $file = $this->executor->run();
        } catch (Throwable) {
            $this->settings->recordManualBackup('failed', null, $actorId);
            throw ValidationException::withMessages(['backup' => 'Backup gagal atau backup lain sedang berjalan. Periksa layanan MySQL, tool backup dan kapasitas disk.']);
        }
        $this->settings->recordManualBackup('succeeded', $file, $actorId);

        return $file;
    }
}
