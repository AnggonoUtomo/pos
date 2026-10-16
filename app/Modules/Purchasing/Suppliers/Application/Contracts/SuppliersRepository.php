<?php

namespace App\Modules\Purchasing\Suppliers\Application\Contracts;

interface SuppliersRepository
{
    public function save(array $data, ?string $id = null): string;

    public function delete(string $id): void;

    public function restore(string $id): void;

    public function paginate(int $page, array $filters): array;

    public function codeExists(string $code): bool;
}
