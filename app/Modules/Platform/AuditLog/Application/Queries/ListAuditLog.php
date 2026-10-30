<?php

namespace App\Modules\Platform\AuditLog\Application\Queries;

use App\Modules\Platform\AuditLog\Application\Contracts\AuditLogReader;
use Carbon\CarbonImmutable;

class ListAuditLog
{
    public function __construct(private AuditLogReader $reader) {}

    public function records(int $page, array $filters): array
    {
        foreach (['date_from' => 'from', 'date_to' => 'until'] as $key => $boundary) {
            $date = $filters[$key] ? CarbonImmutable::createFromFormat('!Y-m-d', $filters[$key], 'Asia/Jakarta') : null;
            if ($boundary === 'until') {
                $date = $date?->addDay();
            }
            $filters[$boundary] = $date?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
        }

        return $this->reader->paginate($page, $filters);
    }

    public function detail(string $id): array
    {
        return $this->reader->detail($id);
    }

    public function options(): array
    {
        return $this->reader->options();
    }
}
