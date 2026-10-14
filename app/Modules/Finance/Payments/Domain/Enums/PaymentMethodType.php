<?php

namespace App\Modules\Finance\Payments\Domain\Enums;

enum PaymentMethodType: string
{
    case Cash = 'cash';
    case Debit = 'debit';
    case Transfer = 'transfer';

    public function accountType(): PaymentAccountType
    {
        return $this === self::Cash ? PaymentAccountType::Cash : PaymentAccountType::Bank;
    }
}
