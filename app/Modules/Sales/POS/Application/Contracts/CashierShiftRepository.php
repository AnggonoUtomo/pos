<?php

namespace App\Modules\Sales\POS\Application\Contracts;

interface CashierShiftRepository extends ActiveShiftLookup
{
    public function lockActor(int $id): array;

    public function active(int $actorId, bool $lock = false): ?array;

    public function occupied(string $accountId, bool $lock = false): bool;

    public function find(string $id, bool $lock = false): ?array;

    public function save(array $data, ?string $id = null): string;

    public function summary(string $id): array;

    public function paginate(?int $actorId, int $page): array;

    public function audit(string $id, string $event, int $actor): void;
}
