<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Application\Queries;

use App\Modules\HumanResource\HumanResource\Application\Contracts\HumanResourceRepository;
use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeListFilter;
use App\Modules\HumanResource\HumanResource\Application\DTO\PaginatedEmployeeData;

final readonly class ListEmployees
{
    public function __construct(private HumanResourceRepository $repository) {}

    public function execute(EmployeeListFilter $filter): PaginatedEmployeeData
    {
        return $this->repository->paginateEmployees($filter);
    }
}
