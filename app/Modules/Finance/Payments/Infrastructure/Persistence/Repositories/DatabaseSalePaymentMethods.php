<?php

namespace App\Modules\Finance\Payments\Infrastructure\Persistence\Repositories;

use App\Modules\Finance\Payments\Application\Contracts\SalePaymentMethods;
use App\Modules\Finance\Payments\Infrastructure\Persistence\Models\PaymentMethod;

class DatabaseSalePaymentMethods implements SalePaymentMethods
{
    public function choices(): array
    {
        return PaymentMethod::where('is_active', true)->whereHas('account', function ($q): void {
            $q->where('is_active', true)->whereNull('deleted_at')->where(function ($q): void {
                $q->where(fn ($q) => $q->where('payment_methods.type', 'cash')->where('payment_accounts.type', 'cash'))
                    ->orWhere(fn ($q) => $q->whereIn('payment_methods.type', ['debit', 'transfer'])->where('payment_accounts.type', 'bank'));
            });
        })->orderBy('code')->orderBy('id')->limit(1000)->get(['id', 'code', 'name', 'type'])->toArray();
    }
}
