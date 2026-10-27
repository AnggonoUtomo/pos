<?php

namespace App\Modules\Sales\Parties\Database\Seeders;

use App\Modules\Sales\Parties\Application\Actions\ManageCustomer;
use App\Modules\Sales\Parties\Application\Actions\ManageCustomerLevel;
use App\Modules\Sales\Parties\Application\DTOs\CustomerData;
use App\Modules\Sales\Parties\Application\DTOs\CustomerLevelData;
use Illuminate\Database\Seeder;

class LifecyclePartiesSeeder extends Seeder
{
    public function run(array $context): array
    {
        foreach (['ECERAN' => 'Eceran', 'GROSIR' => 'Grosir', 'INSTALLER' => 'Installer'] as $code => $name) {
            $context['levels'][$code] = app(ManageCustomerLevel::class)->save(new CustomerLevelData($code, $name));
            $context['customers'][$code] = app(ManageCustomer::class)->save(new CustomerData('LAT-'.$code, 'Pelanggan Latihan '.$name, $context['levels'][$code]));
        }

        return $context;
    }
}
