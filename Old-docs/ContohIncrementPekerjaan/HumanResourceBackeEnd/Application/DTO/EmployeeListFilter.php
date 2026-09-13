<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Application\DTO;

final readonly class EmployeeListFilter
{
    public function __construct(
        public ?string $search,
        public ?string $status,
        public ?string $employmentType,
        public ?string $primaryUnitId,
        public int $page,
        public int $perPage,
        public string $sortField,
        public string $sortDirection,
    ) {}
}
