<?php

namespace App\Modules\Sales\Returns\Application\Queries;

use App\Modules\Sales\POS\Application\Contracts\ReturnInvoiceLookup;
use App\Modules\Sales\Returns\Application\Contracts\ReturnDraftRepository;

final class ReturnWorkspace
{
    public function __construct(private ReturnDraftRepository $drafts, private ReturnInvoiceLookup $invoices) {}

    public function records(int $page, string $search, bool $trashed, string $status = 'all'): array
    {
        return $this->drafts->paginate($page, $search, $trashed, $status);
    }

    public function invoices(string $search): array
    {
        return $this->invoices->search($search);
    }

    public function invoice(string $id): ?array
    {
        return $this->invoices->find($id)?->toArray();
    }
}
