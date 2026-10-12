<?php

namespace App\Modules\Sales\Parties\Application\Contracts;

interface PartiesRepository
{
    public function lockLevel(string $id, bool $deleted = false): array;

    public function lockCustomer(string $id, bool $deleted = false): array;

    public function customerLevelId(string $id, bool $deleted = false): string;

    public function isLevelReferenced(string $id): bool;

    public function saveLevel(array $data, ?string $id = null): string;

    public function saveCustomer(array $data, ?string $id = null): string;

    public function deleteLevel(string $id): void;

    public function restoreLevel(string $id): void;

    public function deleteCustomer(string $id): void;

    public function restoreCustomer(string $id): void;

    public function paginateLevels(int $page, array $filters): array;

    public function paginateCustomers(int $page, array $filters): array;

    public function levelChoices(): array;

    public function findLevelByCode(string $code): ?array;

    public function customerCodeExists(string $code): bool;
}
