<?php

namespace App\Modules\Inventory\Units\Database\Seeders;

use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use Illuminate\Database\Seeder;

class LifecycleUnitsSeeder extends Seeder
{
    public function run(array $context): array
    {
        foreach (['UNIT' => ['Unit', 'unit'], 'BOX' => ['Dus', 'dus'], 'M' => ['Meter', 'm'],
            'ROLL' => ['Rol', 'rol'], 'PCS' => ['Buah', 'pcs'], 'PACK' => ['Pak', 'pak']] as $code => [$name, $symbol]) {
            $context['units'][$code] = Unit::create(['code' => $code, 'name' => $name, 'symbol' => $symbol, 'is_active' => true])->id;
        }

        return $context;
    }
}
