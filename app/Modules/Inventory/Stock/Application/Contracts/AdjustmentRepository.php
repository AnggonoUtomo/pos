<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

interface AdjustmentRepository
{
    public function find(string $id): ?array;

    public function create(array $record): void;
}
