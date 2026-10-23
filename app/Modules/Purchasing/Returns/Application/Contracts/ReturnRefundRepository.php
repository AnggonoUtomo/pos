<?php

namespace App\Modules\Purchasing\Returns\Application\Contracts;

interface ReturnRefundRepository
{
    public function available(): bool;

    public function findReturn(string $id, bool $lock = false): array;

    public function scope(string $purchaseId, bool $lock = false): ?array;

    public function settlement(string $returnId, bool $lock = false): ?array;

    public function allocations(string $scopeId, bool $lock = false): array;

    public function save(array $header, array $lines): void;

    public function audit(string $id, ?int $actorId): void;
}
