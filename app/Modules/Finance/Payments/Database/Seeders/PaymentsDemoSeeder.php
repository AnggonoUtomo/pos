<?php

namespace App\Modules\Finance\Payments\Database\Seeders;

use App\Modules\Finance\Payments\Application\Actions\ManagePaymentAccount;
use App\Modules\Finance\Payments\Application\Actions\ManagePaymentMethod;
use App\Modules\Finance\Payments\Application\Contracts\PaymentsRepository;
use App\Modules\Finance\Payments\Application\DTOs\PaymentAccountData;
use App\Modules\Finance\Payments\Application\DTOs\PaymentMethodData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('Demo Finance hanya local/testing.');
        }
        DB::transaction(function (): void {
            $repository = app(PaymentsRepository::class);
            foreach ([['CASH-DEMO', 'Kas Toko Demo', 'cash'], ['BANK-DEMO', 'Bank Operasional Demo', 'bank']] as [$code, $name, $type]) {
                if ($repository->findAccountByCode($code) === null) {
                    app(ManagePaymentAccount::class)->save(new PaymentAccountData($code, $name, $type));
                }
            }
            foreach ([['TUNAI', 'Tunai', 'cash', 'CASH-DEMO'], ['DEBIT', 'Debit', 'debit', 'BANK-DEMO'], ['TRANSFER', 'Transfer', 'transfer', 'BANK-DEMO']] as [$code, $name, $type, $accountCode]) {
                if ($repository->methodCodeExists($code)) {
                    continue;
                }
                $account = $repository->findAccountByCode($accountCode);
                if ($account === null || $account['deleted_at'] !== null) {
                    throw new \LogicException('Account demo harus tersedia sebelum metode dibuat.');
                }
                app(ManagePaymentMethod::class)->save(new PaymentMethodData($code, $name, $type, $account['id']));
            }
        });
    }
}
