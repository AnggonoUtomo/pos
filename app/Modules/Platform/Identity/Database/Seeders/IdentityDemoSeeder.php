<?php

namespace App\Modules\Platform\Identity\Database\Seeders;

use Illuminate\Database\Seeder;

class IdentityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(IdentityAccessSeeder::class);
    }
}
