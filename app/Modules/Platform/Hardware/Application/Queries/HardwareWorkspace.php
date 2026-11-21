<?php

namespace App\Modules\Platform\Hardware\Application\Queries;

use App\Modules\Inventory\Items\Application\Contracts\ItemLabelCatalog;
use App\Modules\Platform\Hardware\Application\Contracts\HardwareAvailability;
use App\Modules\Platform\Hardware\Application\Contracts\HardwareRepository;

class HardwareWorkspace
{
    public function __construct(private HardwareRepository $repository, private HardwareAvailability $availability, private ItemLabelCatalog $catalog) {}

    public function page(int $actorId, bool $manage, array $filters): array
    {
        return ['profiles' => $this->repository->profiles($manage), 'allowedHosts' => $this->availability->allowedHosts(),
            'printingEnabled' => $this->availability->printingEnabled(), 'filters' => $filters,
            'history' => $this->repository->history($actorId, $manage, $filters['q'] ?? '', $filters['status'] ?? '', (int) ($filters['page'] ?? 1))];
    }

    public function catalog(string $query, int $page): array
    {
        $result = $this->catalog->search($query, $page);

        return ['data' => array_map(fn ($row) => $row->toArray(), $result['data']), 'has_more' => $result['has_more']];
    }
}
