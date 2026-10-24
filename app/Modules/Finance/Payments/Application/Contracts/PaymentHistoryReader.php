<?php

namespace App\Modules\Finance\Payments\Application\Contracts;

interface PaymentHistoryReader
{
    public function summary(string $from, string $until): array;

    public function paginate(int $page, array $filters): array;

    public function detail(string $direction, string $id): array;

    public function options(): array;
}
