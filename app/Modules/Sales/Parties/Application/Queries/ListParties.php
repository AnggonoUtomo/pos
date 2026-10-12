<?php

namespace App\Modules\Sales\Parties\Application\Queries;

use App\Modules\Sales\Parties\Application\Contracts\PartiesRepository;

class ListParties
{
    public function __construct(private PartiesRepository $repository) {}

    public function levels(int $page, array $filters): array
    {
        return $this->repository->paginateLevels($page, $filters);
    }

    public function customers(int $page, array $filters): array
    {
        return $this->repository->paginateCustomers($page, $filters);
    }

    public function levelChoices(): array
    {
        return $this->repository->levelChoices();
    }
}
