<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

interface CashbookRepository
{
    public function opening(string $accountId, bool $lock = false): ?array;

    public function entry(string $id, bool $lock = false): ?array;

    public function entryByKey(string $key): ?array;

    public function createOpening(array $data): string;

    public function createEntry(array $data): string;

    public function audit(string $type, string $id, int $actor): void;
}
