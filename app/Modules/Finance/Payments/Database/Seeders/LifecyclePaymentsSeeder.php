<?php

namespace App\Modules\Finance\Payments\Database\Seeders;

use App\Modules\Finance\Payments\Application\Actions\ManagePaymentAccount;
use App\Modules\Finance\Payments\Application\Actions\ManagePaymentMethod;
use App\Modules\Finance\Payments\Application\DTOs\PaymentAccountData;
use App\Modules\Finance\Payments\Application\DTOs\PaymentMethodData;
use Illuminate\Database\Seeder;

class LifecyclePaymentsSeeder extends Seeder
{
    public function run(array $context): array
    {
        $cash = app(ManagePaymentAccount::class)->save(new PaymentAccountData('LAT-KAS', 'Kas Latihan', 'cash'));
        $bank = app(ManagePaymentAccount::class)->save(new PaymentAccountData('LAT-BANK', 'Bank Latihan', 'bank'));
        foreach (['TUNAI' => ['Tunai', 'cash', $cash], 'DEBIT' => ['Debit', 'debit', $bank],
            'TRANSFER' => ['Transfer', 'transfer', $bank]] as $code => [$name, $type, $account]) {
            $context['methods'][$code] = app(ManagePaymentMethod::class)->save(new PaymentMethodData($code, $name, $type, $account));
        }

        return $context;
    }
}
