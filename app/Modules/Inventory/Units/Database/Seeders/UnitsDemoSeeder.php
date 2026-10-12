<?php

namespace App\Modules\Inventory\Units\Database\Seeders;

use App\Modules\Inventory\Units\Infrastructure\Persistence\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class UnitsDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Demo satuan hanya boleh dijalankan pada environment local/testing.');
        }

        DB::transaction(function (): void {
            foreach ([
                ['code' => 'PCS', 'name' => 'Buah', 'symbol' => 'pcs'],
                ['code' => 'PACK', 'name' => 'Pak', 'symbol' => 'pak'],
                ['code' => 'BOX', 'name' => 'Dus', 'symbol' => 'dus'],
                ['code' => 'UNIT', 'name' => 'Unit', 'symbol' => 'unit'],
                ['code' => 'SET', 'name' => 'Set', 'symbol' => 'set'],
                ['code' => 'PAIR', 'name' => 'Pasang', 'symbol' => 'pasang'],
                ['code' => 'M', 'name' => 'Meter', 'symbol' => 'm'],
                ['code' => 'ROLL', 'name' => 'Rol', 'symbol' => 'rol'],
            ] as $unit) {
                Unit::withTrashed()->firstOrCreate(
                    ['code' => $unit['code']],
                    ['name' => $unit['name'], 'symbol' => $unit['symbol'], 'is_active' => true],
                );
            }
        });
    }
}
