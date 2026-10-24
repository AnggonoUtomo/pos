<?php

namespace App\Modules\Platform\OperationalSettings\Application\Actions;

use App\Modules\Platform\OperationalSettings\Application\Contracts\BackupExecutor;
use App\Modules\Platform\OperationalSettings\Application\Contracts\OperationalSettingsRepository;
use Throwable;

class RunScheduledBackup
{
    public function __construct(private OperationalSettingsRepository $settings, private BackupExecutor $executor) {}

    public function handle(): string
    {
        $claim = $this->settings->claimDue();
        if ($claim === null) {
            return 'skipped';
        }
        try {
            $file = $this->executor->run();
        } catch (Throwable) {
            $this->settings->finish($claim, 'failed', null);

            return 'failed';
        }
        $this->settings->finish($claim, 'succeeded', $file);

        return 'succeeded';
    }
}
