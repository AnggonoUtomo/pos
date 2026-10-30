<?php

namespace App\Modules\Platform\AuditLog\Application\Contracts;

interface AuditLogReader
{
    public function paginate(int $page, array $filters): array;

    public function detail(string $id): array;

    public function options(): array;
}
