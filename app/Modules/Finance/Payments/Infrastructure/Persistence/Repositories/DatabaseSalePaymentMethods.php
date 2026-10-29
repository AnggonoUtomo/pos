<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\Finance\Payments\Application\Contracts\PurchasePaymentMethods;
use App\Modules\Finance\Payments\Application\Contracts\PurchaseRefundReceiptMethods;
use App\Modules\Finance\Payments\Application\Contracts\SalePaymentMethods;
use App\Modules\Finance\Payments\Application\Contracts\SaleRefundPaymentMethods;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\PaymentMethod;

class DatabaseSalePaymentMethods implements PurchasePaymentMethods, PurchaseRefundReceiptMethods, SalePaymentMethods, SaleRefundPaymentMethods
{
    public function choices(): array
    {
        return $this->available();
    }

    public function forDrawer(string $accountId): array
    {
        return $this->available($accountId);
    }

    private function available(?string $accountId = null): array
    {
        return PaymentMethod::where('is_active', true)->whereHas('account', function ($q): void {
            $q->where('is_active', true)->whereNull('deleted_at')->where(function ($q): void {
                $q->where(fn ($q) => $q->where('payment_methods.type', 'cash')->where('payment_accounts.type', 'cash'))
                    ->orWhere(fn ($q) => $q->whereIn('payment_methods.type', ['debit', 'transfer'])->where('payment_accounts.type', 'bank'));
            });
        })->when($accountId !== null, fn ($q) => $q->where(fn ($q) => $q->where('type', '!=', 'cash')->orWhere('payment_account_id', $accountId)))
            ->orderBy('code')->orderBy('id')->limit(1000)->get(['id', 'code', 'name', 'type'])->toArray();
    }
}
