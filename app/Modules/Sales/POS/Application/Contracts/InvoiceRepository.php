<?php

namespace App\Modules\Sales\POS\Application\Contracts;

interface InvoiceRepository
{
    public function lockByKey(int $actorId, string $key): ?array;

    public function find(string $id): ?array;

    public function createPosted(array $header, array $lines): void;

    public function audit(string $id, int $actorId): void;
}
