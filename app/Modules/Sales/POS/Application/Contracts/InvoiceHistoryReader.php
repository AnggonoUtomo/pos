<?php

namespace App\Modules\Sales\POS\Application\Contracts;

interface InvoiceHistoryReader
{
    public function paginate(int $page, array $filters, ?int $actorId): array;

    public function options(?int $actorId): array;
}
