<?php

namespace App\Modules\Inventory\Items\Application\Contracts;

use App\Modules\Inventory\Items\Application\Enums\ClassificationKind;

interface ItemClassificationRepository extends ItemClassificationLookup
{
    public function paginate(ClassificationKind $kind, int $page, array $filters): array;

    public function lock(ClassificationKind $kind, string $id, bool $deleted = false): array;

    public function active(ClassificationKind $kind, string $id): bool;

    public function referenced(ClassificationKind $kind, string $id): bool;

    public function save(ClassificationKind $kind, array $data, ?string $id = null): string;

    public function delete(ClassificationKind $kind, string $id): void;

    public function restore(ClassificationKind $kind, string $id): void;
}
