<?php

namespace App\Modules\Purchasing\Returns\Application\Queries;

use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\ReturnPurchaseLookup;
use App\Modules\Purchasing\Returns\Application\Contracts\ReturnDraftRepository;

final class ReturnWorkspace
{
    public function __construct(private ReturnDraftRepository $drafts, private ReturnPurchaseLookup $purchases) {}

    public function records(int $page, array $filters): array
    {
        return $this->drafts->paginate($page, $filters);
    }

    public function purchases(string $search): array
    {
        return array_map(fn ($purchase) => $purchase->toArray(), $this->purchases->searchEligible($search));
    }

    public function purchase(string $id): ?array
    {
        return $this->purchases->findEligible($id)?->toArray();
    }
}
