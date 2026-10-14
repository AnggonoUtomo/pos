<?php

namespace App\Modules\Finance\Payments\Domain\Enums;

enum PaymentAccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
}
