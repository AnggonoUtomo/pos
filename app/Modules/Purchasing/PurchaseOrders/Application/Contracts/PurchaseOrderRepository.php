<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\Contracts;

interface PurchaseOrderRepository
{
    public function lock(string $id, bool $deleted = false): array;

    public function save(array $header, array $lines, ?string $id = null): string;

    public function delete(string $id): void;

    public function restore(string $id): void;

    public function post(string $id, array $header, array $lines, string $receiptId, string $postedAt, int $version, ?int $actorId): void;

    public function pay(string $id, string $disbursementId, string $paidAt, array $snapshot, int $version, ?int $actorId): void;

    public function paginate(int $page, array $filters): array;

    public function demoExists(string $marker): bool;
}
