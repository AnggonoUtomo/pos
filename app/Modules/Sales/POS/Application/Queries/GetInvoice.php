<?php

namespace App\Modules\Sales\POS\Application\Queries;

use App\Modules\Sales\POS\Application\Contracts\InvoiceRepository;
use App\Modules\Sales\POS\Application\DTOs\InvoiceReceipt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GetInvoice
{
    public function __construct(private InvoiceRepository $invoices) {}

    public function handle(string $id, int $actorId, bool $viewAll): array
    {
        $row = $this->invoices->find(strtolower($id)) ?? throw new ModelNotFoundException;
        if (! $viewAll && $row['actor_id'] !== $actorId) {
            throw new AuthorizationException;
        }

        return InvoiceReceipt::fromRecord($row);
    }
}
