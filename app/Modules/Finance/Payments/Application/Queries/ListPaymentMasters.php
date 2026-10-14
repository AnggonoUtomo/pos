<?php

namespace App\Modules\Finance\Payments\Application\Queries;

use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;

class ListPaymentMasters
{
    public function __construct(private PaymentsRepository $repository) {}

    public function accounts(int $page, array $filters): array
    {
        return $this->repository->paginateAccounts($page, $filters);
    }

    public function methods(int $page, array $filters): array
    {
        return $this->repository->paginateMethods($page, $filters);
    }

    public function accountChoices(): array
    {
        return $this->repository->accountChoices();
    }
}
