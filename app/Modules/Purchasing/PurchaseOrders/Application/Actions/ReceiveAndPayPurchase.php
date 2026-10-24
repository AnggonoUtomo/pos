<?php

namespace App\Modules\Purchasing\PurchaseOrders\Application\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class ReceiveAndPayPurchase
{
    public function __construct(private PostPurchaseOrder $receive, private PayPurchaseOrder $pay) {}

    public function handle(string $id, mixed $version, array $payments, int $actorId): array
    {
        Validator::make(['version' => $version], ['version' => ['required', 'integer', 'min:1', 'max:2147483645']])->validate();

        return DB::transaction(function () use ($id, $version, $payments, $actorId): array {
            $this->receive->handle($id, $version, $actorId);

            return $this->pay->handle($id, (int) $version + 1, $payments, $actorId);
        }, 3);
    }
}
