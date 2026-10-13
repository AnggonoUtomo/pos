<?php

namespace App\Modules\Sales\POS\Database\Seeders;

use Illuminate\Database\Seeder;

class POSDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('Demo POS hanya local/testing.');
        }
        // POS has no persistence yet; fixtures compose the owning modules' demo seeders.
    }
}
