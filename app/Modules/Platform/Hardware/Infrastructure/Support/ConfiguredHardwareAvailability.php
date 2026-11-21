<?php

namespace App\Modules\Platform\Hardware\Infrastructure\Support;

use App\Modules\Platform\Hardware\Application\Contracts\HardwareAvailability;

class ConfiguredHardwareAvailability implements HardwareAvailability
{
    public function enabled(): bool
    {
        return config('pos_hardware.enabled') === true;
    }

    public function printingEnabled(): bool
    {
        return $this->enabled() && config('pos_hardware.printing_enabled') === true;
    }

    public function allowedHosts(): array
    {
        return array_values(array_filter(config('pos_hardware.allowed_hosts', []),
            fn ($host) => is_string($host) && preg_match('/^(?:[a-z0-9](?:[a-z0-9.-]{0,251}[a-z0-9])?)$/D', $host)));
    }
}
