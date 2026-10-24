<?php

namespace App\Modules\Inventory\Transfers\Application\Contracts;

interface TransferDraftRepository
{
    public function lock(string $id, bool $deleted = false): array;

    public function save(array $header, array $lines, ?string $id = null): string;

    public function delete(string $id): void;

    public function restore(string $id): void;

    public function paginate(int $page, array $filters): array;

    public function demoExists(string $marker): bool;
}
