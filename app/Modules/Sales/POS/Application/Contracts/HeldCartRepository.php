<?php

namespace App\Modules\Sales\POS\Application\Contracts;

interface HeldCartRepository
{
    public function findOwned(string $id, int $actorId, bool $lock = false): ?array;

    public function listOwned(int $actorId, int $page): array;

    public function write(string $id, array $attributes, bool $create = false): array;

    public function cancel(string $id): void;

    public function audit(string $id, int $actorId, string $event): void;
}
