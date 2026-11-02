<?php

namespace App\Modules\Inventory\Items\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\ItemClassificationRepository;
use App\Modules\Inventory\Items\Application\Enums\ClassificationKind;

final class ListItemClassifications
{
    public function __construct(private ItemClassificationRepository $repository) {}

    public function handle(ClassificationKind $kind, int $page, array $filters): array
    {
        return $this->repository->paginate($kind, $page, $filters);
    }
}
