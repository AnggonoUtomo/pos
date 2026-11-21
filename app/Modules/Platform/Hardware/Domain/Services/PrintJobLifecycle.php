<?php

namespace App\Modules\Platform\Hardware\Domain\Services;

use LogicException;

final class PrintJobLifecycle
{
    public const OUTCOMES = ['submitted', 'failed_before_send', 'uncertain'];

    public const ERRORS = ['gateway_unavailable', 'printer_not_found', 'permission_denied', 'timeout', 'transport_error', 'ack_lost', 'cancelled', 'render_error'];

    public static function assertClaimable(string $status): void
    {
        if ($status !== 'queued') {
            throw new LogicException('Job sudah diambil. Periksa hasil sebelum membuat cetak ulang.');
        }
    }

    public static function canReprint(string $status): bool
    {
        return in_array($status, self::OUTCOMES, true);
    }
}
