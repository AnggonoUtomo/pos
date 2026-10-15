<?php

namespace App\Modules\Inventory\Stock\Application\Contracts;

use App\Modules\Inventory\Stock\Domain\ValueObjects\FifoAllocation;

interface StockRepository
{
    public function createDraft(array $header, array $lines): string;

    public function lockDraft(string $id, bool $deleted = false): array;

    public function replaceDraft(string $id, array $header, array $lines): void;

    public function deleteDraft(string $id): void;

    public function restoreDraft(string $id): void;

    public function hasBalance(string $warehouseId, string $itemId): bool;

    public function postOpening(string $id, array $header, array $lines, ?int $actorId): void;

    public function paginate(string $tab, int $page, array $filters): array;

    public function lockIssueBySource(string $sourceId): ?array;

    public function createIssue(array $header): string;

    public function lockIssueInventory(string $warehouseId, string $itemId): array;

    public function postIssueLine(string $issueId, array $header, array $line, FifoAllocation $allocation, array $balance): void;

    public function auditIssue(string $id, ?int $actorId): void;
}
