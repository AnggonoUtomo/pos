<?php

namespace App\Modules\Purchasing\Suppliers\Database\Seeders;

use App\Modules\Purchasing\Suppliers\Application\Actions\ManageSupplier;
use App\Modules\Purchasing\Suppliers\Application\DTOs\SupplierData;
use Illuminate\Database\Seeder;

class LifecycleSuppliersSeeder extends Seeder
{
    public function run(array $context): array
    {
        foreach (['CCTV' => 'Distributor CCTV Latihan', 'LISTRIK' => 'Distributor Listrik Latihan'] as $code => $name) {
            $context['suppliers'][$code] = app(ManageSupplier::class)->save(new SupplierData('LAT-SUP-'.$code, $name));
        }

        return $context;
    }
}
