<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

interface PaymentsRepository
{
    public function lockAccount(string $id, bool $deleted = false): array;

    public function lockMethod(string $id, bool $deleted = false): array;

    public function methodAccountId(string $id, bool $deleted = false): string;

    public function isAccountReferenced(string $id): bool;

    public function hasMethodHistory(string $id): bool;

    public function lockReceiptBySource(string $sourceId): ?array;

    public function createReceipt(array $header, array $lines): string;

    public function auditReceipt(string $id, ?int $actorId): void;

    public function saveAccount(array $data, ?string $id = null): string;

    public function saveMethod(array $data, ?string $id = null): string;

    public function deleteAccount(string $id): void;

    public function restoreAccount(string $id): void;

    public function deleteMethod(string $id): void;

    public function restoreMethod(string $id): void;

    public function paginateAccounts(int $page, array $filters): array;

    public function paginateMethods(int $page, array $filters): array;

    public function accountChoices(): array;

    public function findAccountByCode(string $code): ?array;

    public function methodCodeExists(string $code): bool;
}
