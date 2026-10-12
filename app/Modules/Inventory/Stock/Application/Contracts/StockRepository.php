<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

interface StockRepository
{
    public function createDraft(array $header, array $lines): string;

    public function lockDraft(string $id, bool $deleted = false): array;

    public function replaceDraft(string $id, array $header, array $lines): void;

    public function deleteDraft(string $id): void;

    public function restoreDraft(string $id): void;
}
