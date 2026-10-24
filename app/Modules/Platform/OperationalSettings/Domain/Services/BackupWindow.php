<?php

namespace App\Modules\Platform\OperationalSettings\Domain\Services;

use DateTimeImmutable;
use DateTimeZone;

final class BackupWindow
{
    public static function isDue(array $settings, ?array $run, DateTimeImmutable $now): bool
    {
        $local = $now->setTimezone(new DateTimeZone('Asia/Jakarta'));
        if (! $settings['backup_enabled'] || $local->format('H:i') < $settings['backup_time']) {
            return false;
        }
        if ($run === null) {
            return true;
        }
        if ($run['status'] === 'succeeded' || $run['attempts'] >= 3) {
            return false;
        }
        $started = new DateTimeImmutable($run['status'] === 'running' ? $run['started_at'] : ($run['finished_at'] ?? $run['started_at']), new DateTimeZone('UTC'));
        $delay = $run['status'] === 'running' ? 900 : 600;

        return $now->getTimestamp() - $started->getTimestamp() >= $delay;
    }
}
