<?php

namespace App\Modules\Purchasing\Suppliers\Application\Queries;

use App\Modules\Purchasing\Suppliers\Application\Contracts\SuppliersRepository;

class ListSuppliers
{
    public function __construct(private SuppliersRepository $repository) {}

    public function handle(int $page, array $filters): array
    {
        return $this->repository->paginate($page, $filters);
    }
}
